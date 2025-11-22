<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\MstEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{

    /**
     * Ensure employee is logged in for protected routes
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!session('user_type') || session('user_type') !== 'employee') {
                return redirect()->route('employee.login');
            }
            return $next($request);
        })->only(['dashboard', 'index', 'create', 'edit', 'getData']);
    }

    /**
     * Show employee dashboard
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        // Basic dashboard metrics (counts)
        $totalStudents = DB::table('mst_students')->where('status', 'ACTIVE')->count();
        $totalTeachers = DB::table('mst_teachers')->where('status', 'ACTIVE')->count();
        $totalClasses = DB::table('mst_classes')->count();

        return view('dashboard.dashboard-employee', [
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'total_classes'  => $totalClasses,
        ]);
    }

    public function index()
    {
        return view('employees.index');
    }

    public function getData()
    {
        $employees = DB::table('mst_employees')
            ->select([
                'employee_id',
                'first_name',
                'last_name',
                'username',
                'gender',
                'birth_place',
                'birth_date',
                'address',
                'phone',
                'entry_date',
                'level',
                'status',
                'profile_photo'
            ])
            ->where('status', 'ACTIVE');

        return DataTables::of($employees)->make(true);
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        // === Upload Profile Photo ===
        $photoPath = null;

        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('employees', 'public');
        }

        // === Save Employee ===
        MstEmployee::create([
            'employee_id'   => $request->employee_id,
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'username'      => $request->username,
            'password'      => Hash::make($request->password),
            'gender'        => $request->gender,
            'birth_place'   => $request->birth_place,
            'birth_date'    => $request->birth_date,
            'address'       => $request->address,
            'phone'         => $request->phone,
            'entry_date'    => $request->entry_date ?? now(),
            'level'         => $request->level ?? 'Staff',
            'status'        => 'ACTIVE',
            'profile_photo' => $photoPath,
            'created_by'    => session('employee_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully'
        ]);
    }

    public function edit($id)
    {
        return view('employees.edit');
    }

    public function getEmployee($id)
    {
        $employee = MstEmployee::where('employee_id', $id)->first();

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        return response()->json($employee);
    }

    public function update(Request $request, $id)
    {
        $employee = DB::table('mst_employees')->where('employee_id', $id)->first();

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $data = [
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'username'     => $request->username,
            'gender'       => $request->gender,
            'birth_place'  => $request->birth_place,
            'birth_date'   => $request->birth_date,
            'address'      => $request->address,
            'phone'        => $request->phone,
            'level'        => $request->level,
            'entry_date'   => $request->entry_date,
            'updated_at'   => now(),
            'updated_by'    => session('employee_id'),
        ];

        // Update password jika diisi
        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        // Upload foto baru
        if ($request->hasFile('profile_photo')) {

            // Delete old photo
            if ($employee->profile_photo && Storage::exists('public/'.$employee->profile_photo)) {
                Storage::delete('public/'.$employee->profile_photo);
            }

            // Save new photo
            $path = $request->file('profile_photo')->store('profile_photos', 'public');

            $data['profile_photo'] = $path;
        }

        DB::table('mst_employees')->where('employee_id', $id)->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        DB::table('mst_employees')
            ->where('employee_id', $id)
            ->update([
                'status' => 'INACTIVE',
                'updated_at' => now()
            ]);

        return response()->json(['success' => true]);
    }

    public function getNewEmployeeId()
    {
        $last = DB::table('mst_employees')->orderBy('employee_id', 'desc')->first();

        if ($last) {
            $num = intval(substr($last->employee_id, 3)) + 1;
            $nextId = 'EMP' . str_pad($num, 6, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'EMP000001';
        }

        return response()->json(['employee_id' => $nextId]);
    }
}