<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Teacher Dashboard Controller
 * 
 * Handles teacher dashboard and profile management.
 */
class DashboardController extends Controller
{
    /**
     * Middleware to ensure teacher is logged in
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!session('user_type') || session('user_type') !== 'teacher') {
                return redirect()->route('teacher.login');
            }
            return $next($request);
        });
    }

    /**
     * Display teacher dashboard
     * 
     * RAW SELECT queries to fetch dashboard data
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // RAW SELECT: Get total students
        $totalStudents = DB::select(
            'SELECT COUNT(*) as count FROM mst_students WHERE status = ?',
            ['Active']
        );

        return view('dashboard.dashboard-teacher', [
            'total_students' => $totalStudents[0]->count ?? 0
        ]);
    }
}
