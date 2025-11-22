@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <!-- Payment Context Card -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Payment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Student:</strong></p>
                            <p class="text-muted">{{ $payment->student->first_name }} {{ $payment->student->last_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Payment Type:</strong></p>
                            <p class="text-muted">{{ $payment->payment_type }}</p>
                        </div>
                    </div>

                    <div class="row">
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
                            <h6 class="text-warning">Rp {{ number_format($remainingAmount, 2, ',', '.') }}</h6>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Installment Form Card -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Record Payment Installment</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Validation Errors</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('employee.payments.store-installment', $payment->payment_id) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="installment_number" class="form-label">Installment Number</label>
                            <input type="number" class="form-control" id="installment_number" 
                                   value="{{ $nextInstallmentNumber }}" disabled>
                            <input type="hidden" name="installment_number" value="{{ $nextInstallmentNumber }}">
                            <small class="text-muted">Auto-calculated based on existing installments</small>
                        </div>

                        <div class="mb-3">
                            <label for="total_payment" class="form-label">Payment Amount (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('total_payment') is-invalid @enderror" 
                                   id="total_payment" name="total_payment" min="0.01" step="0.01"
                                   value="{{ old('total_payment') }}" required 
                                   max="{{ $remainingAmount }}"
                                   placeholder="Max: Rp {{ number_format($remainingAmount, 2, ',', '.') }}">
                            @error('total_payment')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @else
                                <small class="text-muted d-block mt-1">Maximum amount: Rp {{ number_format($remainingAmount, 2, ',', '.') }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                   id="payment_date" name="payment_date"
                                   value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3" placeholder="Additional notes" maxlength="500">{{ old('notes') }}</textarea>
                            @error('notes')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Note:</strong> The installment amount cannot exceed the remaining payment of 
                            <strong>Rp {{ number_format($remainingAmount, 2, ',', '.') }}</strong>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Record Installment
                            </button>
                            <a href="{{ route('employee.payments.show', $payment->payment_id) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
