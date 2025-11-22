<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Student Dashboard Controller
 * 
 * Handles student dashboard and profile management.
 */
class DashboardController extends Controller
{
    /**
     * Middleware to ensure student is logged in
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!session('user_type') || session('user_type') !== 'student') {
                return redirect()->route('student.login');
            }
            return $next($request);
        });
    }

    /**
     * Display student dashboard
     * 
     * RAW SELECT query to fetch student profile
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // RAW SELECT: Get student profile data
        $students = DB::select(
            'SELECT * FROM mst_students WHERE student_id = ?',
            [session('student_id')]
        );

        if (empty($students)) {
            session()->flush();
            return redirect()->route('student.login');
        }

        $student = $students[0];

        return view('dashboard.dashboard-student', compact('student'));
    }

    /**
     * Show student profile
     * 
     * RAW SELECT to fetch student data
     * 
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        // RAW SELECT: Get student profile
        $students = DB::select(
            'SELECT * FROM mst_students WHERE student_id = ?',
            [session('student_id')]
        );

        if (empty($students)) {
            return redirect()->route('student.dashboard');
        }

        $student = $students[0];

        return view('dashboard.dashboard-student', compact('student'));
    }

    /**
     * Update student profile
     * 
     * Uses Eloquent for UPDATE operation
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'password' => 'nullable|string|min:6|confirmed',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $student = \App\Models\MstStudent::findOrFail(session('student_id'));

        $updateData = [
            'address' => $validated['address'] ?? $student->address,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $student->update($updateData);

        return redirect()->route('student.profile')
            ->with('success', 'Profile updated successfully!');
    }
}
