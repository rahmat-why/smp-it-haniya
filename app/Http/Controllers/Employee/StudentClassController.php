<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\MstStudentClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * StudentClassController
 * 
 * Handles CRUD operations for student class assignments
 * - All READ operations (index, edit) use raw SQL SELECT queries
 * - All WRITE operations (store, update, destroy) use Eloquent Models
 * - Supports multiple student selection via checkboxes
 */
class StudentClassController extends Controller
{
    /**
     * Display list of student class assignments using raw SELECT query
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // We'll load the rows via AJAX DataTables; the view will initialize the table and call getData()
        return view('student_classes.index');
    }

    /**
     * Return JSON list for data tables / ajax
     */
    public function getData()
    {
        $query = DB::table('mst_student_classes as sc')
            ->leftJoin('mst_students as s', 'sc.student_id', 's.student_id')
            ->leftJoin('mst_academic_classes as ac', 'sc.academic_class_id', 'ac.academic_class_id')
            ->leftJoin('mst_classes as c', 'ac.class_id', 'c.class_id')
            ->leftJoin('mst_academic_years as ay', 'ac.academic_year_id', 'ay.academic_year_id')
            ->select(
                'sc.student_class_id',
                'sc.student_id',
                'sc.academic_class_id',
                's.first_name',
                's.last_name',
                'c.class_name',
                'c.class_level',
                'ay.start_date',
                'ay.end_date',
                'ay.semester'
            );

        return DataTables::of($query)
            ->editColumn('start_date', function ($row) {
                return $row->start_date ? \Carbon\Carbon::parse($row->start_date)->toDateString() : null;
            })
            ->editColumn('end_date', function ($row) {
                return $row->end_date ? \Carbon\Carbon::parse($row->end_date)->toDateString() : null;
            })
            ->addColumn('student_name', function ($row) {
                return trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            })
            ->addColumn('class_display', function ($row) {
                return ($row->class_name ? $row->class_name : '') . ($row->class_level ? ' (Level ' . $row->class_level . ')' : '');
            })
            ->addColumn('academic_display', function ($row) {
                if (!empty($row->start_date) && !empty($row->end_date)) {
                    $start = $row->start_date ? \Carbon\Carbon::parse($row->start_date)->format('Y') : '';
                    $end = $row->end_date ? \Carbon\Carbon::parse($row->end_date)->format('Y') : '';
                    return $start . '-' . $end . ' (Sem ' . ($row->semester ?? '-') . ')';
                }
                return $row->academic_class_id;
            })
            ->addColumn('actions', function ($row) {
                $editUrl = url('student-classes/edit') . '/' . $row->student_class_id;
                $deleteUrl = url('student-classes') . '/' . $row->student_class_id;
                $csrf = csrf_token();
                $form = "<form action='{$deleteUrl}' method='POST' style='display:inline;' onsubmit=\"return confirm('Are you sure?');\">";
                $form .= "<input type='hidden' name='_token' value='{$csrf}'>";
                $form .= "<input type='hidden' name='_method' value='DELETE'>";
                $form .= "<button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i> Delete</button></form>";

                return "<a href='{$editUrl}' class='btn btn-sm btn-warning me-1'><i class='fas fa-edit'></i> Edit</a>" . $form;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show form to create new student class assignment
     * 
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        // Get academic_class_id from query parameter if provided
        $academicClassId = $request->query('academic_class_id');

        // Get list of active academic classes
        $academicClasses = DB::select(
            'SELECT ac.academic_class_id, ay.academic_year_id, c.class_name, c.class_level
             FROM mst_academic_classes ac
             LEFT JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
             LEFT JOIN mst_classes c ON ac.class_id = c.class_id
             ORDER BY ay.start_date DESC, c.class_name ASC'
        );

        // Get list of students using raw SELECT
        $students = DB::select('SELECT student_id, first_name, last_name FROM mst_students WHERE status = ? ORDER BY first_name ASC', ['Active']);

        return view('student_classes.create', [
            'students' => $students,
            'academicClasses' => $academicClasses,
            'selectedAcademicClassId' => $academicClassId,
        ]);
    }    /**
     * Store new student class assignments using Eloquent Model
     * Supports multiple students selection via checkboxes
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|string|max:50',
            'academic_class_id' => 'required|string|max:20',
        ]);

        try {
            // Create sequential SCL IDs for each selected student
            $last = DB::table('mst_student_classes')->orderBy('student_class_id', 'desc')->first();
            $startNum = 0;
            if ($last && preg_match('/(\d+)/', $last->student_class_id, $m)) {
                $startNum = intval($m[1]);
            }

            foreach ($validated['student_ids'] as $studentId) {
                $startNum++;
                $studentClassId = 'SCL' . str_pad($startNum, 5, '0', STR_PAD_LEFT);

                MstStudentClass::create([
                    'student_class_id' => $studentClassId,
                    'student_id' => $studentId,
                    'academic_class_id' => $validated['academic_class_id'],
                    'created_by' => session('employee_id'),
                    'updated_by' => session('employee_id'),
                ]);
            }

            return redirect()->route('employee.academic_classes.detail', $validated['academic_class_id'])
                           ->with('success', count($validated['student_ids']) . ' student(s) assigned to class successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Failed to assign students to class: ' . $e->getMessage());
        }
    }

    /**
     * Show form to edit student class assignment (fetch data with raw SELECT)
     * 
     * @param string $id Student Class ID
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        // Raw SELECT query to get student class assignment with full details
        $studentClasses = DB::select('
            SELECT sc.*, s.first_name, s.last_name, ac.academic_class_id, ay.academic_year_id, c.class_name, c.class_level
             FROM mst_student_classes sc
             LEFT JOIN mst_students s ON sc.student_id = s.student_id
             LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
             LEFT JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
             LEFT JOIN mst_classes c ON ac.class_id = c.class_id
             WHERE sc.student_class_id = ?
        ', [$id]);

        if (empty($studentClasses)) {
            return redirect()->route('employee.student_classes.index')
                           ->with('error', 'Student Class assignment not found!');
        }

        $studentClass = $studentClasses[0];

        // Get list of academic classes
        $academicClasses = DB::select(
            'SELECT ac.academic_class_id, ay.academic_year_id, c.class_name, c.class_level
             FROM mst_academic_classes ac
             LEFT JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
             LEFT JOIN mst_classes c ON ac.class_id = c.class_id
             ORDER BY ay.start_date DESC, c.class_name ASC'
        );

        return view('student_classes.edit', [
            'studentClass' => $studentClass,
            'academicClasses' => $academicClasses,
        ]);
    }    /**
     * Generate next student class id (simple increment of numeric suffix)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNewStudentClassId()
    {
        $last = DB::table('mst_student_classes')->orderBy('student_class_id', 'desc')->first();

        if ($last && preg_match('/(\d+)/', $last->student_class_id, $m)) {
            $num = intval($m[1]) + 1;
            $nextId = 'SCL' . str_pad($num, 5, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'SCL00001';
        }

        return response()->json(['student_class_id' => $nextId]);
    }

    /**
     * Update student class assignment using Eloquent Model
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $id Student Class ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Validate input
        $validated = $request->validate([
            'academic_class_id' => 'required|string|max:20',
        ]);

        // Add updated_by
        $validated['updated_by'] = session('employee_id');

        try {
            // Find student class assignment using Eloquent
            $studentClass = MstStudentClass::findOrFail($id);
            $oldAcademicClassId = $studentClass->academic_class_id;

            // Update student class assignment
            $studentClass->update($validated);

            return redirect()->route('employee.academic_classes.detail', $oldAcademicClassId)
                           ->with('success', 'Student Class assignment updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Failed to update student class assignment: ' . $e->getMessage());
        }
    }

    /**
     * Delete student class assignment using Eloquent Model
     * 
     * @param string $id Student Class ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            // Find and delete student class assignment
            $studentClass = MstStudentClass::findOrFail($id);
            $studentClass->delete();

            return redirect()->route('employee.student_classes.index')
                           ->with('success', 'Student Class assignment deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete student class assignment: ' . $e->getMessage());
        }
    }
}
