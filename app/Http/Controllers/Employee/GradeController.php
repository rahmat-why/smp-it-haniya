<?php

namespace App\Http\Controllers\Employee;

use App\Models\TxnGrade;
use App\Models\TxnGradeDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use Yajra\DataTables\Facades\DataTables;

class GradeController extends Controller
{
    private function getNewGradeId()
    {
        $last = DB::table('txn_grades')
            ->where('grade_id', 'LIKE', 'GRH%')
            ->orderBy('grade_id', 'desc')
            ->first();

        if ($last) {
            $num = intval(substr($last->grade_id, 3)) + 1;
            return 'GRH' . str_pad($num, 6, '0', STR_PAD_LEFT);
        }

        return 'GRH000001';
    }

      public function index()
    {
        $grades = collect(DB::select('
            SELECT TOP (1000) g.*,
                   c.class_name, sub.subject_name, t.first_name as teacher_name
            FROM txn_grades g
            JOIN mst_academic_classes ac ON ac.academic_class_id = g.academic_class_id
            JOIN mst_classes c ON ac.class_id = c.class_id
            JOIN mst_subjects sub ON g.subject_id = sub.subject_id
            JOIN mst_teachers t ON g.teacher_id = t.teacher_id
            ORDER BY g.created_at DESC
        '));

        return view('grades.index', compact('grades'));
    }

    // DataTables server-side
    public function data()
    {
        $grades = DB::table('txn_grades as g')
            ->join('mst_academic_classes as ac', 'g.academic_class_id', '=', 'ac.academic_class_id')
            ->join('mst_classes as c', 'ac.class_id', '=', 'c.class_id')
            ->join('mst_subjects as sub', 'g.subject_id', '=', 'sub.subject_id')
            ->join('mst_teachers as t', 'g.teacher_id', '=', 't.teacher_id')
            ->select(
                'g.grade_id',
                'c.class_name',
                'sub.subject_name',
                'g.grade_type',
                't.first_name as teacher_name'
            );

        return DataTables::of($grades)
            ->addColumn('type', function ($row) {
                $color = 'secondary';
                if (strtolower($row->grade_type) == 'midterm') $color = 'primary';
                elseif (strtolower($row->grade_type) == 'final') $color = 'success';
                return "<span class='badge bg-{$color}'>" . ucfirst($row->grade_type) . "</span>";
            })
            ->addColumn('action', function ($row) {
                $edit   = route('employee.grades.edit', $row->grade_id);
                $delete = route('employee.grades.destroy', $row->grade_id);
                $csrf   = csrf_token();

                $btn  = "<a href='{$edit}' class='btn btn-sm btn-warning me-1'>
                            <i class='fas fa-edit'></i>
                         </a>";
                $btn .= "<form action='{$delete}' method='POST' style='display:inline;' 
                            onsubmit=\"return confirm('Are you sure?')\">
                            <input type='hidden' name='_token' value='{$csrf}'>
                            <input type='hidden' name='_method' value='DELETE'>
                            <button type='submit' class='btn btn-sm btn-danger'>
                                <i class='fas fa-trash'></i>
                            </button>
                        </form>";
                return $btn;
            })
            ->rawColumns(['type','action'])
            ->make(true);
    }
    /**
     * Show form for bulk grade input by class
     */
        public function getStudentsByClass($classId)
    {
        $students = DB::select(
            'SELECT sc.student_class_id, s.student_id, s.first_name, s.last_name
             FROM mst_student_classes sc
             JOIN mst_students s ON sc.student_id = s.student_id
             LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
             WHERE ac.class_id = ?
             ORDER BY s.first_name ASC',
            [$classId]
        );

        return response()->json($students);
    }
   public function create()
{
    $classes = DB::select('
        SELECT DISTINCT c.class_id, c.class_name, c.class_level
        FROM mst_classes c
        ORDER BY c.class_name ASC
    ');

    $subjects = DB::select('
        SELECT subject_id, subject_name, minimum_value
        FROM mst_subjects
        ORDER BY subject_name ASC
    ');

    $teachers = DB::select(
        'SELECT teacher_id, first_name, last_name
         FROM mst_teachers
         WHERE status = ?
         ORDER BY first_name ASC',
        ['Active']
    );

    $gradeTypes = DB::select("
        SELECT detail_id, header_id, item_code, item_name, item_desc
        FROM mst_detail_settings
        WHERE header_id = 'GRADE_TYPE' AND status = 'ACTIVE'
        ORDER BY item_name ASC
    ");

    return view('grades.create', compact('classes', 'subjects', 'teachers', 'gradeTypes'));
}

    /**
     * AJAX endpoint: Get students for selected class
     */


    /**
     * Store bulk grade records
     */
   public function store(StoreGradeRequest $request)
{
    $validated = $request->validated();

    // dapatkan academic_class_id
    $academicRow = DB::select("
        SELECT TOP (1) academic_class_id
        FROM mst_academic_classes
        WHERE class_id = ?
        ORDER BY academic_year_id DESC
    ", [$validated['class_id']]);

    $academicClassId = $academicRow[0]->academic_class_id ?? $validated['class_id'];

    // 🔥 GET MINIMUM VALUE FROM mst_subjects
    $minValueRow = DB::selectOne("
        SELECT minimum_value
        FROM mst_subjects
        WHERE subject_id = ?
    ", [$validated['subject_id']]);

    $minimumValue = $minValueRow->minimum_value ?? 0;

    DB::beginTransaction();
    try {

        $gradeId = $this->getNewGradeId();

        // insert header
        DB::table('txn_grades')->updateOrInsert(
            ['grade_id' => $gradeId],
            [
                'grade_id' => $gradeId,
                'academic_class_id' => $academicClassId,
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'grade_type' => $validated['grade_type'],
                'minimum_value' => $minimumValue,     // ⬅ SAVE HERE
                'created_by' => session('employee_id'),
                'updated_by' => session('employee_id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // generate detail
        $maxDetailRow = DB::selectOne("
            SELECT MAX(CAST(RIGHT(grade_detail_id, 6) AS INT)) AS max_no
            FROM txn_grade_details
            WHERE grade_detail_id LIKE 'GRD%'
        ");

        $nextDetailNo = $maxDetailRow->max_no ? $maxDetailRow->max_no + 1 : 1;

        foreach ($validated['grades'] as $grade) {

            $studentClassId = $grade['student_class_id'];

            $studentId = DB::table('mst_student_classes')
                ->where('student_class_id', $studentClassId)
                ->value('student_id');

            if (!$studentId) continue;

            $existing = DB::table('txn_grade_details')
                        ->where('grade_id', $gradeId)
                        ->where('student_id', $studentId)
                        ->first();

            if ($existing) {

                DB::table('txn_grade_details')
                    ->where('grade_detail_id', $existing->grade_detail_id)
                    ->update([
                        'grade_value' => $grade['grade_value'] ?? null,
                        'grade_attitude' => $grade['grade_attitude'] ?? null,
                        'notes' => $grade['notes'] ?? null,
                        'updated_by' => session('employee_id'),
                        'updated_at' => now(),
                    ]);
            } else {

                $detailId = 'GRD' . str_pad($nextDetailNo, 6, '0', STR_PAD_LEFT);
                $nextDetailNo++;

                DB::table('txn_grade_details')->insert([
                    'grade_detail_id' => $detailId,
                    'grade_id' => $gradeId,
                    'student_id' => $studentId,
                    'grade_value' => $grade['grade_value'] ?? null,
                    'grade_attitude' => $grade['grade_attitude'] ?? null,
                    'notes' => $grade['notes'] ?? null,
                    'created_by' => session('employee_id'),
                    'created_at' => now(),
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Grades saved successfully',
            'redirect' => route('employee.grades.index')
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error saving grades: ' . $e->getMessage()
        ], 500);
    }
}
public function edit($gradeId)
{
    // ambil header grade
    $grade = DB::selectOne("
        SELECT g.grade_id, g.academic_class_id, g.subject_id, g.teacher_id, g.grade_type,
               ac.class_id
        FROM txn_grades g
        JOIN mst_academic_classes ac ON ac.academic_class_id = g.academic_class_id
        WHERE g.grade_id = ?
    ", [$gradeId]);

    if (!$grade) {
        abort(404, 'Grade header not found');
    }

    // ambil detail nilai masing2 siswa
    $details = DB::select("
        SELECT gd.grade_detail_id, gd.student_id, gd.grade_value, gd.grade_attitude, gd.notes,
            sc.student_class_id,
            s.first_name, s.last_name
        FROM txn_grade_details gd
        JOIN mst_students s ON gd.student_id = s.student_id
        JOIN mst_student_classes sc ON sc.student_id = s.student_id
        WHERE gd.grade_id = ?
        ORDER BY s.first_name ASC
    ", [$gradeId]);

    // dropdown data
    $classes = DB::select("SELECT class_id, class_name, class_level FROM mst_classes ORDER BY class_name ASC");
  $subjects = collect(DB::select("SELECT subject_id, subject_name, minimum_value FROM mst_subjects ORDER BY subject_name ASC"));

    $teachers = DB::select("SELECT teacher_id, first_name, last_name FROM mst_teachers WHERE status='Active' ORDER BY first_name ASC");
    $gradeTypes = DB::select("SELECT detail_id, item_code, item_name FROM mst_detail_settings WHERE header_id='GRADE_TYPE' AND status='ACTIVE' ORDER BY item_name ASC");

    return view('grades.edit', compact('grade', 'details', 'classes', 'subjects', 'teachers', 'gradeTypes'));
}


public function update(StoreGradeRequest $request, $gradeId)
{
    $validated = $request->validated();

    DB::beginTransaction();
    try {
        // update header
        DB::table('txn_grades')
            ->where('grade_id', $gradeId)
            ->update([
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'grade_type' => $validated['grade_type'],
                'updated_by' => session('employee_id'),
                'updated_at' => now(),
            ]);

        foreach ($validated['grades'] as $grade) {
            $studentClassId = $grade['student_class_id'];

            $studentId = DB::table('mst_student_classes')
                ->where('student_class_id', $studentClassId)
                ->value('student_id');

            if (!$studentId) continue;

            $existing = DB::table('txn_grade_details')
                ->where('grade_id', $gradeId)
                ->where('student_id', $studentId)
                ->first();

            if ($existing) {
                DB::table('txn_grade_details')
                    ->where('grade_detail_id', $existing->grade_detail_id)
                    ->update([
                        'grade_value' => $grade['grade_value'] ?? null,
                        'grade_attitude' => $grade['grade_attitude'] ?? null,
                        'notes' => $grade['notes'] ?? null,
                        'updated_by' => session('employee_id'),
                        'updated_at' => now(),
                    ]);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Grades updated successfully',
            'redirect' => route('employee.grades.index')
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error updating grades: ' . $e->getMessage()
        ], 500);
    }
}


    /**
     * Delete grade record
     */
    public function destroy($id)
    {
        // Delete all detail rows first
        TxnGradeDetail::where('grade_id', $id)->delete();

        // Then delete the master grade
        $grade = TxnGrade::findOrFail($id);
        $grade->delete();

        return redirect()->route('employee.grades.index')
                        ->with('success', 'Grade deleted successfully!');
    }
 

}
