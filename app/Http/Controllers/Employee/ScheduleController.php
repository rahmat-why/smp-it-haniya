<?php

namespace App\Http\Controllers\Employee;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = DB::select(
            "SELECT TOP (1000) s.schedule_id, s.day, s.academic_class_id, c.class_name, s.created_at
             FROM txn_schedules s
             LEFT JOIN mst_academic_classes ac ON s.academic_class_id = ac.academic_class_id
             LEFT JOIN mst_classes c ON ac.class_id = c.class_id
             ORDER BY s.created_at DESC"
        );

        return view('schedules.index', compact('schedules'));
    }

    public function create()
    {
        $classes = DB::select('SELECT academic_class_id, class_id FROM mst_academic_classes ORDER BY academic_class_id DESC');
        $teachers = DB::select('SELECT teacher_id, first_name, last_name FROM mst_teachers WHERE status = ? ORDER BY first_name ASC', ['Active']);
        $subjects = DB::select('SELECT subject_id, subject_name FROM mst_subjects ORDER BY subject_name ASC');

        return view('schedules.create', compact('classes', 'teachers', 'subjects'));
    }

    public function store(Request $request)
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

            $scheduleId = 'SCH' . strtoupper(Str::random(6));
            // Insert schedule header (do not include teacher_id column so we avoid schema mismatch)
            DB::table('txn_schedules')->insert([
                'schedule_id' => $scheduleId,
                'day' => $data['day'],
                'academic_class_id' => $data['academic_class_id'],
                'created_by' => session('employee_id'),
                'created_at' => now(),
            ]);

            foreach ($data['details'] as $detail) {
                $detailId = 'DTL' . strtoupper(Str::random(10));
                DB::table('txn_schedule_details')->insert([
                    'schedule_detail_id' => $detailId,
                    'schedule_id' => $scheduleId,
                    'subject_id' => $detail['subject_id'],
                    'teacher_id' => $detail['teacher_id'] ?? null,
                    'start_time' => $detail['start_time'],
                    'end_time' => $detail['end_time'],
                    'created_by' => session('employee_id'),
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            // Return JSON for AJAX, otherwise redirect
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Schedule created.',
                    'redirect' => route('employee.schedules.index')
                ]);
            }

            return redirect()->route('employee.schedules.index')->with('success', 'Schedule created.');
        } catch (\Exception $e) {
            try { DB::rollBack(); } catch (\Throwable $ex) {}
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
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

