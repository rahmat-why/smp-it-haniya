@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-plus-circle"></i> Assign Students to Class</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.academic_classes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Academic Classes
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('employee.student_classes.store') }}" method="POST">
                @csrf

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

                <div class="mb-3">
                    <label class="form-label">Select Students <span class="text-danger">*</span></label>
                    <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                        @forelse ($students as $student)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="student_ids[]" 
                                       value="{{ $student->student_id }}" 
                                       id="student_{{ $student->student_id }}"
                                       {{ in_array($student->student_id, old('student_ids', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="student_{{ $student->student_id }}">
                                    {{ $student->first_name }} {{ $student->last_name }} 
                                    <small class="text-muted">({{ $student->student_id }})</small>
                                </label>
                            </div>
                        @empty
                            <p class="text-muted">No active students available</p>
                        @endforelse
                    </div>
                    @error('student_ids')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Assign Students
                    </button>
                    <a href="{{ route('employee.academic_classes.index') }}" class="btn btn-secondary">
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
    const selectedAcademicClassId = '{{ $selectedAcademicClassId ?? '' }}';

    // Load academic years via AJAX
    function loadYears() {
        $.getJSON('{{ route('employee.academic_classes.api.years') }}')
            .done(function(data){
                const $year = $('#academic_year_id');
                $year.find('option:not(:first)').remove();
                data.forEach(function(y){
                    $year.append(`<option value="${y.academic_year_id}">${y.academic_year_id}</option>`);
                });
            });
    }

    // Load academic classes for a given year via AJAX
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
                    const option = $('<option>')
                        .val(item.academic_class_id)
                        .text(item.academic_class_id + ' - ' + text);
                    $ac.append(option);
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

    // Checkbox selection tracking
    const checkboxes = document.querySelectorAll('input[name="student_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selected = document.querySelectorAll('input[name="student_ids[]"]:checked').length;
            console.log(selected + ' students selected');
        });
    });
});
</script>
@endpush
