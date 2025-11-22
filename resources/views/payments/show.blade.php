@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <!-- Payment Details Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Payment Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Student:</strong></p>
                            <p class="text-muted">{{ $payment->student->first_name }} {{ $payment->student->last_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Class:</strong></p>
                            <p class="text-muted">{{ $payment->studentClass->class->class_name }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Payment Type:</strong></p>
                            <p class="text-muted">{{ $payment->payment_type }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong></p>
                            <p>
                                @if ($payment->status === 'Paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif ($payment->status === 'Partial')
                                    <span class="badge bg-info">Partial</span>
                                @else
                                    <span class="badge bg-danger">Pending</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <p><strong>Total Payment:</strong></p>
                            <h6 class="text-success">Rp {{ number_format($payment->total_payment, 2, ',', '.') }}</h6>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Total Paid:</strong></p>
                            <h6 class="text-info">Rp {{ number_format($totalPaid, 2, ',', '.') }}</h6>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Remaining:</strong></p>
                            <h6 class="text-warning">Rp {{ number_format($payment->remaining_payment, 2, ',', '.') }}</h6>
                        </div>
                    </div>

                    <!-- Payment Progress -->
                    <div class="mb-3">
                        <p><strong>Payment Progress:</strong></p>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: {{ ($totalPaid / $payment->total_payment) * 100 }}%"
                                 aria-valuenow="{{ $totalPaid }}" aria-valuemin="0" 
                                 aria-valuemax="{{ $payment->total_payment }}">
                                {{ round(($totalPaid / $payment->total_payment) * 100) }}%
                            </div>
                        </div>
                    </div>

                    @if ($payment->notes)
                        <div class="mb-3">
                            <p><strong>Notes:</strong></p>
                            <p class="text-muted">{{ $payment->notes }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('employee.payments.edit', $payment->payment_id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit Payment
                        </a>
                        <a href="{{ route('employee.payments.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Installments Card -->
            <div class="card">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payment Installments</h5>
                    @if ($payment->status !== 'Paid')
                        <a href="{{ route('employee.payments.create-installment', $payment->payment_id) }}" 
                           class="btn btn-light btn-sm">
                            <i class="fas fa-plus"></i> Add Installment
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if (count($installments) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Amount (Rp)</th>
                                        <th>Payment Date</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($installments as $installment)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">{{ $installment->installment_number }}</span>
                                            </td>
                                            <td>
                                                <strong>Rp {{ number_format($installment->total_payment, 2, ',', '.') }}</strong>
                                            </td>
                                            <td>
                                                {{ $installment->payment_date->format('d M Y') }}
                                            </td>
                                            <td>
                                                {{ $installment->notes ?? '-' }}
                                            </td>
                                            <td>
                                                <form action="{{ route('employee.payments.destroy-installment', ['payment' => $payment->payment_id, 'installment' => $installment->installment_id]) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Delete this installment?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info mt-3" role="alert">
                            <strong>Summary:</strong> {{ count($installments) }} installment(s) recorded | 
                            Total Paid: <strong>Rp {{ number_format($totalPaid, 2, ',', '.') }}</strong>
                        </div>
                    @else
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-info-circle"></i> No installments recorded yet.
                            @if ($payment->status !== 'Paid')
                                <a href="{{ route('employee.payments.create-installment', $payment->payment_id) }}" 
                                   class="alert-link">Add the first installment</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
