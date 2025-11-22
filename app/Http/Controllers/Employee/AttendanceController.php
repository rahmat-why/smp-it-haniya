<?php

namespace App\Http\Controllers\Employee;

use App\Models\TxnAttendance;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreAttendanceRequest;

class AttendanceController extends Controller
{
    public function index()
    {
        // List attendance headers (one row per attendance header) with aggregate counts
        $attendances = DB::select(
            "SELECT TOP (1000)
                a.attendance_id,
                a.attendance_date,
                a.academic_class_id,
                c.class_id,
                c.class_name,
                a.teacher_id,
                t.first_name AS teacher_first_name,
                t.last_name AS teacher_last_name,
                (
                    SELECT COUNT(*)
                    FROM mst_student_classes sc
                    WHERE sc.academic_class_id = a.academic_class_id
                ) AS total_students,
                (
                    SELECT COUNT(*) FROM txn_attendance_details d WHERE d.attendance_id = a.attendance_id AND d.status = 'Present'
                ) AS total_present
            FROM txn_attendances a
            LEFT JOIN mst_academic_classes ac ON a.academic_class_id = ac.academic_class_id
            LEFT JOIN mst_classes c ON ac.class_id = c.class_id
            LEFT JOIN mst_teachers t ON a.teacher_id = t.teacher_id
            ORDER BY a.attendance_date DESC"
        );

        return view('attendance.index', compact('attendances'));
    }

    /**
     * Show form for bulk attendance input by class
     * Gets list of classes for selection
     */
    public function create()
    {
        $classes = DB::select('
            SELECT DISTINCT c.class_id, c.class_name, c.class_level
            FROM mst_classes c
            ORDER BY c.class_name ASC
        ');

        // Load active teachers for the create form
        $teachers = DB::select('SELECT teacher_id, first_name, last_name FROM mst_teachers WHERE status = ? ORDER BY first_name ASC', ['Active']);

        return view('attendance.create', compact('classes', 'teachers'));
    }

    /**
     * AJAX endpoint: Get students for selected class
     * Used for dynamic filtering
     */
    public function getStudentsByClass($classId)
    {
        try {
            // use transaction to avoid race conditions when generating sequential IDs
            DB::beginTransaction();
            $students = DB::select(
                'SELECT sc.student_class_id, s.student_id, s.first_name, s.last_name
                 FROM mst_student_classes sc
                 JOIN mst_students s ON sc.student_id = s.student_id
                 JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                 WHERE ac.class_id = ?
                 ORDER BY s.first_name ASC',
                [$classId]
            );

            return response()->json($students);
        } catch (\Exception $e) {
            // Log exception and return a JSON error so AJAX clients get a clean response
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store bulk attendance records
     * Handles multiple student attendance entries at once
     */
    public function store(StoreAttendanceRequest $request)
    {
        $validated = $request->validated();

        try {
            // Map existing attendance details for this date + academic class (if any)
            // Find academic_class_id for the provided class_id (pick most recent academic_class)
            $academicRow = DB::select("SELECT TOP (1) academic_class_id FROM mst_academic_classes WHERE class_id = ? ORDER BY academic_year_id DESC", [$validated['class_id']]);
            $academicClassId = !empty($academicRow) ? $academicRow[0]->academic_class_id : $validated['class_id'];

            $existingDetails = DB::select(
                'SELECT d.attendance_detail_id, d.student_id, a.attendance_id
                 FROM txn_attendance_details d
                 JOIN txn_attendances a ON d.attendance_id = a.attendance_id
                 WHERE CONVERT(date, a.attendance_date) = ? AND a.academic_class_id = ?',
                [$validated['attendance_date'], $academicClassId]
            );

            $existingMap = collect($existingDetails)->keyBy('student_id')->toArray();

            // Ensure header attendance exists (one header per class+date)
            $header = DB::table('txn_attendances')
                        ->whereRaw('CONVERT(date, attendance_date) = ? AND academic_class_id = ?', [$validated['attendance_date'], $academicClassId])
                        ->first();

            if (!$header) {
                // create header with sequential code format ATD000001
                // find current max numeric suffix for attendance_id starting with ATD
                $maxRow = DB::selectOne("SELECT MAX(CAST(RIGHT(attendance_id,6) AS INT)) AS max_no FROM txn_attendances WHERE attendance_id LIKE 'ATD%'");
                $nextNo = ($maxRow && $maxRow->max_no) ? ((int) $maxRow->max_no + 1) : 1;
                $attendanceId = 'ATD' . str_pad($nextNo, 6, '0', STR_PAD_LEFT);

                DB::table('txn_attendances')->insert([
                    'attendance_id' => $attendanceId,
                    'attendance_date' => $validated['attendance_date'],
                    'academic_class_id' => $academicClassId,
                    'teacher_id' => $validated['teacher_id'] ?? null,
                    'created_by' => session('employee_id'),
                    'created_at' => now(),
                ]);

                $header = (object) ['attendance_id' => $attendanceId];
            }

            // Process each attendance entry
            // prepare a sequential counter for attendance_detail_id to avoid UUID length issues
            $maxDetailRow = DB::selectOne("SELECT MAX(CAST(RIGHT(attendance_detail_id,6) AS INT)) AS max_no FROM txn_attendance_details WHERE attendance_detail_id LIKE 'ATDD%'");
            $nextDetailNo = ($maxDetailRow && $maxDetailRow->max_no) ? ((int) $maxDetailRow->max_no + 1) : 1;

            foreach ($validated['attendances'] as $attendance) {
                $studentClassId = $attendance['student_class_id'];

                // find student_id for the student_class_id
                $studentId = DB::table('mst_student_classes')->where('student_class_id', $studentClassId)->value('student_id');
                if (!$studentId) {
                    // skip if mapping not found
                    continue;
                }

                if (isset($existingMap[$studentId])) {
                    // update existing detail
                    DB::table('txn_attendance_details')
                        ->where('attendance_detail_id', $existingMap[$studentId]->attendance_detail_id)
                        ->update([
                            'status' => $attendance['status'],
                            'notes' => $attendance['notes'] ?? null,
                            'updated_by' => session('employee_id'),
                            'updated_at' => now(),
                        ]);
                } else {
                    // create new detail row
                    // generate detail id in format ATDD###### to keep IDs short
                    $detailId = 'ATDD' . str_pad($nextDetailNo, 6, '0', STR_PAD_LEFT);
                    $nextDetailNo++;

                    DB::table('txn_attendance_details')->insert([
                        'attendance_detail_id' => $detailId,
                        'attendance_id' => $header->attendance_id,
                        'student_id' => $studentId,
                        'status' => $attendance['status'],
                        'notes' => $attendance['notes'] ?? null,
                        'created_by' => session('employee_id'),
                        'created_at' => now(),
                    ]);
                }
            }

            DB::commit();

            if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Attendance records saved successfully!'], 201);
            }

            return redirect()->route('employee.attendances.index')
                           ->with('success', 'Attendance records saved successfully!');

        } catch (\Exception $e) {
            // rollback transaction on error
            try { DB::rollBack(); } catch (\Throwable $ex) {}

            if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
                return response()->json(['error' => 'Error saving attendance: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Error saving attendance: ' . $e->getMessage());
        }
    }

    /**
     * Show attendance records for a specific class and date
     */
    public function show($attendanceId)
    {
        // Load header
        $header = DB::table('txn_attendances')->where('attendance_id', $attendanceId)->first();

        if (!$header) {
            return redirect()->route('employee.attendances.index')
                           ->with('error', 'Attendance not found!');
        }

        // Get class and teacher info
        $classRow = DB::selectOne(
            'SELECT c.* FROM mst_academic_classes ac JOIN mst_classes c ON ac.class_id = c.class_id WHERE ac.academic_class_id = ?',
            [$header->academic_class_id]
        );

        $teacher = DB::selectOne('SELECT teacher_id, first_name, last_name FROM mst_teachers WHERE teacher_id = ?', [$header->teacher_id]);

        // Fetch detail rows with student info; use COALESCE for status column name variants
        $details = DB::select(
            "SELECT d.attendance_detail_id,
                    d.attendance_id,
                    d.student_id,
                    s.first_name,
                    s.last_name,
                    d.status AS status,
                    d.notes
             FROM txn_attendance_details d
             LEFT JOIN mst_students s ON d.student_id = s.student_id
             WHERE d.attendance_id = ?
             ORDER BY s.first_name ASC",
            [$attendanceId]
        );

        return view('attendance.show', [
            'header' => $header,
            'class' => $classRow,
            'teacher' => $teacher,
            'details' => $details,
        ]);
    }

    /**
     * Delete attendance record
     */
    public function destroy($id)
    {
        // Delete details first, then the header
        DB::table('txn_attendance_details')->where('attendance_id', $id)->delete();
        DB::table('txn_attendances')->where('attendance_id', $id)->delete();

        return redirect()->route('employee.attendances.index')
                       ->with('success', 'Attendance record deleted successfully!');
    }
}
