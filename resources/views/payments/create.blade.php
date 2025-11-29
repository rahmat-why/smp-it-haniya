@extends('layouts.app')

@section('title', 'Create Payment')
@section('page-title', 'Add New Payment')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">

        {{-- GLOBAL ALERT --}}
        @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <h6 class="alert-heading">
                <i class="fas fa-exclamation-triangle"></i> Validation Errors
            </h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-coins"></i> Add New Payment
                </h5>
            </div>

            <div class="card-body">
                <form action="{{ route('employee.payments.store') }}" method="POST" id="payment-form">
                    @csrf

                    {{-- ROW 1: STUDENT --}}
                    <div class="mb-3">
                        <label for="student_class_id" class="form-label fw-bold">
                            Student <span class="text-danger">*</span>
                        </label>
                        <select id="student_class_id"
                                name="student_class_id"
                                class="form-select @error('student_class_id') is-invalid @enderror"
                                required>
                            <option value="">-- Select Student --</option>
                            @foreach ($students as $student)
                            <option value="{{ $student->student_class_id }}"
                                {{ old('student_class_id') == $student->student_class_id ? 'selected' : '' }}>
                                {{ $student->first_name }} {{ $student->last_name }} ({{ $student->class_name }})
                            </option>
                            @endforeach
                        </select>
                        @error('student_class_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ROW 2: PAYMENT TYPE --}}
                    <div class="mb-3">
                        <label for="payment_type" class="form-label fw-bold">
                            Payment Type <span class="text-danger">*</span>
                        </label>
                        <select id="payment_type"
                                name="payment_type"
                                class="form-select @error('payment_type') is-invalid @enderror"
                                required>
                            <option value="">-- Select Type --</option>
                            @foreach ($paymentTypes as $type)
                            <option value="{{ $type->item_code }}"
                                    data-price="{{ $type->item_desc }}"
                                    {{ old('payment_type') == $type->item_code ? 'selected' : '' }}>
                                {{ $type->item_name }} ({{ number_format($type->item_desc) }})
                            </option>
                            @endforeach
                        </select>
                        @error('payment_type')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ROW 3: TOTAL PAYMENT --}}
                    <div class="mb-3">
                        <label for="total_payment_visible" class="form-label fw-bold">
                            Total Payment (Rp) <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                               id="total_payment_visible"
                               name="total_payment_visible"
                               class="form-control @error('total_payment') is-invalid @enderror"
                               min="0.01"
                               step="0.01"
                               value="{{ old('total_payment') }}"
                               required>
                        @error('total_payment')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- HIDDEN REAL FIELD --}}
                    <input type="hidden"
                           id="total_payment"
                           name="total_payment"
                           value="{{ old('total_payment') }}">

                    {{-- ROW 4: PAYMENT DATE --}}
                    <div class="mb-3">
                        <label for="payment_date" class="form-label fw-bold">
                            Payment Date <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               id="payment_date"
                               name="payment_date"
                               class="form-control @error('payment_date') is-invalid @enderror"
                               value="{{ old('payment_date', date('Y-m-d')) }}"
                               required>
                        @error('payment_date')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ROW 5: PAYMENT METHOD --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Payment Method <span class="text-danger">*</span>
                        </label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input type="radio"
                                       name="payment_method"
                                       id="pm_paid"
                                       class="form-check-input"
                                       value="Paid"
                                       {{ old('payment_method') == 'Paid' ? 'checked' : '' }}
                                       required>
                                <label for="pm_paid" class="form-check-label">Paid</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input type="radio"
                                       name="payment_method"
                                       id="pm_instalment"
                                       class="form-check-input"
                                       value="Instalment"
                                       {{ old('payment_method') == 'Instalment' ? 'checked' : '' }}>
                                <label for="pm_instalment" class="form-check-label">Instalment</label>
                            </div>
                        </div>
                        @error('payment_method')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ROW 6: NOTES --}}
                    <div class="mb-3">
                        <label for="notes" class="form-label fw-bold">Notes</label>
                        <textarea id="notes"
                                  name="notes"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Additional notes">{{ old('notes') }}</textarea>
                        @error('notes')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="form-errors"></div>

                    {{-- BUTTONS --}}
                    <div class="d-flex gap-2">
                        <button type="submit" id="btn-submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Create Payment
                        </button>
                        <a href="{{ route('employee.payments.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {

    // Sync hidden field with visible
    $(document).on('input', '#total_payment_visible', function () {
        $('#total_payment').val($(this).val());
    });

    // Auto set price from payment type
    $('#payment_type').on('change', function () {
        let price = $('option:selected', this).data('price') || 0;
        $('#total_payment_visible').val(price);
        $('#total_payment').val(price);
    });

});
</script>
@endpush
