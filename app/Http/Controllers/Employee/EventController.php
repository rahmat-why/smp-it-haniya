<?php

namespace App\Http\Controllers\Employee;

use App\Models\MstEvent;
use App\Models\MstTagEvent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
class EventController extends Controller
{
  public function index()
{
    return view('events.index');
}

public function getData()
{
    $events = DB::table('mst_events as e')
        ->select(
            'e.event_id',
            'e.event_name',
            'e.location',
            'e.status',
            DB::raw("
                (
                    SELECT STRING_AGG(
                        COALESCE(d.item_name, t.tag_code), ', '
                    )
                    FROM mst_tag_events t
                    LEFT JOIN mst_detail_settings d 
                        ON t.tag_code = d.item_code 
                       AND d.header_id = 'TAG_EVENT'
                    WHERE t.event_id = e.event_id
                ) AS tags
            ")
        );

    return DataTables::of($events)

        // Status Badge
        ->addColumn('status_badge', function ($row) {

            $color = 'secondary';

            if ($row->status === 'Ongoing') $color = 'danger';
            elseif ($row->status === 'Upcoming') $color = 'warning';
            elseif ($row->status === 'Completed') $color = 'success';

            return "<span class='badge bg-{$color}'>{$row->status}</span>";
        })

        // Tag badge
        ->addColumn('tags_badge', function ($row) {

            if (!$row->tags) {
                return '<span class="text-muted">-</span>';
            }

            // Pisah berdasarkan koma
            $tagList = explode(',', $row->tags);

            return collect($tagList)
                ->map(fn($t) => "<span class='badge bg-info'>".trim($t)."</span>")
                ->implode(' ');
        })

        // Action buttons
        ->addColumn('action', function ($row) {

            $edit   = route('employee.events.edit', $row->event_id);
            $delete = route('employee.events.destroy', $row->event_id);
            $csrf   = csrf_token();

            $btn  = "<a href='{$edit}' class='btn btn-sm btn-warning me-1'>
                        <i class='fas fa-edit'></i>
                     </a>";

            $btn .= "<form action='{$delete}' method='POST' style='display:inline;' 
                        onsubmit=\"return confirm('Are you sure?')\">
                        <input type='hidden' name='_token' value='{$csrf}'>
                        <input type='hidden' name='_method' value='DELETE'>
                        <button type='submit' class='btn btn-sm btn-danger'>
                            <i class='fas fa-trash'></i>
                        </button>
                    </form>";

            return $btn;
        })

        ->rawColumns(['status_badge', 'tags_badge', 'action'])
        ->make(true);
}





    /**
     * Show form for creating new event
     */
  public function create()
{
    // ===============================
    // Ambil data Tag untuk dropdown
    // ===============================
    $availableTags = DB::select("
        SELECT item_code AS tag_code, item_name
        FROM mst_detail_settings
        WHERE header_id = ? AND status = ?
        ORDER BY item_name ASC
    ", ['TAG_EVENT', 'Active']);

    // ===============================
    // Generate New Event ID
    // Format: EVT00001, EVT00002, dst
    // ===============================

    $last = DB::table('mst_events')
        ->orderBy('event_id', 'desc')
        ->first();

    if ($last && preg_match('/(\d+)/', $last->event_id, $m)) {
        $num = intval($m[1]) + 1;
        $newEventId = 'EVT' . str_pad($num, 5, '0', STR_PAD_LEFT);
    } else {
        $newEventId = 'EVT00001';
    }

    // ===============================
    // Kirim data ke view
    // ===============================
    return view('events.create', [
        'availableTags' => $availableTags,
        'newEventId' => $newEventId
    ]);
}

public function store(Request $request)
{
    // === VALIDATION ===
    $validated = $request->validate([
        'event_name'   => 'required|string|max:255',
        'description'  => 'required|string',
        'location'     => 'required|string|max:255',
        'status'       => 'required|in:Upcoming,Ongoing,Completed,Cancelled',

        'tag_codes'    => 'nullable|array',
        'tag_codes.*'  => 'required_with:tag_codes|string|max:50',

        'event_id'     => 'nullable|string|max:50',

        'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', 
    ]);

    // === UPLOAD FOTO (SEBELUM LOOPING SUPAYA TIDAK DUPLIKAT FILE) ===
    $photoPath = null;
    if ($request->hasFile('profile_photo')) {
        $photoPath = $request->file('profile_photo')->store('events', 'public');
    }

    // === VALIDATE TAGS FROM DB ===
    if (!empty($validated['tag_codes'])) {
        $placeholders = implode(',', array_fill(0, count($validated['tag_codes']), '?'));

        $rows = DB::select(
            "SELECT item_code FROM mst_detail_settings
             WHERE header_id = ? AND status = ? AND item_code IN ($placeholders)",
            array_merge(['TAG_EVENT', 'Active'], $validated['tag_codes'])
        );

        $found = array_map(fn($r) => $r->item_code, $rows);
        $diff  = array_diff($validated['tag_codes'], $found);

        if (!empty($diff)) {
            return back()->withInput()->withErrors([
                'tag_codes' => 'One or more selected tags are invalid.'
            ]);
        }
    }

    // === META ===
    $createdBy = session('employee_id');
    $now = now();

    // === GENERATE NEXT EVT ID ===
    $generateNextEventId = function() {
        $row = DB::selectOne("
            SELECT MAX(CONVERT(INT, SUBSTRING(event_id, 4, LEN(event_id)))) as maxnum
            FROM mst_events
            WHERE ISNUMERIC(SUBSTRING(event_id,4, LEN(event_id))) = 1
        ");

        $max = $row->maxnum ?? 0;
        $nextNum = intval($max) + 1;

        return 'EVT' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    };

    $baseEventId = $validated['event_id'] ?? null;

    // === TRY INSERT WITH RETRY ===
    $attempts = 0;
    $maxAttempts = 5;
    while ($attempts < $maxAttempts) {
        $attempts++;

        if ($baseEventId) {
            $eventIdToTry = $baseEventId;
            if (DB::table('mst_events')->where('event_id', $eventIdToTry)->exists()) {
                $eventIdToTry = $generateNextEventId();
            }
        } else {
            $eventIdToTry = $generateNextEventId();
        }

        DB::beginTransaction();
        try {

            // === INSERT EVENT ===
            MstEvent::create([
                'event_id'      => $eventIdToTry,
                'event_name'    => $validated['event_name'],
                'description'   => $validated['description'],
                'location'      => $validated['location'],
                'status'        => $validated['status'],
                'profile_photo' => $photoPath,   // <== FOTO MASUK DB DI SINI

                'created_by'    => $createdBy,
                'updated_by'    => $createdBy,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            // === INSERT TAGS ===
            if (!empty($validated['tag_codes'])) {
                foreach ($validated['tag_codes'] as $tagCode) {
                    $tagId = $eventIdToTry . '_' . $tagCode;

                    if (! DB::table('mst_tag_events')->where('tag_id', $tagId)->exists()) {
                        MstTagEvent::create([
                            'tag_id'     => $tagId,
                            'event_id'   => $eventIdToTry,
                            'tag_code'   => $tagCode,
                            'created_by' => $createdBy,
                            'updated_by' => $createdBy,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('employee.events.index')
                             ->with('success', 'Event created successfully!');

        } catch (\Exception $e) {

            DB::rollBack();

            if (str_contains($e->getMessage(), 'duplicate')) {
                $baseEventId = null;
                continue;
            }

            throw $e;
        }
    }

    return back()->withInput()->with('error', 'Failed to create event (please try again).');
}




    /**
     * Show form for editing event
     */
   public function edit($id)
{
    $event = DB::select(
        'SELECT event_id, event_name, description, location, status, profile_photo 
         FROM mst_events WHERE event_id = ?',
        [$id]
    );

    if (empty($event)) {
        return redirect()->route('events.index')
                       ->with('error', 'Event not found!');
    }

    $availableTags = DB::select(
        "SELECT item_code AS tag_code, item_name 
         FROM mst_detail_settings 
         WHERE header_id = ? AND status = ? 
         ORDER BY item_name",
        ['TAG_EVENT', 'Active']
    );

    $assignedTags = DB::select(
        'SELECT tag_code FROM mst_tag_events WHERE event_id = ?',
        [$id]
    );
    $assignedTagCodes = array_map(fn($t) => $t->tag_code, $assignedTags);

    return view('events.edit', [
        'event' => $event[0],
        'availableTags' => $availableTags,
        'assignedTags' => $assignedTagCodes,
    ]);
}
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
        'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
    ]);

    $validated['updated_by'] = session('employee_id');
    $validated['updated_at'] = now();

    DB::beginTransaction();
    try {

        // ================================
        //  📌 HANDLE UPLOAD PROFILE PHOTO
        // ================================
        if ($request->hasFile('profile_photo')) {

            // Delete old photo if exists
            if ($event->profile_photo && Storage::exists('public/'.$event->profile_photo)) {
                Storage::delete('public/'.$event->profile_photo);
            }

            // Save new photo
            $path = $request->file('profile_photo')->store('event_photos', 'public');
            $validated['profile_photo'] = $path;
        }

        // ================================
        //  📌 UPDATE EVENT
        // ================================
        $event->update([
            'event_name'    => $validated['event_name'],
            'description'   => $validated['description'],
            'location'      => $validated['location'],
            'status'        => $validated['status'],
            'profile_photo' => $validated['profile_photo'] ?? $event->profile_photo,
            'updated_by'    => $validated['updated_by'],
            'updated_at'    => $validated['updated_at'],
        ]);

        // ================================
        //  📌 VALIDASI TAG
        // ================================
        $submittedTags = $validated['tag_codes'] ?? [];

        if (!empty($submittedTags)) {
            $rows = DB::select(
                "SELECT item_code FROM mst_detail_settings 
                 WHERE header_id = ? AND status = ? 
                 AND item_code IN (" . implode(',', array_fill(0, count($submittedTags), '?')) . ")",
                array_merge(['TAG_EVENT', 'Active'], $submittedTags)
            );

            $found = array_map(fn($r) => $r->item_code, $rows);
            $diff = array_diff($submittedTags, $found);

            if (!empty($diff)) {
                DB::rollBack();
                return back()->withInput()->withErrors([
                    'tag_codes' => 'One or more selected tags are invalid.'
                ]);
            }
        }

        // ================================
        //  📌 SYNC TAGS
        // ================================
        $existing = DB::select(
            'SELECT tag_code FROM mst_tag_events WHERE event_id = ?',
            [$id]
        );
        $existingCodes = array_map(fn($r) => $r->tag_code, $existing);

        $toAdd = array_diff($submittedTags, $existingCodes);
        $toRemove = array_diff($existingCodes, $submittedTags);

        if (!empty($toRemove)) {
            DB::delete(
                'DELETE FROM mst_tag_events WHERE event_id = ? 
                 AND tag_code IN (' . implode(',', array_fill(0, count($toRemove), '?')) . ')',
                array_merge([$id], $toRemove)
            );
        }

        foreach ($toAdd as $tagCode) {
            $tagId = $id . '_' . $tagCode;
            MstTagEvent::create([
                'tag_id'      => $tagId,
                'event_id'    => $id,
                'tag_code'    => $tagCode,
                'created_by'  => session('employee_id'),
                'updated_by'  => session('employee_id'),
                'created_at'  => now(),
                'updated_at'  => now(),
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

        return redirect()->route('events.index')
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
            return redirect()->route('events.index')
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

        return view('events.index-tag', [
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
            return redirect()->route('events.index')
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

        return view('events.create-tag', [
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

        return redirect()->route('events.tag', $eventId)
                       ->with('success', count($tagsToAdd) . ' tag(s) added successfully!');
    }

    /**
     * Delete a specific tag from event
     */
    public function destroyTag($eventId, $tagId)
    {
        $tag = MstTagEvent::findOrFail($tagId);
        $tag->delete();

        return redirect()->route('events.tag', $eventId)
                       ->with('success', 'Tag deleted successfully!');
    }
}
