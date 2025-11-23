<?php

namespace App\Http\Controllers\Employee;

use App\Models\TxnPayment;
use App\Models\TxnPaymentInstalment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Requests\StorePaymentInstallmentRequest;

class PaymentController extends Controller
{
    private function getNewPaymentId()
    {
        $last = DB::table('txn_payments')
            ->orderBy('payment_id', 'desc')
            ->first();

        if ($last) {
            $num = intval(substr($last->payment_id, 3)) + 1;
            return 'PAY' . str_pad($num, 6, '0', STR_PAD_LEFT);
        }

        return 'PAY000001';
    }

    private function getNewPaymentInstalmentId($paymentId)
    {
        // Count existing instalments for this payment
        $count = DB::table('txn_payment_instalments')
            ->where('payment_id', $paymentId)
            ->count();

        // Next instalment number
        $nextInstalmentNumber = $count + 1;

        // Combine payment_id and instalment number
        return $paymentId . '_' . $nextInstalmentNumber;
    }
    
    private function getUserId()
    {
        return session('employee_id')
            ?? session('teacher_id')
            ?? session('student_id');
    }

    public function index()
    {
        $sql = <<<'SQL'
        SELECT 
            p.payment_id,
            p.student_class_id,
            CAST(ds.item_name AS NVARCHAR(MAX)) as payment_type,
            p.payment_method,
            p.total_payment,
            p.payment_date,
            p.remaining_payment,
            p.status,
            p.notes,
            p.created_at,
            p.updated_at,
            sc.student_id,
            s.first_name,
            s.last_name,
            c.class_name,
            COUNT(pi.instalment_id) AS instalment_count,
            COALESCE(SUM(pi.total_payment),0) AS paid_amount
        FROM txn_payments p
        JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
        JOIN mst_students s ON sc.student_id = s.student_id
        LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
        LEFT JOIN mst_classes c ON ac.class_id = c.class_id
        LEFT JOIN txn_payment_instalments pi ON p.payment_id = pi.payment_id
        LEFT JOIN mst_detail_settings ds ON p.payment_type = ds.item_code AND ds.header_id = 'PAYMENT_TYPE'
        GROUP BY 
            p.payment_id, p.student_class_id, p.payment_type, p.total_payment,
            p.payment_date, p.remaining_payment, p.payment_method, p.status,
            p.notes, p.created_at, p.updated_at,
            sc.student_id, s.first_name, s.last_name, c.class_name, CAST(ds.item_name AS NVARCHAR(MAX))
        ORDER BY p.created_at DESC
        SQL;

        $payments = DB::select($sql);

        $paymentTypes = DB::table('mst_detail_settings')
            ->where('header_id', 'PAYMENT_TYPE')
            ->where('status', 'Active')
            ->pluck('item_code')
            ->toArray();

        return view('payments.index', compact('payments', 'paymentTypes'));
    }

    public function create()
    {
        $students = DB::select('
            SELECT sc.student_class_id, s.student_id, s.first_name, s.last_name, c.class_name
            FROM mst_student_classes sc
            JOIN mst_students s ON sc.student_id = s.student_id
            LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
            LEFT JOIN mst_classes c ON ac.class_id = c.class_id
            ORDER BY s.first_name ASC
        ');

        $paymentTypes = DB::table('mst_detail_settings')
            ->select('item_code', 'item_name', 'item_desc')
            ->where('header_id', 'PAYMENT_TYPE')
            ->where('status', 'ACTIVE')
            ->get();

        return view('payments.create', compact('students', 'paymentTypes'));
    }

    public function store(StorePaymentRequest $request)
    {
        try {
            DB::beginTransaction();

            // 1) Get new payment ID
            $paymentId = $this->getNewPaymentId();

            // 2) Validate form input
            $validated = $request->validated();

            // 3) Determine user ID (employee / teacher / student)
            $userId = $this->getUserId();

            // 4) Get payment type to extract total price from item_desc
            $paymentType = DB::table('mst_detail_settings')
                ->where('header_id', 'PAYMENT_TYPE')
                ->where('item_code', $validated['payment_type'])
                ->first();

            if (!$paymentType) {
                return response()->json(['success' => false, 'message' => 'Invalid payment type.']);
            }

            $totalPrice = (int) $paymentType->item_desc;
            $amountPaid = (int) $validated['total_payment'];

            // 5) Determine payment status
            $status = $amountPaid >= $totalPrice ? 'PAID' : 'PARTIALLY PAID';

            // 6) Calculate remaining balance
            $remaining = max($totalPrice - $amountPaid, 0);

            // 7) Insert payment record
            DB::table('txn_payments')->insert([
                'payment_id'        => $paymentId,
                'student_class_id'  => $validated['student_class_id'],
                'payment_type'      => $validated['payment_type'],
                'total_price'       => $totalPrice,
                'total_payment'     => $amountPaid,
                'remaining_payment' => $remaining,
                'payment_date'      => $validated['payment_date'],
                'payment_method'    => $validated['payment_method'],
                'status'            => $status,
                'notes'             => $validated['notes'] ?? null,
                'created_by'        => $userId,
                'created_at'        => now(),
            ]);

            if($validated['payment_method'] === 'INSTALLMENT') {
            // 8) Insert into instalment table (history)
            $newInstalmentId = $this->getNewPaymentInstalmentId($paymentId);
                DB::table('txn_payment_instalments')->insert([
                    'instalment_id'   => $newInstalmentId,
                    'payment_id'    => $paymentId,
                    'total_payment'   => $amountPaid,
                    'payment_date'     => $validated['payment_date'],
                    'notes'         => $validated['notes'] ?? null,
                    'created_by'    => $userId,
                    'created_at'    => now(),
                    'instalment_number' => 1
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment added successfully.',
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $th->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        // Ambil data payment
        $payment = DB::table('txn_payments as p')
            ->select(
                'p.*',
                'dt.item_name'
            )
            ->leftJoin('mst_detail_settings as dt', 'p.payment_type', '=', 'dt.item_code')
            ->where('payment_id', $id)
            ->first();

        if (!$payment) {
            abort(404);
        }

        // Ambil instalments by payment_id
        $instalments = DB::table('txn_payment_instalments')
            ->where('payment_id', $id)
            ->orderBy('instalment_number', 'ASC')
            ->get();

        // Tambahkan instalments ke object payment (menyerupai Eloquent)
        $payment->instalments = $instalments;

        return view('payments.show', compact('payment'));
    }

    public function destroy($id)
    {
        TxnPaymentInstalment::where('payment_id', $id)->delete();
        TxnPayment::where('payment_id', $id)->delete();

        return redirect()
            ->route('employee.payments.index')
            ->with('success', 'Payment and all instalments deleted successfully!');
    }

    public function createInstallment($paymentId)
    {
        $payment = DB::select('SELECT p.*, sc.student_id, s.first_name, s.last_name, c.class_name FROM txn_payments p JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id JOIN mst_students s ON sc.student_id = s.student_id LEFT JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id LEFT JOIN mst_classes c ON ac.class_id = c.class_id WHERE p.payment_id = ?', [$paymentId]);
        if (empty($payment)) {
            return redirect()->route('employee.payments.index')->with('error', 'Payment not found!');
        }
        $count = DB::select('SELECT COUNT(*) AS count FROM txn_payment_instalments WHERE payment_id = ?', [$paymentId]);
        $nextNumber = ($count[0]->count ?? 0) + 1;

        $paid = DB::select('SELECT SUM(total_payment) AS total FROM txn_payment_instalments WHERE payment_id = ?', [$paymentId]);
        $totalPaid = $paid[0]->total ?? 0;
        $remaining = $payment[0]->total_payment - $totalPaid;
        return view('payments.create-installment', ['payment' => $payment[0], 'nextNumber' => $nextNumber, 'remainingAmount' => $remaining]);
    }

    public function storeInstalment(Request $request, $paymentId)
    {
        // process instalment recording
        $request->validate([
            'payment_date'   => 'required|date',
            'total_payment'  => 'required|numeric|min:1',
            'notes'          => 'nullable|string',
        ]);

        $payment = TxnPayment::findOrFail($paymentId);

        $instalmentNumber = $payment->instalments()->count() + 1;
        $newId = $this->getNewPaymentInstalmentId($paymentId);

        $inst = TxnPaymentInstalment::create([
            'instalment_id'     => $newId,
            'payment_id'        => $paymentId,
            'instalment_number' => $instalmentNumber,
            'total_payment'     => $request->total_payment,
            'payment_date'      => $request->payment_date,
            'notes'             => $request->notes,
            'created_at'        => now(),
            'created_by'        => $this->getUserId(),
        ]);

        // Update remaining payment
        $payment->remaining_payment -= $request->total_payment;
        if ($payment->remaining_payment < 0) {
            $payment->remaining_payment = 0;
        }
        $payment->status = $payment->remaining_payment == 0 ? 'PAID' : 'UNPAID';
        $payment->save();

        return response()->json([
            'success' => true,
            'instalment' => $inst,
            'remaining' => $payment->remaining_payment,
            'paid_total' => $payment->instalments()->sum('total_payment'),
            'status' => $payment->status,
        ]);
    }

    public function destroyInstallment($paymentId, $instalmentId)
    {
        $instalment = TxnPaymentInstalment::findOrFail($instalmentId);
        $payment = TxnPayment::findOrFail($paymentId);

        $paid = DB::select('SELECT SUM(total_payment) as total FROM txn_payment_instalments WHERE payment_id = ? AND instalment_id != ?', [$paymentId, $instalmentId]);
        $newPaidTotal = $paid[0]->total ?? 0;
        $newStatus = $newPaidTotal == 0 ? 'Pending' : 'Instalment';
        $newRemaining = $payment->total_payment - $newPaidTotal;

        $instalment->delete();

        DB::update('UPDATE txn_payments SET remaining_payment = ?, status = ?, updated_by = ?, updated_at = ? WHERE payment_id = ?', [$newRemaining, $newStatus, session('employee_id'), now(), $paymentId]);

        return redirect()->route('employee.payments.show', $paymentId)->with('success', 'Instalment deleted successfully!');
    }
}
