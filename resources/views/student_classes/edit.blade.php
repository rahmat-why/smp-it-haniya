@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-edit"></i> Edit Student Class Assignment</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.student_classes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Assignments
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('employee.student_classes.update', $studentClass->student_class_id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Assignment ID</label>
                        <input type="text" class="form-control" value="{{ $studentClass->student_class_id }}" disabled>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Student</label>
                        <input type="text" class="form-control" value="{{ $studentClass->first_name }} {{ $studentClass->last_name }} ({{ $studentClass->student_id }})" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="academic_year_id" class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select class="form-select @error('academic_year_id') is-invalid @enderror" 
                                id="academic_year_id" name="academic_year_id" required>
                            <option value="">-- Select Academic Year --</option>
                        </select>
                        @error('academic_year_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="academic_class_id" class="form-label">Academic Class <span class="text-danger">*</span></label>
                    <select class="form-select @error('academic_class_id') is-invalid @enderror" 
                            id="academic_class_id" name="academic_class_id" required>
                        <option value="">-- Select Academic Class --</option>
                    </select>
                    @error('academic_class_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </form>
        </div>
    </div>
</div>

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
                    $year.append(`<option value="${y.academic_year_id}">${y.academic_year_id}</option>`);
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
                    $ac.append($('<option>').val(item.academic_class_id).text(item.academic_class_id + ' - ' + text));
                });
                if (selectedAcademicClassId) $ac.val(selectedAcademicClassId);
            });
    }

    // Init
    loadYears();

    $('#academic_year_id').on('change', function(){
        const year = $(this).val();
        loadAcademicClasses(year);
    });
});
</script>
@endpush
@endsection
