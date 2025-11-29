<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\MstAcademicClass;
use App\Models\MstAcademicYear;
use App\Models\MstClass;
use App\Models\MstEmployee;
use App\Models\MstStudentClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * AcademicClassController
 * 
 * Handles CRUD operations for academic classes (class assignments in specific academic year with homeroom teacher)
 */
class AcademicClassController extends Controller
{
    /**
     * Display list of academic classes using AJAX DataTables
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('academic_classes.index');
    }

    /**
     * Return JSON list for data tables / ajax
     */
    public function getData()
    {
        $query = DB::table('mst_academic_classes as ac')
            ->leftJoin('mst_academic_years as ay', 'ac.academic_year_id', 'ay.academic_year_id')
            ->leftJoin('mst_classes as c', 'ac.class_id', 'c.class_id')
            ->leftJoin('mst_employees as e', 'ac.homeroom_teacher_id', 'e.employee_id')
            ->select(
                'ac.academic_class_id',
                'ac.academic_year_id',
                'ac.class_id',
                'ac.homeroom_teacher_id',
                'ay.start_date',
                'ay.end_date',
                'ay.semester',
                'c.class_name',
                'c.class_level',
                'e.first_name as teacher_first_name',
                'e.last_name as teacher_last_name'
            );

        return DataTables::of($query)
            ->addColumn('academic_year_display', function ($row) {
                if (!empty($row->start_date) && !empty($row->end_date)) {
                    $start = \Carbon\Carbon::parse($row->start_date)->format('Y');
                    $end = \Carbon\Carbon::parse($row->end_date)->format('Y');
                    return $start . '-' . $end . ' (Sem ' . ($row->semester ?? '-') . ')';
                }
                return $row->academic_year_id;
            })
            ->addColumn('class_display', function ($row) {
                return ($row->class_name ? $row->class_name : $row->class_id) . ($row->class_level ? ' (Level ' . $row->class_level . ')' : '');
            })
            ->addColumn('teacher_name', function ($row) {
                return trim(($row->teacher_first_name ?? '') . ' ' . ($row->teacher_last_name ?? '')) ?: '-';
            })
            ->addColumn('actions', function ($row) {
                // Return the academic_class_id so the client-side can build the action buttons.
                // We keep this column but do not render HTML here to allow the Blade view
                // to build buttons (preventing controller-side HTML generation).
                return $row->academic_class_id;
            })
            ->make(true);
    }

    /**
     * Show form to create new academic class
     */
    public function create()
    {
    $academicYears = DB::select('SELECT academic_year_id, start_date, end_date, semester FROM mst_academic_years WHERE status = ? ORDER BY start_date DESC', ['Active']);
        $classes = DB::select('SELECT class_id, class_name, class_level FROM mst_classes ORDER BY class_name ASC');
        $teachers = DB::select('SELECT employee_id, first_name, last_name FROM mst_employees WHERE status = ? ORDER BY first_name ASC', ['Active']);

        // Get active students to allow assigning students to this academic class on creation
        $students = DB::select('SELECT student_id, first_name, last_name FROM mst_students WHERE status = ? ORDER BY first_name ASC', ['Active']);

        return view('academic_classes.create', [
            'academicYears' => $academicYears,
            'classes' => $classes,
            'teachers' => $teachers,
            'students' => $students,
        ]);
    }

    /**
     * Store new academic class
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_class_id' => 'required|string|max:20|unique:mst_academic_classes,academic_class_id',
            'academic_year_id' => 'required|string|max:20|exists:mst_academic_years,academic_year_id',
            'class_id' => 'required|string|max:20|exists:mst_classes,class_id',
            'homeroom_teacher_id' => 'nullable|string|max:20|exists:mst_employees,employee_id',
        ]);

        // If student_ids[] are submitted on create, persist them as mst_student_classes rows
        $submittedStudentIds = $request->input('student_ids', []);

        try {
            DB::beginTransaction();

            // create academic class
            $academicClass = MstAcademicClass::create($validated);

            // handle student assignments if any
            if (is_array($submittedStudentIds) && !empty($submittedStudentIds)) {
                // Prepare SCL id generator
                $last = DB::table('mst_student_classes')->orderBy('student_class_id', 'desc')->first();
                $startNum = 0;
                if ($last && preg_match('/(\d+)/', $last->student_class_id, $m)) {
                    $startNum = intval($m[1]);
                }

                foreach ($submittedStudentIds as $studentId) {
                    // skip if student already assigned in this academic year
                    $already = DB::select(
                        'SELECT sc.student_id
                         FROM mst_student_classes sc
                         INNER JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                         WHERE ac.academic_year_id = ? AND sc.student_id = ?'
                        , [$validated['academic_year_id'], $studentId]
                    );

                    if (!empty($already)) {
                        continue;
                    }

                    $startNum++;
                    $studentClassId = 'SCL' . str_pad($startNum, 5, '0', STR_PAD_LEFT);

                    MstStudentClass::create([
                        'student_class_id' => $studentClassId,
                        'student_id' => $studentId,
                        'academic_class_id' => $academicClass->academic_class_id,
                        'created_by' => session('employee_id'),
                        'updated_by' => session('employee_id'),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('employee.academic_classes.edit', $academicClass->academic_class_id)
                           ->with('success', 'Academic Class created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                        ->with('error', 'Failed to create academic class: ' . $e->getMessage());
        }
    }
 public function getNewAcademicClassId()
    {
        $last = DB::table('mst_academic_classes')->orderBy('academic_class_id', 'desc')->first();

        if ($last && preg_match('/(\d+)/', $last->academic_class_id, $m)) {
            $num = intval($m[1]) + 1;
            $nextId = 'ACC' . str_pad($num, 5, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'ACC00001';
        }

        return response()->json(['academic_class_id' => $nextId]);
    }
    /**
     * Show form to edit academic class
     */
    public function edit($id)
    {
        $academicClass = MstAcademicClass::findOrFail($id);
    $academicYears = DB::select('SELECT academic_year_id, start_date, end_date, semester FROM mst_academic_years WHERE status = ? ORDER BY start_date DESC', ['Active']);
        $classes = DB::select('SELECT class_id, class_name, class_level FROM mst_classes ORDER BY class_name ASC');
        $teachers = DB::select('SELECT employee_id, first_name, last_name FROM mst_employees WHERE status = ? ORDER BY first_name ASC', ['Active']);

        // fetch active students and assignment info for this academic year
        $students = DB::select('SELECT student_id, first_name, last_name FROM mst_students WHERE status = ? ORDER BY first_name ASC', ['Active']);

        // students assigned in this academic year (student_id => academic_class_id)
        $assignedRows = DB::select(
            'SELECT sc.student_id, sc.academic_class_id
             FROM mst_student_classes sc
             INNER JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
             WHERE ac.academic_year_id = ?',
            [$academicClass->academic_year_id]
        );

        $assignedMap = [];
        foreach ($assignedRows as $r) {
            $assignedMap[$r->student_id] = $r->academic_class_id;
        }

        // students assigned specifically to this academic class
        $assignedThisRows = DB::select('SELECT student_id FROM mst_student_classes WHERE academic_class_id = ?', [$id]);
        $assignedThis = array_map(function($r){ return $r->student_id; }, $assignedThisRows);

        return view('academic_classes.edit', [
            'academicClass' => $academicClass,
            'academicYears' => $academicYears,
            'classes' => $classes,
            'teachers' => $teachers,
            'students' => $students,
            'assignedMap' => $assignedMap,
            'assignedThis' => $assignedThis,
        ]);
    }

    /**
     * Update academic class
     */
    public function update(Request $request, $id)
    {
        $academicClass = MstAcademicClass::findOrFail($id);

        $validated = $request->validate([
            'academic_year_id' => 'required|string|max:20|exists:mst_academic_years,academic_year_id',
            'class_id' => 'required|string|max:20|exists:mst_classes,class_id',
            'homeroom_teacher_id' => 'nullable|string|max:20|exists:mst_employees,employee_id',
        ]);

        try {
            DB::beginTransaction();

            // Update academic class basic fields
            $academicClass->update($validated);

            // Process assigned students if present (student_ids[] submitted)
            $submittedStudentIds = $request->input('student_ids', []);
            if (!is_array($submittedStudentIds)) {
                $submittedStudentIds = [];
            }

            // Current assignments for this academic class
            $existing = DB::table('mst_student_classes')
                ->where('academic_class_id', $id)
                ->pluck('student_id')
                ->toArray();

            $toAdd = array_values(array_diff($submittedStudentIds, $existing));
            $toRemove = array_values(array_diff($existing, $submittedStudentIds));

            // Remove unchecked assignments
            if (!empty($toRemove)) {
                DB::table('mst_student_classes')
                    ->where('academic_class_id', $id)
                    ->whereIn('student_id', $toRemove)
                    ->delete();
            }

            // Add newly checked assignments (skip students already assigned in same academic year)
            if (!empty($toAdd)) {
                // Prepare SCL id generator
                $last = DB::table('mst_student_classes')->orderBy('student_class_id', 'desc')->first();
                $startNum = 0;
                if ($last && preg_match('/(\d+)/', $last->student_class_id, $m)) {
                    $startNum = intval($m[1]);
                }

                foreach ($toAdd as $studentId) {
                    // Check if student is already assigned in this academic year
                    $already = DB::select(
                        'SELECT sc.student_id
                         FROM mst_student_classes sc
                         INNER JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                         WHERE ac.academic_year_id = ? AND sc.student_id = ?'
                        , [$academicClass->academic_year_id, $studentId]
                    );

                    if (!empty($already)) {
                        // skip this student (they're assigned to some class in same year)
                        continue;
                    }

                    $startNum++;
                    $studentClassId = 'SCL' . str_pad($startNum, 5, '0', STR_PAD_LEFT);

                    MstStudentClass::create([
                        'student_class_id' => $studentClassId,
                        'student_id' => $studentId,
                        'academic_class_id' => $id,
                        'created_by' => session('employee_id'),
                        'updated_by' => session('employee_id'),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('employee.academic_classes.index')
                           ->with('success', 'Academic Class updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                        ->with('error', 'Failed to update academic class: ' . $e->getMessage());
        }
    }

    /**
     * Delete academic class
     */
   public function destroy($id)
{
    try {
        DB::beginTransaction();

        // Hapus dulu data relasi di tabel anak
        DB::table('mst_student_classes')
            ->where('academic_class_id', $id)
            ->delete();

        // Baru hapus data master
        $academicClass = MstAcademicClass::findOrFail($id);
        $academicClass->delete();

        DB::commit();

        return redirect()->route('employee.academic_classes.index')
                        ->with('success', 'Academic Class deleted successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Failed to delete academic class: ' . $e->getMessage());
    }
}

    /**
     * Generate next academic class id
     */
   

    /**
     * API: return list of active academic years (JSON)
     */
    public function apiYears()
    {
        $years = DB::select('SELECT academic_year_id, start_date, end_date, semester FROM mst_academic_years WHERE status = ? ORDER BY start_date DESC', ['Active']);
        return response()->json($years);
    }

    /**
     * API: return list of academic classes for a given academic year
     */
    public function apiClassesByYear($academicYearId)
    {
        $classes = DB::select(
            'SELECT ac.academic_class_id, ac.class_id, c.class_name, c.class_level
             FROM mst_academic_classes ac
             LEFT JOIN mst_classes c ON ac.class_id = c.class_id
             WHERE ac.academic_year_id = ?
             ORDER BY c.class_name ASC',
            [$academicYearId]
        );

        return response()->json($classes);
    }

    /**
     * API: return list of student_ids already assigned in given academic year
     */
    public function apiAssignedStudentsByYear($academicYearId)
    {
        $assigned = DB::select(
            'SELECT DISTINCT sc.student_id
             FROM mst_student_classes sc
             INNER JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
             WHERE ac.academic_year_id = ?',
            [$academicYearId]
        );

        // return array of student_id strings
        $ids = array_map(function($r){ return $r->student_id; }, $assigned);
        return response()->json($ids);
    }
}
