@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">

            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Create New Payment</h5>
                </div>

                <div class="card-body">

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle"></i> Validation Errors
                            </h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('employee.payments.store') }}" method="POST" id="payment-form">
                        @csrf

                        {{-- Student --}}
                        <div class="mb-3">
                            <label for="student_class_id" class="form-label">
                                Student <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('student_class_id') is-invalid @enderror"
                                    id="student_class_id" name="student_class_id" required>
                                <option value="">-- Select Student --</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->student_class_id }}"
                                        {{ old('student_class_id') == $student->student_class_id ? 'selected' : '' }}>
                                        {{ $student->first_name }} {{ $student->last_name }} 
                                        ({{ $student->class_name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('student_class_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Payment Type --}}
                        <div class="mb-3">
                            <label for="payment_type" class="form-label">
                                Payment Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('payment_type') is-invalid @enderror"
                                    id="payment_type" name="payment_type" required>
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
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Total Payment --}}
                        <div class="mb-3">
                            <label for="total_payment_visible" class="form-label">
                                Total Payment (Rp) <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="number"
                                class="form-control @error('total_payment') is-invalid @enderror"
                                id="total_payment_visible"
                                name="total_payment_visible"
                                min="0.01" step="0.01"
                                value="{{ old('total_payment') }}"
                                required>
                            @error('total_payment')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Hidden real field --}}
                        <input type="hidden" id="total_payment" name="total_payment" value="{{ old('total_payment') }}">

                        {{-- Payment Date --}}
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">
                                Payment Date <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="date"
                                class="form-control @error('payment_date') is-invalid @enderror"
                                id="payment_date"
                                name="payment_date"
                                value="{{ old('payment_date', date('Y-m-d')) }}"
                                required>
                            @error('payment_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Payment Method --}}
                        <div class="mb-3">
                            <label class="form-label">
                                Payment Method <span class="text-danger">*</span>
                            </label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input 
                                        class="form-check-input" 
                                        type="radio" 
                                        name="payment_method" 
                                        id="pm_paid" 
                                        value="Paid"
                                        {{ old('payment_method') == 'Paid' ? 'checked' : '' }}
                                        required>
                                    <label class="form-check-label" for="pm_paid">Paid</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input 
                                        class="form-check-input" 
                                        type="radio" 
                                        name="payment_method" 
                                        id="pm_instalment" 
                                        value="Instalment"
                                        {{ old('payment_method') == 'Instalment' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pm_instalment">Instalment</label>
                                </div>
                            </div>
                            @error('payment_method')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Notes --}}
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea 
                                class="form-control @error('notes') is-invalid @enderror"
                                id="notes"
                                name="notes"
                                rows="3"
                                placeholder="Additional notes"
                                maxlength="500">{{ old('notes') }}</textarea>
                            @error('notes')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div id="form-errors"></div>

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
</div>
@endsection

@push('scripts')
<script>
$(function() {

    // Sync hidden total_payment with visible field
    $(document).on('input', '#total_payment_visible', function () {
        $('#total_payment').val($(this).val());
    });

    // AJAX Submit
    $('#btn-submit').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);

        $('#form-errors').html('');
        $btn.prop('disabled', true);

        var form = $('#payment-form')[0];

        var visibleTotal = $('#total_payment_visible').val();
        $('#total_payment').val(visibleTotal);

        var data = new FormData(form);

        $.ajax({
            url: $(form).attr('action'),
            method: 'POST',
            data: data,
            processData: false,
            contentType: false,
            headers: { 
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
            },

            success: function (res) {
                if (res.success) {
                    window.location.href = '{{ route("employee.payments.index") }}';
                } else {
                    $('#form-errors').html('<div class="alert alert-danger">Unexpected server response.</div>');
                }
            },

            error: function (xhr) {
                var html = '<div class="alert alert-danger"><ul>';
                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors, function (key, msgs) {
                        msgs.forEach(msg => html += `<li>${msg}</li>`);
                    });
                } else {
                    html += `<li>${xhr.responseJSON.error ?? xhr.statusText}</li>`;
                }
                html += '</ul></div>';

                $('#form-errors').html(html);
                $btn.prop('disabled', false);
            }
        });

        $('#payment_type').on('change', function () {
            let price = $('option:selected', this).data('price') || 0;
            $('#total_payment_visible').val(price);
            $('#total_payment').val(price);
        });
    });
});
</script>
@endpush