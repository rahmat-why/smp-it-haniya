<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.student-login');
    }

    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $credentials = [
            'nis' => $validated['nis'],
            'password' => $validated['password'],
            'status' => 'Active',
        ];

        if (Auth::guard('student')->attempt($credentials)) {
            $student = Auth::guard('student')->user();

            session([
                'student_id' => $student->student_id,
                'name'       => $student->first_name . ' ' . $student->last_name,
                'user_type'  => 'student'
            ]);

            return redirect()->route('student.dashboard')->with('success', 'Login successful!');
        }

        return back()->withErrors(['nis' => 'Invalid NIS or password']);
    }

    public function logout()
    {
        Auth::guard('student')->logout();
        session()->flush();
        return redirect()->route('student.login')->with('success', 'Logged out successfully!');
    }
}

