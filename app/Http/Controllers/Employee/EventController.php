<?php

namespace App\Http\Controllers\Employee;

use App\Models\MstEvent;
use App\Models\MstTagEvent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    // Check authentication before accessing
    public function __construct()
    {
        if (session('user_type') !== 'Employee') {
            return redirect('/employee/login');
        }
    }

    // ============ EVENT CRUD ============

    /**
     * Display all events with tag count
     */
    public function index()
    {
        // Avoid grouping/sorting on text/ntext columns (SQL Server restriction)
        // Aggregate the potentially large text column (description) with MAX after casting
        $events = DB::select('
            SELECT e.event_id, e.event_name,
                   MAX(CAST(e.description AS NVARCHAR(MAX))) AS description,
                   e.location, e.status, e.created_at, e.updated_at, e.created_by, e.updated_by,
                   COUNT(t.tag_id) as tag_count
            FROM mst_events e
            LEFT JOIN mst_tag_events t ON e.event_id = t.event_id
            GROUP BY e.event_id, e.event_name, e.location, e.status, e.created_at, e.updated_at, e.created_by, e.updated_by
            ORDER BY e.event_id DESC
        ');

        // Fetch all tags for the returned events in one query and attach them to each event
        $eventIds = array_map(function ($e) { return $e->event_id; }, $events);
        $tagsByEvent = [];
        if (!empty($eventIds)) {
            // Use parameter placeholders
            $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
            $tagRows = DB::select(
                "SELECT t.event_id, t.tag_code, d.item_name FROM mst_tag_events t LEFT JOIN mst_detail_settings d ON t.tag_code = d.item_code AND d.header_id = 'TAG_EVENT' WHERE t.event_id IN ($placeholders) ORDER BY t.tag_id DESC",
                $eventIds
            );

            foreach ($tagRows as $r) {
                $label = $r->item_name ?? $r->tag_code;
                $tagsByEvent[$r->event_id][] = $label;
            }
        }

        // Attach tags array to each event (empty array if none)
        foreach ($events as $e) {
            $e->tags = $tagsByEvent[$e->event_id] ?? [];
        }

        return view('employee.events.index', compact('events'));
    }

    /**
     * Show form for creating new event
     */
    public function create()
    {
        // Load tag options from settings for the create form
        $availableTags = DB::select(
            "SELECT item_code AS tag_code, item_name FROM mst_detail_settings WHERE header_id = ? AND status = ? ORDER BY item_name",
            ['TAG_EVENT', 'Active']
        );

        return view('employee.events.create', compact('availableTags'));
    }

    /**
     * Store new event in database
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|string|max:50|unique:mst_events',
            'event_name' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'status' => 'required|in:Upcoming,Ongoing,Completed,Cancelled',
            'tag_codes' => 'nullable|array',
            'tag_codes.*' => 'required_with:tag_codes|string|max:50',
        ]);

        // If tag_codes provided, ensure they exist in mst_detail_settings for TAG_EVENT
        if (!empty($validated['tag_codes'])) {
            $rows = DB::select(
                "SELECT item_code FROM mst_detail_settings WHERE header_id = ? AND status = ? AND item_code IN (" .
                implode(',', array_fill(0, count($validated['tag_codes']), '?')) . ")",
                array_merge(['TAG_EVENT', 'Active'], $validated['tag_codes'])
            );
            $found = array_map(function ($r) { return $r->item_code; }, $rows);
            $diff = array_diff($validated['tag_codes'], $found);
            if (!empty($diff)) {
                return back()->withInput()->withErrors(['tag_codes' => 'One or more selected tags are invalid.']);
            }
        }

        $validated['created_by'] = session('employee_id');
        $validated['updated_by'] = session('employee_id');
        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        // Use transaction: create event and insert tags if provided
        DB::beginTransaction();
        try {
            MstEvent::create($validated);

            if (!empty($validated['tag_codes'])) {
                foreach ($validated['tag_codes'] as $tagCode) {
                    $tagId = $validated['event_id'] . '_' . $tagCode;

                    // Avoid duplicate
                    $exists = DB::select('SELECT TOP (1) 1 AS found FROM mst_tag_events WHERE tag_id = ?', [$tagId]);
                    if (empty($exists)) {
                        MstTagEvent::create([
                            'tag_id' => $tagId,
                            'event_id' => $validated['event_id'],
                            'tag_code' => $tagCode,
                            'created_by' => session('employee_id'),
                            'updated_by' => session('employee_id'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('employee.events.index')
                           ->with('success', 'Event created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Show form for editing event
     */
    public function edit($id)
    {
        $event = DB::select(
            'SELECT * FROM mst_events WHERE event_id = ?',
            [$id]
        );

        if (empty($event)) {
            return redirect()->route('employee.events.index')
                           ->with('error', 'Event not found!');
        }

        // Load tag options and assigned tags for edit form
        $availableTags = DB::select(
            "SELECT item_code AS tag_code, item_name FROM mst_detail_settings WHERE header_id = ? AND status = ? ORDER BY item_name",
            ['TAG_EVENT', 'Active']
        );

        $assignedTags = DB::select('SELECT tag_code FROM mst_tag_events WHERE event_id = ?', [$id]);
        $assignedTagCodes = array_map(function($t){ return $t->tag_code; }, $assignedTags);

        return view('employee.events.edit', [
            'event' => $event[0],
            'availableTags' => $availableTags,
            'assignedTags' => $assignedTagCodes,
        ]);
    }

    /**
     * Update event in database
     */
    public function update(Request $request, $id)
    {
        $event = MstEvent::findOrFail($id);

        $validated = $request->validate([
            'event_name' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'status' => 'required|in:Upcoming,Ongoing,Completed,Cancelled',
            'tag_codes' => 'nullable|array',
            'tag_codes.*' => 'required_with:tag_codes|string|max:50',
        ]);

        $validated['updated_by'] = session('employee_id');
        $validated['updated_at'] = now();

        DB::beginTransaction();
        try {
            // Update event fields
            $event->update([
                'event_name' => $validated['event_name'],
                'description' => $validated['description'],
                'location' => $validated['location'],
                'status' => $validated['status'],
                'updated_by' => $validated['updated_by'],
                'updated_at' => $validated['updated_at'],
            ]);

            // Validate provided tag_codes exist in settings
            $submittedTags = $validated['tag_codes'] ?? [];
            if (!empty($submittedTags)) {
                $rows = DB::select(
                    "SELECT item_code FROM mst_detail_settings WHERE header_id = ? AND status = ? AND item_code IN (" .
                    implode(',', array_fill(0, count($submittedTags), '?')) . ")",
                    array_merge(['TAG_EVENT', 'Active'], $submittedTags)
                );
                $found = array_map(function ($r) { return $r->item_code; }, $rows);
                $diff = array_diff($submittedTags, $found);
                if (!empty($diff)) {
                    DB::rollBack();
                    return back()->withInput()->withErrors(['tag_codes' => 'One or more selected tags are invalid.']);
                }
            }

            // Sync tags: compute existing, to add, to remove
            $existing = DB::select('SELECT tag_code FROM mst_tag_events WHERE event_id = ?', [$id]);
            $existingCodes = array_map(function($r){ return $r->tag_code; }, $existing);

            $toAdd = array_diff($submittedTags, $existingCodes);
            $toRemove = array_diff($existingCodes, $submittedTags);

            if (!empty($toRemove)) {
                DB::delete(
                    'DELETE FROM mst_tag_events WHERE event_id = ? AND tag_code IN (' . implode(',', array_fill(0, count($toRemove), '?')) . ')',
                    array_merge([$id], $toRemove)
                );
            }

            foreach ($toAdd as $tagCode) {
                $tagId = $id . '_' . $tagCode;
                MstTagEvent::create([
                    'tag_id' => $tagId,
                    'event_id' => $id,
                    'tag_code' => $tagCode,
                    'created_by' => session('employee_id'),
                    'updated_by' => session('employee_id'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('employee.events.index')
                           ->with('success', 'Event updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating event: ' . $e->getMessage());
        }
    }

    /**
     * Delete event and all its tags
     */
    public function destroy($id)
    {
        $event = MstEvent::findOrFail($id);

        // Delete all tags first
        MstTagEvent::where('event_id', $id)->delete();

        // Delete event
        $event->delete();

        return redirect()->route('employee.events.index')
                       ->with('success', 'Event and all tags deleted successfully!');
    }

    // ============ TAG EVENT CRUD ============

    /**
     * Display all tags for an event
     */
    public function indexTag($eventId)
    {
        // Check if event exists
        $event = DB::select(
            'SELECT * FROM mst_events WHERE event_id = ?',
            [$eventId]
        );

        if (empty($event)) {
            return redirect()->route('employee.events.index')
                           ->with('error', 'Event not found!');
        }

        // Fetch tags and include human-friendly name from mst_detail_settings if available
        $tags = DB::select(
            "SELECT t.*, d.item_name FROM mst_tag_events t\n             LEFT JOIN mst_detail_settings d ON t.tag_code = d.item_code AND d.header_id = 'TAG_EVENT'\n             WHERE t.event_id = ? ORDER BY t.tag_id DESC",
            [$eventId]
        );

        // Get all available tag codes for the select2 dropdown
            // Get tag options from mst_detail_settings where header_id = 'TAG_EVENT'
            $availableTags = DB::select(
                "SELECT item_code AS tag_code, item_name FROM mst_detail_settings WHERE header_id = ? AND status = ? ORDER BY item_name",
                ['TAG_EVENT', 'Active']
            );

        return view('employee.events.index-tag', [
            'event' => $event[0],
            'tags' => $tags,
            'availableTags' => $availableTags
        ]);
    }

    /**
     * Show form for creating multiple tags at once (Select2 multiple)
     */
    public function createTag($eventId)
    {
        // Check if event exists
        $event = DB::select(
            'SELECT * FROM mst_events WHERE event_id = ?',
            [$eventId]
        );

        if (empty($event)) {
            return redirect()->route('employee.events.index')
                           ->with('error', 'Event not found!');
        }

            // Get tag options from mst_detail_settings where header_id = 'TAG_EVENT'
            $availableTags = DB::select(
                "SELECT item_code AS tag_code, item_name FROM mst_detail_settings WHERE header_id = ? AND status = ? ORDER BY item_name",
                ['TAG_EVENT', 'Active']
            );

        // Get already assigned tags
        $assignedTags = DB::select(
            'SELECT tag_code FROM mst_tag_events WHERE event_id = ?',
            [$eventId]
        );

        $assignedTagCodes = array_map(function($tag) {
            return $tag->tag_code;
        }, $assignedTags);

        return view('employee.events.create-tag', [
            'event' => $event[0],
            'availableTags' => $availableTags,
            'assignedTags' => $assignedTagCodes
        ]);
    }

    /**
     * Store multiple tags for event (handles Select2 multiple selection)
     */
    public function storeTag(Request $request, $eventId)
    {
        $validated = $request->validate([
            'tag_codes' => 'required|array|min:1',
            'tag_codes.*' => 'required|string|max:50',
        ]);

        // Get existing tags for this event
        $existingTags = DB::select(
            'SELECT tag_code FROM mst_tag_events WHERE event_id = ?',
            [$eventId]
        );

        $existingTagCodes = array_map(function($tag) {
            return $tag->tag_code;
        }, $existingTags);

        // Determine which tags to add and which to remove
        $tagsToAdd = array_diff($validated['tag_codes'], $existingTagCodes);
        $tagsToRemove = array_diff($existingTagCodes, $validated['tag_codes']);

        // Remove old tags
        if (!empty($tagsToRemove)) {
            DB::delete(
                'DELETE FROM mst_tag_events WHERE event_id = ? AND tag_code IN (' . 
                implode(',', array_fill(0, count($tagsToRemove), '?')) . ')',
                array_merge([$eventId], $tagsToRemove)
            );
        }

        // Add new tags
        foreach ($tagsToAdd as $tagCode) {
            $tagId = $eventId . '_' . $tagCode;

            MstTagEvent::create([
                'tag_id' => $tagId,
                'event_id' => $eventId,
                'tag_code' => $tagCode,
                'created_by' => session('employee_id'),
                'updated_by' => session('employee_id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('employee.events.tag', $eventId)
                       ->with('success', count($tagsToAdd) . ' tag(s) added successfully!');
    }

    /**
     * Delete a specific tag from event
     */
    public function destroyTag($eventId, $tagId)
    {
        $tag = MstTagEvent::findOrFail($tagId);
        $tag->delete();

        return redirect()->route('employee.events.tag', $eventId)
                       ->with('success', 'Tag deleted successfully!');
    }
}
