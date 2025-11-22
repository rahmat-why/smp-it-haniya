<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $username = $request->username;
        $password = $request->password;

        // 1. Login Employee
        $emp = DB::table('mst_employees')->where('username', $username)->first();
        if ($emp && Hash::check($password, $emp->password)) {
            session(['role' => 'EMPLOYEE', 'name' => $emp->first_name]);
            return redirect('/admin');
        }

        // 2. Login Teacher
        $teacher = DB::table('mst_teachers')->where('username', $username)->first();
        if ($teacher && Hash::check($password, $teacher->password)) {
            session(['role' => 'TEACHER', 'name' => $teacher->first_name]);
            return redirect('/admin');
        }

        // 3. Login Student
        $student = DB::table('mst_students')->where('username', $username)->first();
        if ($student && Hash::check($password, $student->password)) {
            session(['role' => 'STUDENT', 'name' => $student->first_name]);
            return redirect('/admin');
        }

        return back()->with('error', 'Invalid Login Credentials');
    }

    public function logout()
    {
        session()->flush();
        return redirect('/login');
    }
}
