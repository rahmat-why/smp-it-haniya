<?php

namespace App\Http\Controllers\Employee;

use App\Models\TxnGrade;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;

class GradeController extends Controller
{
    // Check authentication before accessing
    public function __construct()
    {
        if (session('user_type') !== 'Employee') {
            return redirect('/employee/login');
        }
    }

    /**
     * Display all grade records with filtering
     */
    public function index()
    {
        $grades = DB::select('
            SELECT TOP (1000) g.*, sc.student_id, s.first_name, s.last_name, 
                   c.class_name, sub.subject_name, t.first_name as teacher_name
            FROM txn_grades g
            JOIN mst_student_classes sc ON g.student_class_id = sc.student_class_id
            JOIN mst_students s ON sc.student_id = s.student_id
            JOIN mst_classes c ON sc.class_id = c.class_id
            JOIN mst_subjects sub ON g.subject_id = sub.subject_id
            JOIN mst_teachers t ON g.teacher_id = t.teacher_id
            ORDER BY g.created_at DESC
        ');

        return view('employee.grades.index', compact('grades'));
    }

    /**
     * Show form for bulk grade input by class
     */
    public function create()
    {
        $classes = DB::select('
            SELECT DISTINCT c.class_id, c.class_name, c.class_level
            FROM mst_classes c
            ORDER BY c.class_name ASC
        ');

        $subjects = DB::select('
            SELECT subject_id, subject_name
            FROM mst_subjects
            ORDER BY subject_name ASC
        ');

        $teachers = DB::select('
            SELECT teacher_id, first_name, last_name
            FROM mst_teachers
            WHERE status = ?
            ORDER BY first_name ASC
        ', ['Active']);

        return view('employee.grades.create', compact('classes', 'subjects', 'teachers'));
    }

    /**
     * AJAX endpoint: Get students for selected class
     */
    public function getStudentsByClass($classId)
    {
        $students = DB::select('
            SELECT sc.student_class_id, s.student_id, s.first_name, s.last_name
            FROM mst_student_classes sc
            JOIN mst_students s ON sc.student_id = s.student_id
            WHERE sc.class_id = ? AND sc.status = ?
            ORDER BY s.first_name ASC
        ', [$classId, 'Active']);

        return response()->json($students);
    }

    /**
     * Store bulk grade records
     */
    public function store(StoreGradeRequest $request)
    {
        $validated = $request->validated();

        try {
            foreach ($validated['grades'] as $grade) {
                $gradeId = $grade['student_class_id'] . '_' . $validated['subject_id'] . '_' . $validated['grade_type'];

                TxnGrade::create([
                    'grade_id' => $gradeId,
                    'student_class_id' => $grade['student_class_id'],
                    'subject_id' => $validated['subject_id'],
                    'teacher_id' => $validated['teacher_id'],
                    'grade_type' => $validated['grade_type'],
                    'grade_value' => $grade['grade_value'],
                    'grade_attitude' => $grade['grade_attitude'] ?? null,
                    'notes' => $grade['notes'] ?? null,
                    'created_by' => session('employee_id'),
                    'updated_by' => session('employee_id'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->route('employee.grades.index')
                           ->with('success', count($validated['grades']) . ' grade(s) recorded successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error saving grades: ' . $e->getMessage());
        }
    }

    /**
     * Show form for editing grades
     */
    public function edit($id)
    {
        $grade = DB::select(
            'SELECT * FROM txn_grades WHERE grade_id = ?',
            [$id]
        );

        if (empty($grade)) {
            return redirect()->route('employee.grades.index')
                           ->with('error', 'Grade not found!');
        }

        $subjects = DB::select('SELECT subject_id, subject_name FROM mst_subjects ORDER BY subject_name ASC');
        $teachers = DB::select('SELECT teacher_id, first_name, last_name FROM mst_teachers WHERE status = ? ORDER BY first_name ASC', ['Active']);

        return view('employee.grades.edit', [
            'grade' => $grade[0],
            'subjects' => $subjects,
            'teachers' => $teachers
        ]);
    }

    /**
     * Update grade record
     */
    public function update(UpdateGradeRequest $request, $id)
    {
        $grade = TxnGrade::findOrFail($id);

        $validated = $request->validated();

        $validated['updated_by'] = session('employee_id');
        $validated['updated_at'] = now();

        $grade->update($validated);

        return redirect()->route('employee.grades.index')
                       ->with('success', 'Grade updated successfully!');
    }

    /**
     * Delete grade record
     */
    public function destroy($id)
    {
        $grade = TxnGrade::findOrFail($id);
        $grade->delete();

        return redirect()->route('employee.grades.index')
                       ->with('success', 'Grade deleted successfully!');
    }
}
