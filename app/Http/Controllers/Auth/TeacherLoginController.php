<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Teacher Login Controller
 */
class TeacherLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.teacher-login');
    }

    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'npk' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $credentials = [
            'npk' => $validated['npk'],
            'password' => $validated['password'],
            'status' => 'Active',
        ];

        if (Auth::guard('teacher')->attempt($credentials)) {
            $teacher = Auth::guard('teacher')->user();

            session([
                'teacher_id' => $teacher->teacher_id,
                'name'       => $teacher->first_name . ' ' . $teacher->last_name,
                'level'      => $teacher->level ?? null,
                'user_type'  => 'teacher'
            ]);

            return redirect()->route('teacher.dashboard')
                ->with('success', 'Login successful!');
        }

        return back()->withErrors(['npk' => 'Invalid NPK or password']);
    }

    public function logout()
    {
        Auth::guard('teacher')->logout();
        session()->flush();
        return redirect()->route('teacher.login')->with('success', 'Logged out successfully!');
    }
}

