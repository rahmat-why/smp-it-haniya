<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\MstAcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * AcademicYearController
 * 
 * Handles CRUD operations for academic years
 * - All READ operations (index, edit) use raw SQL SELECT queries
 * - All WRITE operations (store, update, destroy) use Eloquent Models
 */
class AcademicYearController extends Controller
{
    /**
     * Display list of academic years using raw SELECT query
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Use AJAX / DataTables for listing
        return view('academic_years.index');
    }

    /**
     * Return JSON list for data tables / ajax
     */
    public function getData()
    {
    // DB table is mst_academic_years
    $years = DB::table('mst_academic_years')->select('*');

        return DataTables::of($years)
            ->editColumn('start_date', function ($row) {
                return $row->start_date ? \Carbon\Carbon::parse($row->start_date)->toDateString() : null;
            })
            ->editColumn('end_date', function ($row) {
                return $row->end_date ? \Carbon\Carbon::parse($row->end_date)->toDateString() : null;
            })
            ->addColumn('action', function ($row) {
                $editUrl = url('academic-years/edit') . '/' . $row->academic_year_id;
                $deleteUrl = url('academic-years') . '/' . $row->academic_year_id;
                $csrf = csrf_token();
                $form = "<form action='{$deleteUrl}' method='POST' style='display:inline;' onsubmit=\"return confirm('Are you sure?');\">";
                $form .= "<input type='hidden' name='_token' value='{$csrf}'>";
                $form .= "<input type='hidden' name='_method' value='DELETE'>";
                $form .= "<button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i> Delete</button></form>";

                return "<a href='{$editUrl}' class='btn btn-sm btn-warning me-1'><i class='fas fa-edit'></i> Edit</a>" . $form;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Return a single academic year as JSON
     */
    public function getAcademicYear($id)
    {
        $year = MstAcademicYear::where('academic_year_id', $id)->first();

        if (! $year) {
            return response()->json(['error' => 'Academic Year not found'], 404);
        }

        return response()->json($year);
    }

    /**
     * Generate next academic year id (simple increment of numeric suffix)
     */
    public function getNewAcademicYearId()
    {
    $last = DB::table('mst_academic_years')->orderBy('academic_year_id', 'desc')->first();

        if ($last && preg_match('/(\d+)/', $last->academic_year_id, $m)) {
            $num = intval($m[1]) + 1;
            $nextId = 'ACY' . str_pad($num, 5, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'ACY00001';
        }

        return response()->json(['academic_year_id' => $nextId]);
    }

    /**
     * Show form to create new academic year
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('academic_years.create');
    }

    /**
     * Store new academic year using Eloquent Model
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'academic_year_id' => 'required|string|max:50|unique:mst_academic_years,academic_year_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'semester' => 'required|string|max:10',
            'status' => 'required|string|in:Active,Inactive',
        ]);

        // Add created_by
        $validated['created_by'] = session('employee_id');
        $validated['updated_by'] = session('employee_id');

        // Create academic year using Eloquent
        try {
            MstAcademicYear::create($validated);
            return redirect()->route('employee.academic_years.index')
                           ->with('success', 'Academic Year created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Failed to create academic year: ' . $e->getMessage());
        }
    }

    /**
     * Show form to edit academic year (fetch data with raw SELECT)
     * 
     * @param string $id Academic Year ID
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        // Raw SELECT query to get academic year
    $academicYears = DB::select('SELECT * FROM mst_academic_years WHERE academic_year_id = ?', [$id]);

        if (empty($academicYears)) {
            return redirect()->route('employee.academic_years.index')
                           ->with('error', 'Academic Year not found!');
        }

        $academicYear = $academicYears[0];

    return view('academic_years.edit', ['academicYear' => $academicYear]);
    }

    /**
     * Update academic year using Eloquent Model
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $id Academic Year ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Validate input
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'semester' => 'required|string|max:10',
            'status' => 'required|string|in:Active,Inactive',
        ]);

        // Add updated_by
        $validated['updated_by'] = session('employee_id');

        try {
            // Find academic year using Eloquent
            $academicYear = MstAcademicYear::findOrFail($id);

            // Update academic year
            $academicYear->update($validated);

            return redirect()->route('employee.academic_years.index')
                           ->with('success', 'Academic Year updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Failed to update academic year: ' . $e->getMessage());
        }
    }

    /**
     * Delete academic year using Eloquent Model
     * 
     * @param string $id Academic Year ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            // Find and delete academic year
            $academicYear = MstAcademicYear::findOrFail($id);
            $academicYear->delete();

            return redirect()->route('employee.academic_years.index')
                           ->with('success', 'Academic Year deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete academic year: ' . $e->getMessage());
        }
    }
}
