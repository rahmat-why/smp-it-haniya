<?php

namespace App\Http\Controllers\Employee;

use App\Models\TxnPayment;
use App\Models\TxnPaymentInstallment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Requests\StorePaymentInstallmentRequest;

class PaymentController extends Controller
{
    // Check authentication before accessing
    public function __construct()
    {
        if (session('user_type') !== 'Employee') {
            return redirect('/employee/login');
        }
    }

    /**
     * Display all payments with status tracking
     */
    public function index()
    {
        $payments = DB::select('
            SELECT TOP (1000) p.*, sc.student_id, s.first_name, s.last_name, 
                   c.class_name,
                   COUNT(pi.installment_id) as installment_count,
                   SUM(pi.total_payment) as paid_amount
            FROM txn_payments p
            JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
            JOIN mst_students s ON sc.student_id = s.student_id
            JOIN mst_classes c ON sc.class_id = c.class_id
            LEFT JOIN txn_payment_installments pi ON p.payment_id = pi.payment_id
            GROUP BY p.payment_id, p.student_class_id, p.payment_type, p.total_payment, 
                     p.remaining_payment, p.status, p.notes, p.created_at, p.updated_at, 
                     p.created_by, p.updated_by, sc.student_id, s.first_name, s.last_name, c.class_name
            ORDER BY p.created_at DESC
        ');

        return view('employee.payments.index', compact('payments'));
    }

    /**
     * Show form for creating new payment
     */
    public function create()
    {
        $students = DB::select('
            SELECT sc.student_class_id, s.student_id, s.first_name, s.last_name, c.class_name
            FROM mst_student_classes sc
            JOIN mst_students s ON sc.student_id = s.student_id
            JOIN mst_classes c ON sc.class_id = c.class_id
            WHERE sc.status = ?
            ORDER BY s.first_name ASC
        ', ['Active']);

        $paymentTypes = ['Tuition', 'Activity Fee', 'Facility Fee', 'Development Fee', 'Uniform', 'Books'];

        return view('employee.payments.create', compact('students', 'paymentTypes'));
    }

    /**
     * Store new payment
     */
    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();

        try {
            $paymentId = $validated['student_class_id'] . '_' . date('YmdHis');

            $validated['payment_id'] = $paymentId;
            $validated['remaining_payment'] = $validated['total_payment'];
            $validated['created_by'] = session('employee_id');
            $validated['updated_by'] = session('employee_id');
            $validated['created_at'] = now();
            $validated['updated_at'] = now();

            TxnPayment::create($validated);

            return redirect()->route('employee.payments.index')
                           ->with('success', 'Payment record created successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error creating payment: ' . $e->getMessage());
        }
    }

    /**
     * Show payment details and installments
     */
    public function show($id)
    {
        $payment = DB::select('
            SELECT p.*, sc.student_id, s.first_name, s.last_name, c.class_name
            FROM txn_payments p
            JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
            JOIN mst_students s ON sc.student_id = s.student_id
            JOIN mst_classes c ON sc.class_id = c.class_id
            WHERE p.payment_id = ?
        ', [$id]);

        if (empty($payment)) {
            return redirect()->route('employee.payments.index')
                           ->with('error', 'Payment not found!');
        }

        $installments = DB::select(
            'SELECT * FROM txn_payment_installments WHERE payment_id = ? ORDER BY installment_number ASC',
            [$id]
        );

        $totalPaid = collect($installments)->sum('total_payment');

        return view('employee.payments.show', [
            'payment' => $payment[0],
            'installments' => $installments,
            'totalPaid' => $totalPaid
        ]);
    }

    /**
     * Show form for editing payment
     */
    public function edit($id)
    {
        $payment = DB::select(
            'SELECT * FROM txn_payments WHERE payment_id = ?',
            [$id]
        );

        if (empty($payment)) {
            return redirect()->route('employee.payments.index')
                           ->with('error', 'Payment not found!');
        }

        $paymentTypes = ['Tuition', 'Activity Fee', 'Facility Fee', 'Development Fee', 'Uniform', 'Books'];

        return view('employee.payments.edit', [
            'payment' => $payment[0],
            'paymentTypes' => $paymentTypes
        ]);
    }

    /**
     * Update payment
     */
    public function update(UpdatePaymentRequest $request, $id)
    {
        $payment = TxnPayment::findOrFail($id);

        $validated = $request->validated();

        $validated['updated_by'] = session('employee_id');
        $validated['updated_at'] = now();

        $payment->update($validated);

        return redirect()->route('employee.payments.show', $id)
                       ->with('success', 'Payment updated successfully!');
    }

    /**
     * Delete payment and all installments
     */
    public function destroy($id)
    {
        $payment = TxnPayment::findOrFail($id);

        // Delete all installments first
        TxnPaymentInstallment::where('payment_id', $id)->delete();

        // Delete payment
        $payment->delete();

        return redirect()->route('employee.payments.index')
                       ->with('success', 'Payment and all installments deleted successfully!');
    }

    // ============ PAYMENT INSTALLMENT CRUD ============

    /**
     * Show form for recording installment payment
     */
    public function createInstallment($paymentId)
    {
        $payment = DB::select(
            'SELECT * FROM txn_payments WHERE payment_id = ?',
            [$paymentId]
        );

        if (empty($payment)) {
            return redirect()->route('employee.payments.index')
                           ->with('error', 'Payment not found!');
        }

        // Get existing installments to determine next number
        $installmentCount = DB::select(
            'SELECT COUNT(*) as count FROM txn_payment_installments WHERE payment_id = ?',
            [$paymentId]
        );

        $nextNumber = ($installmentCount[0]->count ?? 0) + 1;

        // Calculate remaining amount
        $paidAmount = DB::select(
            'SELECT SUM(total_payment) as total FROM txn_payment_installments WHERE payment_id = ?',
            [$paymentId]
        );

        $totalPaid = $paidAmount[0]->total ?? 0;
        $remainingAmount = $payment[0]->total_payment - $totalPaid;

        return view('employee.payments.create-installment', [
            'payment' => $payment[0],
            'nextNumber' => $nextNumber,
            'remainingAmount' => $remainingAmount
        ]);
    }

    /**
     * Store installment payment
     */
    public function storeInstallment(StorePaymentInstallmentRequest $request, $paymentId)
    {
        $payment = TxnPayment::findOrFail($paymentId);

        $validated = $request->validated();

        try {
            // Get current paid amount
            $paidAmount = DB::select(
                'SELECT SUM(total_payment) as total FROM txn_payment_installments WHERE payment_id = ?',
                [$paymentId]
            );

            $currentPaid = $paidAmount[0]->total ?? 0;
            $newTotal = $currentPaid + $validated['total_payment'];

            // Check if payment exceeds total
            if ($newTotal > $payment->total_payment) {
                return back()->with('error', 'Installment amount exceeds remaining payment amount!');
            }

            // Create installment
            $installmentId = $paymentId . '_' . $validated['installment_number'];

            TxnPaymentInstallment::create([
                'installment_id' => $installmentId,
                'payment_id' => $paymentId,
                'installment_number' => $validated['installment_number'],
                'total_payment' => $validated['total_payment'],
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => session('employee_id'),
                'updated_by' => session('employee_id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update payment status and remaining amount
            $newRemaining = $payment->total_payment - $newTotal;
            $newStatus = $newTotal >= $payment->total_payment ? 'Paid' : 'Partial';

            DB::update(
                'UPDATE txn_payments SET remaining_payment = ?, status = ?, updated_by = ?, updated_at = ? WHERE payment_id = ?',
                [$newRemaining, $newStatus, session('employee_id'), now(), $paymentId]
            );

            return redirect()->route('employee.payments.show', $paymentId)
                           ->with('success', 'Installment payment recorded successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error recording installment: ' . $e->getMessage());
        }
    }

    /**
     * Delete installment payment
     */
    public function destroyInstallment($paymentId, $installmentId)
    {
        $installment = TxnPaymentInstallment::findOrFail($installmentId);
        $payment = TxnPayment::findOrFail($paymentId);

        // Recalculate payment status
        $paidAmount = DB::select(
            'SELECT SUM(total_payment) as total FROM txn_payment_installments WHERE payment_id = ? AND installment_id != ?',
            [$paymentId, $installmentId]
        );

        $newPaidTotal = $paidAmount[0]->total ?? 0;
        $newStatus = $newPaidTotal == 0 ? 'Pending' : 'Partial';
        $newRemaining = $payment->total_payment - $newPaidTotal;

        // Delete installment
        $installment->delete();

        // Update payment status
        DB::update(
            'UPDATE txn_payments SET remaining_payment = ?, status = ?, updated_by = ?, updated_at = ? WHERE payment_id = ?',
            [$newRemaining, $newStatus, session('employee_id'), now(), $paymentId]
        );

        return redirect()->route('employee.payments.show', $paymentId)
                       ->with('success', 'Installment deleted successfully!');
    }
}
