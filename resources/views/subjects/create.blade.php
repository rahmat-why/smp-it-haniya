@extends('layouts.app')

@section('title', 'Create Subject')
@section('page-title', 'Create New Subject')

@section('content')
<div class="container-fluid">
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-plus-circle text-primary"></i> Create New Subject
            </h5>
        </div>

        <div class="card-body px-4">

            {{-- Alert Messages --}}
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="bg-white border rounded p-4 shadow-sm">
                <form id="subject-form" action="{{ route('employee.subjects.store') }}" method="POST" novalidate>
                    @csrf

                    {{-- ROW 1 --}}
                    <div class="row mb-4">
                        <div class="col-md-6 d-none">
                            <label class="form-label fw-bold">Subject ID <span class="text-danger">*</span></label>
                            <input type="text" readonly name="subject_id" id="subject_id"
                                   class="form-control @error('subject_id') is-invalid @enderror"
                                   value="{{ old('subject_id') }}" required>
                            <small class="text-muted d-block">Auto-generated subject ID.</small>
                            @error('subject_id')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" name="subject_code" id="subject_code"
                                   class="form-control @error('subject_code') is-invalid @enderror"
                                   value="{{ old('subject_code') }}" required>
                            <small class="text-muted d-block">Enter subject code.</small>
                            @error('subject_code')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    {{-- ROW 2 --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Subject Name <span class="text-danger">*</span></label>
                            <input type="text" name="subject_name" id="subject_name"
                                   class="form-control @error('subject_name') is-invalid @enderror"
                                   value="{{ old('subject_name') }}" required>
                            <small class="text-muted d-block">Enter subject name.</small>
                            @error('subject_name')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- CLASS LEVEL DROPDOWN (PERMINTAAN) --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Class Level <span class="text-danger">*</span></label>

                            <select name="class_level" id="class_level"
                                    class="form-control @error('class_level') is-invalid @enderror"
                                    required>
                                <option value="">-- Select Class Level --</option>
                                <option value="7" {{ old('class_level') == '7' ? 'selected' : '' }}>7</option>
                                <option value="8" {{ old('class_level') == '8' ? 'selected' : '' }}>8</option>
                                <option value="9" {{ old('class_level') == '9' ? 'selected' : '' }}>9</option>
                            </select>

                            <small class="text-muted d-block">Pilih level kelas.</small>
                            @error('class_level')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    {{-- ROW 3 — MINIMUM VALUE --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Minimum Value <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="minimum_value" id="minimum_value"
                                   class="form-control @error('minimum_value') is-invalid @enderror"
                                   value="{{ old('minimum_value') }}" required>
                            <small class="text-muted d-block">Masukkan nilai minimum (cth: 75.5)</small>
                            @error('minimum_value')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    {{-- DESCRIPTION --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" id="description" rows="4"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        <small class="text-muted d-block">Optional. Enter description if needed.</small>
                        @error('description')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- BUTTONS --}}
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="fas fa-save"></i> Create Subject
                        </button>
                        <a href="{{ route('employee.subjects.index') }}" class="btn btn-secondary shadow-sm">
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
$(function(){
    // Client-side validation
    $('#subject-form').on('submit', function(e){
        let isValid = true;
        $(this).find('input, textarea, select').each(function(){
            if (this.hasAttribute('required') && !$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        if (!isValid) e.preventDefault();
    });

    function showValidationErrors($form, errors){
        for (const field in errors) {
            const $el = $form.find('[name="'+field+'"]').first();
            if ($el.length) {
                $el.addClass('is-invalid');
                $el.after('<div class="invalid-feedback ajax">'+errors[field][0]+'</div>');
            }
        }
    }

    // AJAX submit
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
                if (xhr.status === 422) {
                    showValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    alert(xhr.responseJSON?.message || 'Failed to create subject');
                }
            }
        });
    });

    // Auto fetch ID
    $.getJSON("{{ route('employee.subjects.getNewId') }}")
        .done(function(res){
            if (res.subject_id) $('#subject_id').val(res.subject_id);
        });
});
</script>
@endpush
