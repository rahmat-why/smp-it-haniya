@extends('layouts.app')

@section('title', 'Edit Class')
@section('page-title', 'Class Management')

@section('content')

<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-edit text-primary"></i> Edit Class
            </h5>
            <a href="{{ route('employee.classes.index') }}" 
               class="btn btn-secondary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <!-- BODY -->
        <div class="card-body px-4">

            <!-- VALIDATION ERROR -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <h6 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-triangle"></i> Validation Errors
                    </h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- FORM WRAPPER -->
            <div class="bg-white border rounded p-4 shadow-sm">

                <form id="class-form" action="{{ route('employee.classes.update', $class->class_id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- CLASS ID -->
                       <div class="col-md-6 d-none">
                            <label for="class_id" class="form-label fw-bold">Class ID</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="class_id" 
                                value="{{ $class->class_id }}" 
                                disabled>
                        </div>

                        <!-- CLASS NAME -->
                        <div class="col-md-6 mb-3">
                            <label for="class_name" class="form-label fw-bold">
                                Class Name <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control @error('class_name') is-invalid @enderror" 
                                id="class_name" 
                                name="class_name" 
                                value="{{ old('class_name', $class->class_name) }}" 
                                required>
                            @error('class_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- CLASS LEVEL -->
                        <div class="col-md-6 mb-3">
                            <label for="class_level" class="form-label fw-bold">
                                Class Level <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control @error('class_level') is-invalid @enderror" 
                                id="class_level" 
                                name="class_level" 
                                value="{{ old('class_level', $class->class_level) }}" 
                                required>
                            @error('class_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- BUTTONS -->
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="fas fa-save"></i> Update Class
                        </button>

                        <a href="{{ route('employee.classes.index') }}" 
                           class="btn btn-secondary shadow-sm">
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
            success: function(){
                window.location = "{{ route('employee.classes.index') }}";
            },
            error: function(xhr){
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
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
