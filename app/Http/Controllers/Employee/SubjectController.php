<?php

namespace App\Http\Controllers\Employee;

use App\Models\MstSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Controller;

/**
 * SubjectController
 * 
 * Handles CRUD operations for subjects
 * - All READ operations (index, edit) use raw SQL SELECT queries
 * - All WRITE operations (store, update, destroy) use Eloquent Models
 */
class SubjectController extends Controller
{
    /**
     * Display list of subjects using raw SELECT query
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Check if user is employee
        if (session('user_type') !== 'employee') {
            return redirect('/employee/login');
        }

    // Raw SELECT query to get all subjects
    $subjects = DB::select('SELECT * FROM mst_subjects');

    // Use views/subjects/*.blade.php
    return view('subjects.index', ['subjects' => $subjects]);
    }

    /**
     * Show form to create new subject
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Check if user is employee
        if (session('user_type') !== 'employee') {
            return redirect('/employee/login');
        }

    return view('subjects.create');
    }

    /**
     * Store new subject using Eloquent Model
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
   public function store(Request $request)
{
    // Check if user is employee
    if (session('user_type') !== 'employee') {
        return redirect('/employee/login');
    }

    // VALIDATION
    $validated = $request->validate([
        'subject_id' => 'required|string|max:50|unique:mst_subjects,subject_id',
        'subject_name' => 'required|string|max:100',
        'subject_code' => 'required|string|max:20',
        'class_level' => 'required|string|max:10',
        'minimum_value' => 'required|numeric|min:0',
        'description' => 'nullable|string|max:500',
    ]);

    // Add created_by & updated_by
    $validated['created_by'] = session('employee_id');
    $validated['updated_by'] = session('employee_id');

    try {
        MstSubject::create($validated);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Subject created successfully']);
        }

        return redirect()->route('employee.subjects.index')
                         ->with('success', 'Subject created successfully!');
    } catch (\Exception $e) {

        if ($request->ajax()) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return back()->withInput()->with('error', 'Failed to create subject: '.$e->getMessage());
    }
}

    /**
     * Show form to edit subject (fetch data with raw SELECT)
     * 
     * @param string $id Subject ID
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        // Check if user is employee
        if (session('user_type') !== 'employee') {
            return redirect('/employee/login');
        }

        // Raw SELECT query to get subject
    $subjects = DB::select('SELECT * FROM mst_subjects WHERE subject_id = ?', [$id]);

        if (empty($subjects)) {
            return redirect()->route('employee.subjects.index')
                           ->with('error', 'Subject not found!');
        }

        $subject = $subjects[0];

    return view('subjects.edit', ['subject' => $subject]);
    }

    /**
     * Update subject using Eloquent Model
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $id Subject ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
{
    $validated = $request->validate([
        'subject_name' => 'required|string|max:100',
        'subject_code' => 'required|string|max:20',
        'class_level' => 'required|in:7,8,9',
        'minimum_value' => 'required|numeric|min:0',
        'description' => 'nullable|string|max:500',
    ]);

    $validated['updated_by'] = session('employee_id');

    try {
        $subject = MstSubject::findOrFail($id);
        $subject->update($validated);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('employee.subjects.index')
                        ->with('success', 'Subject updated successfully!');

    } catch (\Exception $e) {

        if ($request->ajax()) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }

        return back()->withInput()
                     ->with('error', 'Failed: ' . $e->getMessage());
    }
}


    /**
     * Delete subject using Eloquent Model
     * 
     * @param string $id Subject ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Check if user is employee
        if (session('user_type') !== 'Employee') {
            return redirect('/employee/login');
        }

        try {
            // Find and delete subject
            $subject = MstSubject::findOrFail($id);
            $subject->delete();
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Subject deleted successfully']);
            }

            return redirect()->route('employee.subjects.index')
                           ->with('success', 'Subject deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete subject: ' . $e->getMessage());
        }
    }

    /**
     * Return JSON list for data tables / ajax
     */
    public function getData()
    {
        $subjects = DB::table('mst_subjects')
            ->select('*');

        return DataTables::of($subjects)->make(true);
    }

    /**
     * Return a single subject as JSON
     */
    public function getSubject($id)
    {
        $subject = MstSubject::where('subject_id', $id)->first();

        if (!$subject) {
            return response()->json(['error' => 'Subject not found'], 404);
        }

        return response()->json($subject);
    }

    /**
     * Generate next subject id
     */
    public function getNewSubjectId()
    {
        $last = DB::table('mst_subjects')->orderBy('subject_id', 'desc')->first();

        if ($last) {
            $num = intval(preg_replace('/[^0-9]/', '', $last->subject_id)) + 1;
            $nextId = 'SUB' . str_pad($num, 5, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'SUB00001';
        }

        return response()->json(['subject_id' => $nextId]);
    }
}
