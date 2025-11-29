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
    $studentId = session('student_id');

    // ==========================
    // 1. PROFILE STUDENT
    // ==========================
    $students = DB::select(
        "SELECT * FROM mst_students WHERE student_id = ?",
        [$studentId]
    );

    if (empty($students)) {
        session()->flush();
        return redirect()->route('student.login');
    }

    $student = $students[0];

    // ==========================
    // 2. Ambil student_class_id & academic_class_id
    // ==========================
    $studentClass = DB::table('mst_student_classes')
        ->where('student_id', $studentId)
        ->first();

    $student_class_id = $studentClass->student_class_id ?? null;
    $academic_class_id = $studentClass->academic_class_id ?? null;

    // ==========================
    // 3. CHART GRADE
    // ==========================
 $grades = DB::select("
    SELECT 
        CONCAT(YEAR(ay.start_date), '/', YEAR(ay.end_date)) AS academic_year,
        ay.semester,
        AVG(gd.grade_value) AS grade_value
    FROM mst_academic_years ay
    LEFT JOIN mst_academic_classes ac 
        ON ac.academic_year_id = ay.academic_year_id
    LEFT JOIN txn_grades g 
        ON g.academic_class_id = ac.academic_class_id
    LEFT JOIN txn_grade_details gd 
        ON gd.grade_id = g.grade_id AND gd.student_id = ?
    GROUP BY 
        ay.academic_year_id,
        ay.start_date,
        ay.end_date,
        ay.semester
    ORDER BY ay.start_date ASC
", [$studentId]);


    // ==========================
    // 4. CHART ATTENDANCE
    // ==========================
   $attendance = DB::select("
    SELECT 
        CONCAT(YEAR(ay.start_date), '/', YEAR(ay.end_date)) AS academic_year,
        ay.semester,
        MIN(ay.start_date) AS sort_date,
        COUNT(ad.attendance_detail_id) AS total
    FROM mst_academic_years ay
    LEFT JOIN mst_academic_classes ac
        ON ac.academic_year_id = ay.academic_year_id
    LEFT JOIN txn_attendances a
        ON a.academic_class_id = ac.academic_class_id
    LEFT JOIN txn_attendance_details ad
        ON ad.attendance_id = a.attendance_id
        AND ad.student_id = ?
    GROUP BY 
        ay.academic_year_id,
        ay.start_date,
        ay.end_date,
        ay.semester
    ORDER BY sort_date ASC
", [$studentId]);

    // ==========================
    // 5. MAPEL STUDENT (JADWAL)
    // ==========================
    $subjects = [];
    if ($academic_class_id) {
        $subjects = DB::select("
            SELECT 
                sc.day,
                sub.subject_name, 
                t.first_name, 
                t.last_name,
                sd.start_time,
                sd.end_time
            FROM txn_schedules sc
            JOIN txn_schedule_details sd 
                ON sd.schedule_id = sc.schedule_id
            JOIN mst_subjects sub 
                ON sub.subject_id = sd.subject_id
            JOIN mst_teachers t 
                ON t.teacher_id = sd.teacher_id
            WHERE sc.academic_class_id = ?
            ORDER BY 
                CASE sc.day
                    WHEN 'Monday' THEN 1
                    WHEN 'Tuesday' THEN 2
                    WHEN 'Wednesday' THEN 3
                    WHEN 'Thursday' THEN 4
                    WHEN 'Friday' THEN 5
                    WHEN 'Saturday' THEN 6
                    WHEN 'Sunday' THEN 7
                END,
                sd.start_time
        ", [$academic_class_id]);
    }

    // ==========================
    // 6. PAYMENTS (LIST)
    // ==========================
    // ==========================
// 6. PAYMENTS (LIST)
// ==========================
$payments = [];

if ($student_class_id) {
    $payments = DB::select("
        SELECT 
            p.payment_id,
            p.payment_date,
            p.payment_type,
            p.total_payment,
            p.remaining_payment,
            p.status,
            d.item_name
        FROM txn_payments p
        LEFT JOIN mst_detail_settings d 
            ON d.item_code = p.payment_type
        WHERE p.student_class_id = ?
        ORDER BY p.payment_date DESC
    ", [$student_class_id]);
}



    // ==========================
    // 7. TOTAL TAGIHAN & TOTAL DIBAYAR
    // ==========================
   // ==========================
// 7. TOTAL TAGIHAN & TOTAL DIBAYAR
// ==========================
$totalBill = 0;
$totalPaid = 0;

if ($student_class_id) {
    $total = DB::selectOne("
        SELECT 
            SUM(total_payment) AS total_bill,
            SUM(total_payment - remaining_payment) AS total_paid
        FROM txn_payments
        WHERE student_class_id = ?
    ", [$student_class_id]);

    if ($total) {
        $totalBill = $total->total_bill ?? 0;
        $totalPaid = $total->total_paid ?? 0;
    }
}

// ==========================
// 8. CHART ATTENDANCE PER ACADEMIC YEAR
// ==========================
$attendance_chart = DB::select("
    SELECT
        ay.academic_year_id,
        CONCAT(YEAR(ay.start_date), '/', YEAR(ay.end_date)) AS academic_year,
        ay.semester,
        ay.start_date AS sort_date,
        COUNT(ad.attendance_detail_id) AS total
    FROM mst_academic_years ay
    LEFT JOIN mst_academic_classes ac 
        ON ac.academic_year_id = ay.academic_year_id
    LEFT JOIN txn_attendances a 
        ON a.academic_class_id = ac.academic_class_id
    LEFT JOIN txn_attendance_details ad 
        ON ad.attendance_id = a.attendance_id
        AND ad.student_id = ?
    GROUP BY
        ay.academic_year_id,
        ay.start_date,
        ay.end_date,
        ay.semester
    ORDER BY
        ay.start_date ASC
", [$studentId]);



 
    // ==========================
    // 9. EVENTS
    // ==========================
  $events = DB::select("
    SELECT *
    FROM mst_events
    ORDER BY updated_at DESC
    OFFSET 0 ROWS
    FETCH NEXT 5 ROWS ONLY
");


 return view('dashboard.dashboard-student', compact(
    'student',
    'grades',
    'attendance',
    'subjects',
    'payments',
    'totalBill',
    'totalPaid',
    'events',
    'attendance_chart' // ← penting
));

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
