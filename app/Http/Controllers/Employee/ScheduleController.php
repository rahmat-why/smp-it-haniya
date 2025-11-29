<?php

namespace App\Http\Controllers\Employee;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
class ScheduleController extends Controller
{
  public function index()
{
    // Tidak kirim data langsung, DataTables akan ambil via AJAX
    return view('schedules.index');
}
public function getData()
{
    $schedules = DB::table('txn_schedules as s')
        ->leftJoin('mst_academic_classes as ac', 's.academic_class_id', '=', 'ac.academic_class_id')
        ->leftJoin('mst_classes as c', 'ac.class_id', '=', 'c.class_id')
        ->select(
            's.schedule_id',
            's.day',
            's.academic_class_id',
            'c.class_name',
            's.created_at'
        );

    return DataTables::of($schedules)
        ->editColumn('created_at', function ($row) {
            return $row->created_at 
                ? \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i')
                : '-';
        })
        // Action column kosong karena dropdown di Blade
        ->addColumn('action', function ($row) {
            return ''; // dropdown akan di-handle langsung di Blade
        })
        ->rawColumns(['action'])
        ->make(true);
}


public function create()
{
    $classes = DB::select('SELECT academic_class_id, class_id FROM mst_academic_classes ORDER BY academic_class_id DESC');
    $teachers = DB::select('SELECT teacher_id, first_name, last_name FROM mst_teachers WHERE status = ? ORDER BY first_name ASC', ['Active']);
    
    // FIXED → hilangkan ")" yang berlebih
    $subjects = DB::select('SELECT subject_id, subject_name FROM mst_subjects ORDER BY subject_name ASC');

    return view('schedules.create', compact('classes', 'teachers', 'subjects'));
}

public function store(Request $request)
{
    // Validasi
    $data = $request->validate([
        'academic_class_id' => 'required|string',
        'details' => 'required|array',
    ]);

    try {
        DB::beginTransaction();

        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];

        foreach ($days as $day) {

            // Abaikan hari yang tidak memiliki detail
            if (!isset($data['details'][$day]) || count($data['details'][$day]) == 0) {
                continue;
            }

            // Insert header schedule
            $scheduleId = 'SCH' . strtoupper(Str::random(8));

            DB::table('txn_schedules')->insert([
                'schedule_id' => $scheduleId,
                'day' => $day,
                'academic_class_id' => $data['academic_class_id'],
                'created_by' => session('employee_id'),
                'created_at' => now(),
            ]);

            // Insert detail untuk hari tersebut
            foreach ($data['details'][$day] as $detail) {

                DB::table('txn_schedule_details')->insert([
                    'schedule_detail_id' => 'DTL' . strtoupper(Str::random(10)),
                    'schedule_id' => $scheduleId,
                    'subject_id' => $detail['subject_id'],
                    'teacher_id' => $detail['teacher_id'],
                    'start_time' => $detail['start_time'],
                    'end_time' => $detail['end_time'],
                    'created_by' => session('employee_id'),
                    'created_at' => now(),
                ]);

            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Schedules for all days saved successfully.',
            'redirect' => route('employee.schedules.index')
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}




    public function edit($id)
    {
        $schedule = DB::table('txn_schedules')->where('schedule_id', $id)->first();
        if (! $schedule) {
            abort(404);
        }

        $details = DB::table('txn_schedule_details')->where('schedule_id', $id)->get();

        $classes = DB::select('SELECT academic_class_id, class_id FROM mst_academic_classes ORDER BY academic_class_id DESC');
        $teachers = DB::select('SELECT teacher_id, first_name, last_name FROM mst_teachers WHERE status = ? ORDER BY first_name ASC', ['Active']);
        $subjects = DB::select('SELECT subject_id, subject_name FROM mst_subjects ORDER BY subject_name ASC');

        return view('schedules.edit', compact('schedule', 'details', 'classes', 'teachers', 'subjects'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'academic_class_id' => 'required|string',
            'day' => 'required|string',
            'details' => 'required|array|min:1',
            'details.*.subject_id' => 'required|string',
            'details.*.teacher_id' => 'required|string',
            'details.*.start_time' => 'required',
            'details.*.end_time' => 'required',
        ]);

        try {
            DB::beginTransaction();

            // update header
            DB::table('txn_schedules')->where('schedule_id', $id)->update([
                'day' => $data['day'],
                'academic_class_id' => $data['academic_class_id'],
                'updated_by' => session('employee_id'),
                'updated_at' => now(),
            ]);

            // replace details: delete existing and insert new
            DB::table('txn_schedule_details')->where('schedule_id', $id)->delete();
            foreach ($data['details'] as $detail) {
                $detailId = 'DTL' . strtoupper(Str::random(10));
                DB::table('txn_schedule_details')->insert([
                    'schedule_detail_id' => $detailId,
                    'schedule_id' => $id,
                    'subject_id' => $detail['subject_id'],
                    'teacher_id' => $detail['teacher_id'] ?? null,
                    'start_time' => $detail['start_time'],
                    'end_time' => $detail['end_time'],
                    'created_by' => session('employee_id'),
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Schedule updated.', 'redirect' => route('employee.schedules.index')]);
            }

            return redirect()->route('employee.schedules.index')->with('success', 'Schedule updated.');
        } catch (\Exception $e) {
            try { DB::rollBack(); } catch (\Throwable $ex) {}
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            DB::table('txn_schedule_details')->where('schedule_id', $id)->delete();
            DB::table('txn_schedules')->where('schedule_id', $id)->delete();
            DB::commit();
            return redirect()->route('employee.schedules.index')->with('success', 'Schedule deleted.');
        } catch (\Exception $e) {
            try { DB::rollBack(); } catch (\Throwable $ex) {}
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

