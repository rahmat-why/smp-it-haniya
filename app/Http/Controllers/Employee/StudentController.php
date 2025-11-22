<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\MstStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Student Management Controller
 * 
 * Handles CRUD operations for student management.
 * Uses raw SELECT for reading, Eloquent for create/update/delete.
 */
class StudentController extends Controller
{
    /**
     * Middleware to ensure employee is logged in
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!session('user_type') || session('user_type') !== 'employee') {
                return redirect()->route('employee.login');
            }
            return $next($request);
        });
    }

    /**
     * Display list of students
     * 
     * RAW SELECT query to fetch all students
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // RAW SELECT QUERY: Fetch all students
        $students = DB::select(
            'SELECT * FROM mst_students WHERE status = ? ORDER BY student_id DESC',
            ['Active']
        );

        return view('students.index', compact('students'));
    }

    /**
     * Show the form for creating a new student
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('students.create');
    }

    /**
     * Store a newly created student in database
     * 
     * Uses Eloquent Model for CREATE operation
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'student_id' => 'required|string|unique:mst_students',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'nis' => 'required|string|max:50|unique:mst_students',
            'password' => 'required|string|min:6|confirmed',
            'gender' => 'nullable|in:M,F',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:20',
            'mother_phone' => 'nullable|string|max:20',
            'father_job' => 'nullable|string|max:100',
            'mother_job' => 'nullable|string|max:100',
            'entry_date' => 'nullable|date',
        ]);

        // Handle profile photo upload
        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('students', 'public');
        }

        // ELOQUENT CREATE: Create new student
        MstStudent::create([
            'student_id' => $validated['student_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'nis' => $validated['nis'],
            'password' => Hash::make($validated['password']),
            'gender' => $validated['gender'] ?? null,
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'mother_name' => $validated['mother_name'] ?? null,
            'father_phone' => $validated['father_phone'] ?? null,
            'mother_phone' => $validated['mother_phone'] ?? null,
            'father_job' => $validated['father_job'] ?? null,
            'mother_job' => $validated['mother_job'] ?? null,
            'profile_photo' => $photoPath,
            'entry_date' => $validated['entry_date'] ?? now(),
            'status' => 'Active',
            'created_by' => session('employee_id')
        ]);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Student created successfully']);
        }

        return redirect()->route('employee.students.index')
            ->with('success', 'Student created successfully!');
    }

    /**
     * Show the form for editing the specified student
     * 
     * RAW SELECT to fetch student data
     * 
     * @param string $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        // RAW SELECT QUERY: Fetch student by ID
        $students = DB::select(
            'SELECT * FROM mst_students WHERE student_id = ?',
            [$id]
        );

        if (empty($students)) {
            return redirect()->route('employee.students.index')
                ->with('error', 'Student not found!');
        }

        $student = $students[0];

    return view('students.edit', compact('student'));
    }

    /**
     * Update the specified student in database
     * 
     * Uses Eloquent Model for UPDATE operation
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Validate input
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'nis' => 'required|string|max:50|unique:mst_students,nis,' . $id . ',student_id',
            'password' => 'nullable|string|min:6|confirmed',
            'gender' => 'nullable|in:M,F',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:20',
            'mother_phone' => 'nullable|string|max:20',
            'father_job' => 'nullable|string|max:100',
            'mother_job' => 'nullable|string|max:100',
            'entry_date' => 'nullable|date',
        ]);

        // ELOQUENT UPDATE: Update student record
        $student = MstStudent::findOrFail($id);
        
        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'nis' => $validated['nis'],
            'gender' => $validated['gender'] ?? $student->gender,
            'birth_place' => $validated['birth_place'] ?? $student->birth_place,
            'birth_date' => $validated['birth_date'] ?? $student->birth_date,
            'address' => $validated['address'] ?? $student->address,
            'father_name' => $validated['father_name'] ?? $student->father_name,
            'mother_name' => $validated['mother_name'] ?? $student->mother_name,
            'father_phone' => $validated['father_phone'] ?? $student->father_phone,
            'mother_phone' => $validated['mother_phone'] ?? $student->mother_phone,
            'father_job' => $validated['father_job'] ?? $student->father_job,
            'mother_job' => $validated['mother_job'] ?? $student->mother_job,
            'entry_date' => $validated['entry_date'] ?? $student->entry_date,
            'updated_by' => session('employee_id')
        ];

        // Only update password if provided
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // Handle profile photo upload on update
        if ($request->hasFile('profile_photo')) {
            // delete old photo if exists
            if ($student->profile_photo && Storage::exists('public/' . $student->profile_photo)) {
                Storage::delete('public/' . $student->profile_photo);
            }

            $path = $request->file('profile_photo')->store('students', 'public');
            $updateData['profile_photo'] = $path;
        }

        $student->update($updateData);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Student updated successfully']);
        }

        return redirect()->route('employee.students.index')
            ->with('success', 'Student updated successfully!');
    }

    /**
     * Delete the specified student from database
     * 
     * Uses Eloquent Model for DELETE operation
     * 
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // ELOQUENT DELETE: Mark student as inactive
        $student = MstStudent::findOrFail($id);
        
        $student->update([
            'status' => 'Inactive',
            'updated_by' => session('employee_id')
        ]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Student deleted successfully']);
        }

        return redirect()->route('employee.students.index')
            ->with('success', 'Student deleted successfully!');
    }

    /**
     * Return JSON list for data tables / ajax
     */
    public function getData()
    {
        $students = DB::table('mst_students')
            ->select('*')
            ->where('status', 'Active');

        return DataTables::of($students)
            ->addColumn('full_name', function ($row) {
                return $row->first_name . ' ' . $row->last_name;
            })
            ->editColumn('entry_date', function ($row) {
                return $row->entry_date ? \Carbon\Carbon::parse($row->entry_date)->toDateString() : null;
            })
            ->make(true);
    }

    /**
     * Return a single student as JSON
     */
    public function getStudent($id)
    {
        $student = MstStudent::where('student_id', $id)->first();

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        return response()->json($student);
    }

    /**
     * Generate next student id
     */
    public function getNewStudentId()
    {
        $last = DB::table('mst_students')->orderBy('student_id', 'desc')->first();

        if ($last) {
            $num = intval(preg_replace('/[^0-9]/', '', $last->student_id)) + 1;
            $nextId = 'STD' . str_pad($num, 5, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'STD00001';
        }

        return response()->json(['student_id' => $nextId]);
    }
}
