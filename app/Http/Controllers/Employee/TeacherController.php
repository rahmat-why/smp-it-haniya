<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\MstTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Teacher Management Controller
 * 
 * Handles CRUD operations for teacher management.
 * Uses raw SELECT for reading, Eloquent for create/update/delete.
 */
class TeacherController extends Controller
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
     * Display list of teachers
     * 
     * RAW SELECT query to fetch all teachers
     * 
     * @return \Illuminate\View\View
     */
   public function index()
{
    // RAW SELECT QUERY (hasil array) → ubah ke Collection
    $teachers = collect(DB::select(
        'SELECT * FROM mst_teachers WHERE status = ? ORDER BY teacher_id DESC',
        ['Active']
    ));

    return view('teachers.index', compact('teachers'));
}


    /**
     * Show the form for creating a new teacher
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('teachers.create');
    }

    /**
     * Store a newly created teacher in database
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
            'teacher_id' => 'required|string|unique:mst_teachers',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'npk' => 'required|string|max:50|unique:mst_teachers',
            'password' => 'required|string|min:6|confirmed',
            'gender' => 'nullable|in:M,F',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'entry_date' => 'nullable|date',
            'level' => 'nullable|string|max:50',
        ]);

        // Handle profile photo upload (optional)
        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('teachers', 'public');
        }

        // ELOQUENT CREATE: Create new teacher
        MstTeacher::create([
            'teacher_id' => $validated['teacher_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'npk' => $validated['npk'],
            'password' => Hash::make($validated['password']),
            'gender' => $validated['gender'] ?? null,
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'entry_date' => $validated['entry_date'] ?? now(),
            'level' => $validated['level'] ?? 'Teacher',
            'status' => 'Active',
            'created_by' => session('employee_id'),
            'profile_photo' => $photoPath
        ]);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Teacher created successfully']);
        }

        return redirect()->route('employee.teachers.index')
            ->with('success', 'Teacher created successfully!');
    }

    /**
     * Show the form for editing the specified teacher
     * 
     * RAW SELECT to fetch teacher data
     * 
     * @param string $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        // RAW SELECT QUERY: Fetch teacher by ID
        $teachers = DB::select(
            'SELECT * FROM mst_teachers WHERE teacher_id = ?',
            [$id]
        );

        if (empty($teachers)) {
            return redirect()->route('employee.teachers.index')
                ->with('error', 'Teacher not found!');
        }

        $teacher = $teachers[0];

    return view('teachers.edit', compact('teacher'));
    }

    /**
     * Update the specified teacher in database
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
            'npk' => 'required|string|max:50|unique:mst_teachers,npk,' . $id . ',teacher_id',
            'password' => 'nullable|string|min:6|confirmed',
            'gender' => 'nullable|in:M,F',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'entry_date' => 'nullable|date',
            'level' => 'nullable|string|max:50',
        ]);

        // ELOQUENT UPDATE: Update teacher record
        $teacher = MstTeacher::findOrFail($id);
        
        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'npk' => $validated['npk'],
            'gender' => $validated['gender'] ?? $teacher->gender,
            'birth_place' => $validated['birth_place'] ?? $teacher->birth_place,
            'birth_date' => $validated['birth_date'] ?? $teacher->birth_date,
            'address' => $validated['address'] ?? $teacher->address,
            'phone' => $validated['phone'] ?? $teacher->phone,
            'entry_date' => $validated['entry_date'] ?? $teacher->entry_date,
            'level' => $validated['level'] ?? $teacher->level,
            'updated_by' => session('employee_id')
        ];

        // Only update password if provided
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // Handle profile photo upload on update
        if ($request->hasFile('profile_photo')) {
            if ($teacher->profile_photo && Storage::exists('public/' . $teacher->profile_photo)) {
                Storage::delete('public/' . $teacher->profile_photo);
            }

            $path = $request->file('profile_photo')->store('teachers', 'public');
            $updateData['profile_photo'] = $path;
        }

        $teacher->update($updateData);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Teacher updated successfully']);
        }

        return redirect()->route('employee.teachers.index')
            ->with('success', 'Teacher updated successfully!');
    }

    /**
     * Delete the specified teacher from database
     * 
     * Uses Eloquent Model for DELETE operation
     * 
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // ELOQUENT DELETE: Mark teacher as inactive
        $teacher = MstTeacher::findOrFail($id);
        
        $teacher->update([
            'status' => 'Inactive',
            'updated_by' => session('employee_id')
        ]);
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Teacher deleted successfully']);
        }

        return redirect()->route('employee.teachers.index')
            ->with('success', 'Teacher deleted successfully!');
    }

    /**
     * Return JSON list for data tables / ajax
     */
    public function getData()
    {
        $teachers = DB::table('mst_teachers')
            ->select('*')
            ->where('status', 'Active');

        return DataTables::of($teachers)
            ->addColumn('full_name', function ($row) {
                return $row->first_name . ' ' . $row->last_name;
            })
            ->editColumn('entry_date', function ($row) {
                return $row->entry_date ? \Carbon\Carbon::parse($row->entry_date)->toDateString() : null;
            })
            ->make(true);
    }

    /**
     * Return a single teacher as JSON
     */
    public function getTeacher($id)
    {
        $teacher = MstTeacher::where('teacher_id', $id)->first();

        if (!$teacher) {
            return response()->json(['error' => 'Teacher not found'], 404);
        }

        return response()->json($teacher);
    }

    /**
     * Generate next teacher id
     */
    public function getNewTeacherId()
    {
        $last = DB::table('mst_teachers')->orderBy('teacher_id', 'desc')->first();

        if ($last) {
            $num = intval(preg_replace('/[^0-9]/', '', $last->teacher_id)) + 1;
            $nextId = 'TCH' . str_pad($num, 5, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'TCH00001';
        }

        return response()->json(['teacher_id' => $nextId]);
    }
}
