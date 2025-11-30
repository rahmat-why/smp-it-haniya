<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!session('user_type') || session('user_type') !== 'teacher') {
                return redirect()->route('teacher.login');
            }
            return $next($request);
        });
    }

public function index(Request $request)
{
    // ================================
    // TOTAL STUDENTS
    // ================================
    $totalStudents = DB::table('mst_students')
        ->where('status', 'Active')
        ->count();

    // ================================
    // DETEKSI KOLOM ID KELAS DI mst_students
    // ================================
    $columns = DB::getSchemaBuilder()->getColumnListing('mst_students');

    if (in_array('academic_class_id', $columns)) {
        $classColumn = 'academic_class_id';
    } elseif (in_array('class_id', $columns)) {
        $classColumn = 'class_id';
    } elseif (in_array('kelas_id', $columns)) {
        $classColumn = 'kelas_id';
    } else {
        $classColumn = null;
    }

    // ================================
    // DAFTAR KEHADIRAN PER KELAS
    // ================================
   $studentsPerClass = collect();

if ($classColumn !== null) {
    $studentsPerClass = DB::table('mst_classes as c')
        ->join('mst_academic_classes as ac', 'ac.class_id', '=', 'c.class_id')
        ->leftJoin('mst_students as s', 's.' . $classColumn, '=', 'ac.academic_class_id')
        ->leftJoin('txn_attendances as a', 'a.academic_class_id', '=', 'ac.academic_class_id')
        ->leftJoin('txn_attendance_details as ad', 'ad.attendance_id', '=', 'a.attendance_id')
        ->select(
            'c.class_name',
            DB::raw('COUNT(DISTINCT s.student_id) as total_students'),
            DB::raw('COUNT(ad.attendance_detail_id) as total_present')
        )
        ->where('s.status', 'Active')
        ->groupBy('c.class_name')
        ->get()
        ->map(function($item){
            $item->percentage = $item->total_students > 0 
                ? round(($item->total_present / $item->total_students) * 100, 2) 
                : 0;
            return $item;
        });
}


    // ================================
    // ATTENDANCE CHART (HARIAN) – jangan dihapus
    // ================================
    $attendance_chart = DB::table('txn_attendances')
        ->select(
            DB::raw('CONVERT(VARCHAR(10), created_at, 23) as date'),
            DB::raw('COUNT(*) as total')
        )
        ->groupBy(DB::raw('CONVERT(VARCHAR(10), created_at, 23)'))
        ->orderBy(DB::raw('CONVERT(VARCHAR(10), created_at, 23)'), 'ASC')
        ->get();

    // ================================
    // ATTENDANCE PER ACADEMIC YEAR (TABEL)
    // ================================
    $studentId = auth()->user()->student_id ?? null;
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

    // ================================
    // GRADE CHART
    // ================================
    $gradeChart = DB::table('txn_grades as g')
        ->join('mst_subjects as s', 'g.subject_id', '=', 's.subject_id')
        ->select('s.subject_name', DB::raw('COUNT(*) as total'))
        ->groupBy('s.subject_name')
        ->orderBy('s.subject_name')
        ->get();

    // ================================
    // CLASSES FOR DROPDOWN
    // ================================
    $classes = DB::table('mst_classes')->get();

    // ================================
    // SCHEDULE
    // ================================
    $scheduleColumns = DB::getSchemaBuilder()->getColumnListing('txn_schedules');

    $subjectCol = in_array('subject_id', $scheduleColumns) ? 'subject_id' :
                  (in_array('mapel_id', $scheduleColumns) ? 'mapel_id' : null);

    $teacherCol = in_array('teacher_id', $scheduleColumns) ? 'teacher_id' :
                  (in_array('guru_id', $scheduleColumns) ? 'guru_id' : null);

    $classCol = in_array('academic_class_id', $scheduleColumns) ? 'academic_class_id' :
                (in_array('class_id', $scheduleColumns) ? 'class_id' :
                (in_array('kelas_id', $scheduleColumns) ? 'kelas_id' : null));

    if ($subjectCol && $teacherCol && $classCol) {
        $schedule = DB::table('txn_schedules as sc')
            ->join('mst_subjects as sub', 'sc.' . $subjectCol, '=', 'sub.subject_id')
            ->join('mst_teachers as t', 'sc.' . $teacherCol, '=', 't.teacher_id')
            ->join('mst_academic_classes as ac', 'sc.' . $classCol, '=', 'ac.academic_class_id')
            ->join('mst_classes as c', 'ac.class_id', '=', 'c.class_id')
            ->select(
                'sub.subject_name',
                't.first_name as teacher_name',
                'sc.start_time',
                'sc.end_time',
                'c.class_name'
            )
            ->get();
    } else {
        $schedule = collect();
    }

    // ================================
    // ACADEMIC YEARS
    // ================================
    $academicYears = DB::table('mst_academic_years')
        ->where('status', 'Active')
        ->orderBy('start_date', 'desc')
        ->get()
        ->map(function($item) {
            return [
                'id'   => $item->academic_year_id,
                'name' => date('Y', strtotime($item->start_date)) . '/' . date('Y', strtotime($item->end_date))
            ];
        });

    // ================================
    // EVENTS
    // ================================
    $events = DB::table('mst_events')->get();

    // ================================
    // RETURN VIEW
    // ================================
    return view('dashboard.dashboard-teacher', [
        'total_students'     => $totalStudents,
        'students_per_class' => $studentsPerClass,
        'attendance_chart'   => $attendance_chart,
        'attendance'         => $attendance,
        'grade_chart'        => $gradeChart,
        'classes'            => $classes,
        'schedule'           => $schedule,
        'events'             => $events,
        'academicYears'      => $academicYears,
    ]);
}

    

}


