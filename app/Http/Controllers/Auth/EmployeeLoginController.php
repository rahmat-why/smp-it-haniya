<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MstEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Employee Login Controller
 * 
 * Handles employee (admin/staff) authentication.
 * Uses raw SELECT for authentication, session-based login.
 */
class EmployeeLoginController extends Controller
{
    /**
     * Show the employee login form
     * 
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.employee-login');
    }

    /**
     * Handle employee login authentication
     * 
     * Raw SELECT query to authenticate employee by username.
     * Uses Eloquent for model operations.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $credentials = [
            'username' => $validated['username'],
            'password' => $validated['password'],
            'status'   => 'Active'
        ];

        if (Auth::guard('employee')->attempt($credentials)) {
            $employee = Auth::guard('employee')->user();

            // Store minimal session info used by the app
            session([
                'employee_id' => $employee->employee_id,
                'name'        => $employee->first_name . ' ' . $employee->last_name,
                'level'       => $employee->level ?? null,
                'user_type'   => 'employee'
            ]);

            return redirect()->route('employee.dashboard')
                ->with('success', 'Login successful!');
        }

        return back()->withErrors([
            'username' => 'Invalid username or password'
        ]);
    }


    /**
     * Logout the employee
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        Auth::guard('employee')->logout();
        session()->flush();
        return redirect()->route('employee.login')
            ->with('success', 'Logged out successfully!');
    }
}
