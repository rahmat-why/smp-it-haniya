<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\MstEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
   public function dashboard(Request $request)
{
    // ==========================
    // FILTER
    // ==========================
    $year   = $request->get('year', date('Y'));
    $month  = $request->get('month', date('m'));
    $type   = $request->get('payment_type');

    $filterClass = $request->get('class_id');          // filter kelas
    $filterAcademicYear = $request->get('academic_year');  // filter tahun ajaran

    // ==========================
    // TOTAL TAGIHAN / DIBAYAR
    // ==========================
    $totalTagihan = DB::table('txn_payments')
        ->when($type, fn($q) => $q->where('payment_type', $type))
        ->sum('total_payment');

    $totalDibayar = DB::table('txn_payments')
        ->when($type, fn($q) => $q->where('payment_type', $type))
        ->where('status', 'PAID')
        ->sum('total_payment');

    // ==========================
    // DAFTAR KELAS
    // ==========================
    $classes = DB::table('mst_classes as c')
        ->join('mst_academic_classes as ac', 'ac.class_id', '=', 'c.class_id')
        ->join('mst_student_classes as sc', 'sc.academic_class_id', '=', 'ac.academic_class_id')
        ->leftJoin('txn_payments as p', 'p.student_class_id', '=', 'sc.student_class_id')
        ->select(
            'c.class_name',
            DB::raw('COUNT(sc.student_id) as total_siswa'),
            DB::raw("SUM(CASE WHEN p.status='PAID' THEN 1 ELSE 0 END) as total_sudah_bayar"),
            DB::raw("SUM(CASE WHEN p.status='UNPAID' THEN 1 ELSE 0 END) as total_belum_bayar"),
            'c.class_id'
        )
        ->groupBy('c.class_name','c.class_id')
        ->orderBy('c.class_name')
        ->get();

    // ==========================
    // JADWAL MATA PELAJARAN
    // ==========================
$query = DB::table('txn_schedules as s')
    ->join('txn_schedule_details as sd', 'sd.schedule_id', '=', 's.schedule_id')
    ->join('mst_subjects as sub', 'sub.subject_id', '=', 'sd.subject_id')
    ->join('mst_teachers as t', 't.teacher_id', '=', 'sd.teacher_id')
    ->join('mst_academic_classes as ac', 'ac.academic_class_id', '=', 's.academic_class_id')
    ->join('mst_classes as c', 'c.class_id', '=', 'ac.class_id')
    ->select(
        's.day',
        'sub.subject_name',
        't.first_name as teacher_name',
        'sd.start_time',
        'sd.end_time',
        'c.class_name'
    )
    ->orderBy('s.day')
    ->orderBy('sd.start_time');

$schedules = $query->get();




    // ==========================
    // EVENTS
    // ==========================
    $events = DB::table('mst_events')->get();

    // ==========================
    // RETURN VIEW
    // ==========================
    return view('dashboard.dashboard-employee', [
        'totalTagihan' => $totalTagihan,
        'totalDibayar' => $totalDibayar,
        'classes' => $classes,
        'academicYears' => DB::table('mst_academic_years')->get(),
        'schedules' => $schedules,
        'events' => $events,
        'filter_year' => $year,
        'filter_month' => $month,
        'filter_type' => $type,
        'filter_class' => $filterClass,
        'filter_academic_year' => $filterAcademicYear,
    ]);
}


    public function index()
    {
        return view('employees.index');
    }

    public function getData()
    {
        $employees = DB::table('mst_employees')
            ->select([
                'employee_id',
                'first_name',
                'last_name',
                'username',
                'gender',
                'birth_place',
                'birth_date',
                'address',
                'phone',
                'entry_date',
                'level',
                'status',
                'profile_photo'
            ])
            ->where('status', 'ACTIVE');

        return DataTables::of($employees)->make(true);
    }

    public function create()
    {
        return view('employees.create');
    }

   public function store(Request $request)
{
    // === VALIDATION ===
    $validated = $request->validate([
        'event_name'   => 'required|string|max:255',
        'description'  => 'required|string',
        'location'     => 'required|string|max:255',
        'status'       => 'required|in:Upcoming,Ongoing,Completed,Cancelled',

        'tag_codes'    => 'nullable|array',
        'tag_codes.*'  => 'required_with:tag_codes|string|max:50',

        'event_id'     => 'nullable|string|max:50',

        'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // <== FOTO
    ]);

    // === UPLOAD PHOTO ===
    $photoPath = null;
    if ($request->hasFile('profile_photo')) {
        $photoPath = $request->file('profile_photo')->store('events', 'public');
    }

    // === VALIDATE TAGS FROM DB ===
    if (!empty($validated['tag_codes'])) {
        $placeholders = implode(',', array_fill(0, count($validated['tag_codes']), '?'));

        $rows = DB::select(
            "SELECT item_code FROM mst_detail_settings
             WHERE header_id = ? AND status = ? AND item_code IN ($placeholders)",
            array_merge(['TAG_EVENT', 'Active'], $validated['tag_codes'])
        );

        $found = array_map(fn($r) => $r->item_code, $rows);
        $diff  = array_diff($validated['tag_codes'], $found);

        if (!empty($diff)) {
            return back()->withInput()->withErrors([
                'tag_codes' => 'One or more selected tags are invalid.'
            ]);
        }
    }

    // === META ===
    $createdBy = session('employee_id');
    $now = now();

    // === GENERATE NEXT EVT ID ===
    $generateNextEventId = function() {
        $row = DB::selectOne("
            SELECT MAX(CONVERT(INT, SUBSTRING(event_id, 4, LEN(event_id)))) as maxnum
            FROM mst_events
            WHERE ISNUMERIC(SUBSTRING(event_id,4, LEN(event_id))) = 1
        ");

        $max = $row->maxnum ?? 0;
        $nextNum = intval($max) + 1;

        return 'EVT' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    };

    $baseEventId = $validated['event_id'] ?? null;

    // === TRY INSERT WITH RETRY ===
    $attempts = 0;
    $maxAttempts = 5;
    while ($attempts < $maxAttempts) {
        $attempts++;

        if ($baseEventId) {
            $eventIdToTry = $baseEventId;
            if (DB::table('mst_events')->where('event_id', $eventIdToTry)->exists()) {
                $eventIdToTry = $generateNextEventId();
            }
        } else {
            $eventIdToTry = $generateNextEventId();
        }

        DB::beginTransaction();
        try {

            // === INSERT EVENT ===
            MstEvent::create([
                'event_id'      => $eventIdToTry,
                'event_name'    => $validated['event_name'],
                'description'   => $validated['description'],
                'location'      => $validated['location'],
                'status'        => $validated['status'],
                'profile_photo' => $photoPath,   // <== SIMPAN FOTO

                'created_by'    => $createdBy,
                'updated_by'    => $createdBy,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            // === INSERT EVENT TAGS ===
            if (!empty($validated['tag_codes'])) {
                foreach ($validated['tag_codes'] as $tagCode) {
                    $tagId = $eventIdToTry . '_' . $tagCode;

                    MstTagEvent::create([
                        'tag_id'     => $tagId,
                        'event_id'   => $eventIdToTry,
                        'tag_code'   => $tagCode,
                        'created_by' => $createdBy,
                        'updated_by' => $createdBy,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('employee.events.index')
                             ->with('success', 'Event created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            if (str_contains($e->getMessage(), 'duplicate')) {
                $baseEventId = null;
                continue;
            }

            throw $e;
        }
    }

    return back()->withInput()->with('error', 'Failed to create event (please try again).');
}

public function edit($id)
    {
        return view('employees.edit');
    }

    public function getEmployee($id)
    {
        $employee = MstEmployee::where('employee_id', $id)->first();

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        return response()->json($employee);
    }

    public function update(Request $request, $id)
    {
        $employee = DB::table('mst_employees')->where('employee_id', $id)->first();

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $data = [
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'username'     => $request->username,
            'gender'       => $request->gender,
            'birth_place'  => $request->birth_place,
            'birth_date'   => $request->birth_date,
            'address'      => $request->address,
            'phone'        => $request->phone,
            'level'        => $request->level,
            'entry_date'   => $request->entry_date,
            'updated_at'   => now(),
            'updated_by'    => session('employee_id'),
        ];

        // Update password jika diisi
        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        // Upload foto baru
        if ($request->hasFile('profile_photo')) {

            // Delete old photo
            if ($employee->profile_photo && Storage::exists('public/'.$employee->profile_photo)) {
                Storage::delete('public/'.$employee->profile_photo);
            }

            // Save new photo
            $path = $request->file('profile_photo')->store('profile_photos', 'public');

            $data['profile_photo'] = $path;
        }

        DB::table('mst_employees')->where('employee_id', $id)->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        DB::table('mst_employees')
            ->where('employee_id', $id)
            ->update([
                'status' => 'INACTIVE',
                'updated_at' => now()
            ]);

        return response()->json(['success' => true]);
    }

    public function getNewEmployeeId()
    {
        $last = DB::table('mst_employees')->orderBy('employee_id', 'desc')->first();

        if ($last) {
            $num = intval(substr($last->employee_id, 3)) + 1;
            $nextId = 'EMP' . str_pad($num, 6, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'EMP000001';
        }

        return response()->json(['employee_id' => $nextId]);
    }
}