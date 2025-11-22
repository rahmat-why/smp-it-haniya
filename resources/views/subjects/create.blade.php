@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-plus-circle"></i> Create New Subject</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.subjects.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Subjects
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="subject-form" action="{{ route('employee.subjects.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
               <label for="subject_id" class="form-label">Subject ID <span class="text-danger">*</span></label>
               <input type="text" readonly class="form-control @error('subject_id') is-invalid @enderror" 
                   id="subject_id" name="subject_id" value="{{ old('subject_id') }}" required>
                        @error('subject_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject_code') is-invalid @enderror" 
                               id="subject_code" name="subject_code" value="{{ old('subject_code') }}" required>
                        @error('subject_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject_name') is-invalid @enderror" 
                               id="subject_name" name="subject_name" value="{{ old('subject_name') }}" required>
                        @error('subject_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="class_level" class="form-label">Class Level <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('class_level') is-invalid @enderror" 
                               id="class_level" name="class_level" value="{{ old('class_level') }}" required>
                        @error('class_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="4">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Subject
                    </button>
                    <a href="{{ route('employee.subjects.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
$(function(){
    function showValidationErrors($form, errors){
        for (const field in errors) {
            const $el = $form.find('[name="'+field+'"]').first();
            if ($el.length) {
                $el.addClass('is-invalid');
                $el.after('<div class="invalid-feedback ajax">'+errors[field][0]+'</div>');
            }
        }
    }

    $('#subject-form').on('submit', function(e){
        e.preventDefault();
        const $form = $(this);
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax').remove();

        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method') || 'POST',
            data: $form.serialize(),
            success: function(res){ window.location = "{{ route('employee.subjects.index') }}"; },
            error: function(xhr){
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    alert(xhr.responseJSON?.message || 'Failed to create subject');
                }
            }
        });
    });
});
    // fetch new subject id
    $.getJSON("{{ route('employee.subjects.getNewId') }}").done(function(res){
        if (res.subject_id) $('#subject_id').val(res.subject_id);
    });
</script>
@endpush
