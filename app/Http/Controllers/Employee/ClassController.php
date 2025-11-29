<?php

namespace App\Http\Controllers\Employee;

use App\Models\MstClass;
use App\Models\MstTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Controller;

/**
 * ClassController
 * 
 * Handles CRUD operations for classes
 * - All READ operations (index, edit) use raw SQL SELECT queries
 * - All WRITE operations (store, update, destroy) use Eloquent Models
 */
class ClassController extends Controller
{
    /**
     * Display list of classes using raw SELECT query
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Check if user is employee
            if (session('user_type') !== 'employee') {
            return redirect('/employee/login');
        }

        // Raw SELECT query to get all classes
        $classes = DB::select('SELECT * FROM mst_classes ORDER BY class_id DESC');

            return view('classes.index', ['classes' => $classes]);
    }

    /**
     * Show form to create new class
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Check if user is employee
            if (session('user_type') !== 'employee') {
            return redirect('/employee/login');
        }

        // Homeroom teacher is now assigned via academic classes, not on mst_classes
        return view('classes.create');
    }

    /**
     * Store new class using Eloquent Model
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
   public function store(Request $request)
{
    if (session('user_type') !== 'employee') {
        return redirect('/employee/login');
    }

    // VALIDASI DENGAN PESAN LENGKAP
    $validated = $request->validate([
        'class_id' => [
            'required',
            'regex:/^[A-Za-z0-9]+$/',
            'max:50',
            'unique:mst_classes,class_id'
        ],

        'class_name' => [
            'required',
            'regex:/^[A-Za-z0-9 .,]+$/',
            'max:100',
        ],

        'class_level' => [
            'required',
            'regex:/^[A-Za-z0-9]+$/',
            'max:10',
        ],
    ], [
        // CLASS ID
        'class_id.required' => 'Class ID wajib diisi.',
        'class_id.regex'    => 'Class ID hanya boleh berisi huruf dan angka.',
        'class_id.max'      => 'Class ID maksimal 50 karakter.',
        'class_id.unique'   => 'Class ID sudah digunakan.',

        // CLASS NAME
        'class_name.required' => 'Class Name wajib diisi.',
        'class_name.regex'    => 'Class Name hanya boleh berisi huruf, angka, spasi, titik, atau koma.',
        'class_name.max'      => 'Class Name maksimal 100 karakter.',

        // CLASS LEVEL
        'class_level.required' => 'Class Level wajib diisi.',
        'class_level.regex'    => 'Class Level hanya boleh huruf atau angka.',
        'class_level.max'      => 'Class Level maksimal 10 karakter.',
    ]);

    $validated['created_by'] = session('employee_id');
    $validated['updated_by'] = session('employee_id');
    $validated['created_at'] = now();
    $validated['updated_at'] = now();

    DB::beginTransaction();
    try {
        MstClass::create($validated);
        DB::commit();

        // JIKA AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Class created successfully!'
            ]);
        }

        // JIKA NORMAL
        return redirect()
            ->route('employee.classes.index')
            ->with('success', 'Class created successfully!');

    } catch (\Exception $e) {
        DB::rollBack();

        // JIKA AJAX ERROR
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create class: ' . $e->getMessage()
            ], 500);
        }

        // NORMAL ERROR
        return back()
            ->withErrors(['error' => 'Failed to create class: ' . $e->getMessage()])
            ->withInput();
    }
}


    /**
     * Show form to edit class (fetch data with raw SELECT)
     * 
     * @param string $id Class ID
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        // Check if user is employee
            if (session('user_type') !== 'employee') {
            return redirect('/employee/login');
        }

        // Raw SELECT query to get class
        $classes = DB::select('SELECT * FROM mst_classes WHERE class_id = ?', [$id]);

        if (empty($classes)) {
            return redirect()->route('employee.classes.index')
                           ->with('error', 'Class not found!');
        }

        $class = $classes[0];

        // Homeroom teacher is now assigned via academic classes, not on mst_classes
        return view('classes.edit', ['class' => $class]);
    }

    /**
     * Update class using Eloquent Model
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $id Class ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Check if user is employee
            if (session('user_type') !== 'employee') {
            return redirect('/employee/login');
        }

        // Validate input (homeroom_teacher_id handled on mst_academic_classes now)
        $validated = $request->validate([
            'class_name' => 'required|string|max:100',
            'class_level' => 'required|string|max:10',
        ]);

        // Add updated_by
        $validated['updated_by'] = session('employee_id');

        try {
            // Find class using Eloquent
            $class = MstClass::findOrFail($id);

            // Update class
            $class->update($validated);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Class updated successfully']);
            }

            return redirect()->route('employee.classes.index')
                           ->with('success', 'Class updated successfully!');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update class: ' . $e->getMessage()], 500);
            }

            return back()->withInput()
                        ->with('error', 'Failed to update class: ' . $e->getMessage());
        }
    }

    /**
     * Delete class using Eloquent Model
     * 
     * @param string $id Class ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Check if user is employee
            if (session('user_type') !== 'employee') {
            return redirect('/employee/login');
        }

        try {
            // Find and delete class
            $class = MstClass::findOrFail($id);
            $class->delete();
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Class deleted successfully']);
            }

            return redirect()->route('employee.classes.index')
                           ->with('success', 'Class deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete class: ' . $e->getMessage());
        }
    }

    /**
     * Return JSON list for data tables / ajax
     */
    public function getData()
    {
        // Return classes; homeroom teacher is now modelled per-academic-class
        $classes = DB::table('mst_classes')
            ->select('mst_classes.*');

        return DataTables::of($classes)->make(true);
    }

    /**
     * Return a single class as JSON
     */
    public function getClass($id)
    {
        $class = MstClass::where('class_id', $id)->first();

        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        return response()->json($class);
    }

    /**
     * Generate next class id
     */
    public function getNewClassId()
    {
        $last = DB::table('mst_classes')->orderBy('class_id', 'desc')->first();

        if ($last) {
            $num = intval(preg_replace('/[^0-9]/', '', $last->class_id)) + 1;
            $nextId = 'CLS' . str_pad($num, 5, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'CLS00001';
        }

        return response()->json(['class_id' => $nextId]);
    }
}
