@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Edit Payment</h5>
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

                    <form action="{{ route('employee.payments.update', $payment->payment_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="student_class_id" class="form-label">Student</label>
                            <input type="text" class="form-control" disabled value="{{ $payment->student->first_name }} {{ $payment->student->last_name }}">
                            <small class="text-muted d-block mt-1">Cannot change student. Create new payment if needed.</small>
                        </div>

                        <div class="mb-3">
                            <label for="payment_type" class="form-label">Payment Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_type') is-invalid @enderror" id="payment_type" name="payment_type" required>
                                <option value="">-- Select Type --</option>
                                @foreach ($paymentTypes as $type)
                                    <option value="{{ $type }}" {{ old('payment_type', $payment->payment_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('payment_type')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="total_payment" class="form-label">Total Payment (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('total_payment') is-invalid @enderror" 
                                   id="total_payment" name="total_payment" min="0.01" step="0.01"
                                   value="{{ old('total_payment', $payment->total_payment) }}" required>
                            @error('total_payment')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">-- Select Status --</option>
                                <option value="Pending" {{ old('status', $payment->status) == 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="Partial" {{ old('status', $payment->status) == 'Partial' ? 'selected' : '' }}>Partial</option>
                                <option value="Paid" {{ old('status', $payment->status) == 'Paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                            @error('status')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3" placeholder="Additional notes" maxlength="500">{{ old('notes', $payment->notes) }}</textarea>
                            @error('notes')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="alert alert-info" role="alert">
                            <strong>Payment Summary:</strong><br>
                            Total: <strong>Rp {{ number_format($payment->total_payment, 2, ',', '.') }}</strong> | 
                            Remaining: <strong>Rp {{ number_format($payment->remaining_payment, 2, ',', '.') }}</strong>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Payment
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
