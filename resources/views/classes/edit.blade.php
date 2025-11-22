@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-edit"></i> Edit Class</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.classes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Classes
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="class-form" action="{{ route('employee.classes.update', $class->class_id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="class_id" class="form-label">Class ID</label>
                        <input type="text" class="form-control" id="class_id" 
                               value="{{ $class->class_id }}" disabled>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="class_name" class="form-label">Class Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('class_name') is-invalid @enderror" 
                               id="class_name" name="class_name" value="{{ old('class_name', $class->class_name) }}" required>
                        @error('class_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="class_level" class="form-label">Class Level <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('class_level') is-invalid @enderror" 
                               id="class_level" name="class_level" value="{{ old('class_level', $class->class_level) }}" required>
                        @error('class_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Class
                    </button>
                    <a href="{{ route('employee.classes.index') }}" class="btn btn-secondary">
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

    $('#class-form').on('submit', function(e){
        e.preventDefault();
        const $form = $(this);
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax').remove();

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            success: function(res){ window.location = "{{ route('employee.classes.index') }}"; },
            error: function(xhr){
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    alert(xhr.responseJSON?.message || 'Failed to update class');
                }
            }
        });
    });
});
</script>
@endpush
