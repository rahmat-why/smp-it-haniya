@extends('layouts.app')

@section('title', 'Edit Student Class Assignment')
@section('page-title', 'Student Class Assignment')

@section('content')

<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-edit text-primary"></i> Edit Student Class Assignment
            </h5>
            <a href="{{ route('employee.student_classes.index') }}"
               class="btn btn-secondary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <!-- BODY -->
        <div class="card-body px-4">

            <!-- ALERT SUCCESS -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- ALERT ERROR -->
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

                <form action="{{ route('employee.student_classes.update', $studentClass->student_class_id) }}"
                      method="POST">

                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- ASSIGNMENT ID -->
                       <div class="col-md-6 d-none">
                            <label class="form-label fw-bold">Assignment ID</label>
                            <input type="text" 
                                   class="form-control" 
                                   value="{{ $studentClass->student_class_id }}" 
                                   disabled>
                        </div>

                        <!-- STUDENT -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Student</label>
                            <input type="text" 
                                   class="form-control" 
                                   value="{{ $studentClass->first_name }} {{ $studentClass->last_name }} ({{ $studentClass->student_id }})" 
                                   disabled>
                        </div>
                    </div>

                    <div class="row">
                        <!-- ACADEMIC YEAR -->
                        <div class="col-md-6 mb-3">
                            <label for="academic_year_id" class="form-label fw-bold">
                                Academic Year <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('academic_year_id') is-invalid @enderror"
                                    id="academic_year_id" name="academic_year_id" required>
                                <option value="">-- Select Academic Year --</option>
                            </select>
                            @error('academic_year_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- ACADEMIC CLASS -->
                    <div class="mb-3">
                        <label for="academic_class_id" class="form-label fw-bold">
                            Academic Class <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('academic_class_id') is-invalid @enderror"
                                id="academic_class_id" name="academic_class_id" required>
                            <option value="">-- Select Academic Class --</option>
                        </select>
                        @error('academic_class_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- BUTTONS -->
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="fas fa-save"></i> Save Changes
                        </button>

                        <a href="{{ route('employee.student_classes.index') }}"
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

    const selectedAcademicClassId = '{{ $studentClass->academic_class_id ?? '' }}';
    const currentYear = '{{ $studentClass->academic_year_id ?? '' }}';

    function loadYears() {
        $.getJSON('{{ route('employee.academic_classes.api.years') }}')
            .done(function(data){
                const $year = $('#academic_year_id');
                $year.find('option:not(:first)').remove();

                data.forEach(function(y){
                    $year.append(`<option value="${y.academic_year_id}">
                        ${y.academic_year_id}
                    </option>`);
                });

                if (currentYear) {
                    $year.val(currentYear).trigger('change');
                }
            });
    }

    function loadAcademicClasses(yearId){
        const urlBase = '{{ url('academic-classes/api/by-year') }}';

        if (!yearId) {
            $('#academic_class_id').find('option:not(:first)').remove();
            return;
        }

        $.getJSON(urlBase + '/' + encodeURIComponent(yearId))
            .done(function(data){
                const $ac = $('#academic_class_id');
                $ac.find('option:not(:first)').remove();

                data.forEach(function(item){
                    const text = `${item.class_name} (Level ${item.class_level})`;
                    $ac.append(
                        $('<option>')
                            .val(item.academic_class_id)
                            .text(item.academic_class_id + ' - ' + text)
                    );
                });

                if (selectedAcademicClassId) 
                    $ac.val(selectedAcademicClassId);
            });
    }

    // INIT
    loadYears();

    $('#academic_year_id').on('change', function(){
        loadAcademicClasses($(this).val());
    });

});
</script>
@endpush
