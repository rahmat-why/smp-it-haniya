@extends('layouts.app')

@section('title', 'Edit Subject')
@section('page-title', 'Subject Management')

@section('content')

<div class="container-fluid">

    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-edit text-primary"></i> Edit Subject
            </h5>
        </div>

        <div class="card-body px-4">

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form id="subject-form" 
                  action="{{ route('employee.subjects.update', $subject->subject_id) }}" 
                  method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold">
                            Subject Code <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="subject_code"
                               class="form-control @error('subject_code') is-invalid @enderror"
                               value="{{ old('subject_code', $subject->subject_code) }}"
                               required>
                        @error('subject_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold">
                            Subject Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="subject_name"
                               class="form-control @error('subject_name') is-invalid @enderror"
                               value="{{ old('subject_name', $subject->subject_name) }}"
                               required>
                        @error('subject_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- DROPDOWN CLASS LEVEL -->
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold">
                            Class Level <span class="text-danger">*</span>
                        </label>
                        <select name="class_level" 
                                class="form-control @error('class_level') is-invalid @enderror"
                                required>
                            <option value="">-- Select Level --</option>
                            <option value="7" {{ old('class_level', $subject->class_level) == '7' ? 'selected' : '' }}>7</option>
                            <option value="8" {{ old('class_level', $subject->class_level) == '8' ? 'selected' : '' }}>8</option>
                            <option value="9" {{ old('class_level', $subject->class_level) == '9' ? 'selected' : '' }}>9</option>
                        </select>
                        @error('class_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <!-- MINIMUM VALUE FLOAT -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold">
                            Minimum Value <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01"
                               name="minimum_value"
                               class="form-control @error('minimum_value') is-invalid @enderror"
                               value="{{ old('minimum_value', $subject->minimum_value) }}"
                               required>
                        <small class="text-muted">Contoh: 75.50</small>
                        @error('minimum_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- DESCRIPTION -->
                <div class="mb-3">
                    <label class="fw-semibold">Description</label>
                    <textarea name="description"
                              rows="4"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $subject->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- ACTION BUTTONS -->
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fas fa-save"></i> Update Subject
                    </button>

                    <a href="{{ route('employee.subjects.index') }}" 
                       class="btn btn-secondary shadow-sm">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function(){

    function showValidationErrors($form, errors){
        for (const field in errors) {
            const input = $form.find(`[name="${field}"]`);
            input.addClass('is-invalid');
            input.after(`<div class="invalid-feedback ajax">${errors[field][0]}</div>`);
        }
    }

    $('#subject-form').on('submit', function(e){
        e.preventDefault();

        const $form = $(this);
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax').remove();

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            success: function(){
                window.location = "{{ route('employee.subjects.index') }}";
            },
            error: function(xhr){
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    showValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    alert(xhr.responseJSON?.message || 'Failed to update subject');
                }
            }
        });
    });
});
</script>
@endpush
