@extends('layouts.app')

@section('title', 'Assign Students')
@section('page-title', 'Assign Students to Class')

@section('content')
<div class="container-fluid">
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-plus-circle text-primary"></i> Assign Students to Class
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
                <form action="{{ route('employee.student_classes.store') }}" method="POST" id="assign-student-form">
                    @csrf

                    {{-- ROW 1: Academic Year & Class --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Academic Year <span class="text-danger">*</span></label>
                            <select name="academic_year_id" id="academic_year_id" class="form-select" required
                                    oninvalid="this.classList.add('is-invalid')"
                                    oninput="this.classList.remove('is-invalid')">
                                <option value="">-- Select Academic Year --</option>
                            </select>
                            <small class="text-muted d-block">Required. Select an academic year.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Academic Class <span class="text-danger">*</span></label>
                            <select name="academic_class_id" id="academic_class_id" class="form-select" required
                                    oninvalid="this.classList.add('is-invalid')"
                                    oninput="this.classList.remove('is-invalid')">
                                <option value="">-- Select Academic Class --</option>
                            </select>
                            <small class="text-muted d-block">Required. Select an academic class.</small>
                        </div>
                    </div>

                    {{-- STUDENT LIST --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Students <span class="text-danger">*</span></label>
                        <div class="border rounded p-3" style="max-height: 420px; overflow-y: auto;">
                            @forelse ($students as $student)
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->student_id }}"
                                           id="student_{{ $student->student_id }}"
                                           class="form-check-input"
                                           {{ in_array($student->student_id, old('student_ids', [])) ? 'checked' : '' }}
                                           required
                                           oninvalid="this.classList.add('is-invalid')"
                                           oninput="this.classList.remove('is-invalid')">
                                    <label class="form-check-label" for="student_{{ $student->student_id }}">
                                        {{ $student->first_name }} {{ $student->last_name }}
                                        <small class="text-muted">({{ $student->student_id }})</small>
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">No active students available.</p>
                            @endforelse
                        </div>
                        <small class="text-muted d-block">Required. Select at least one student.</small>
                    </div>

                    {{-- BUTTONS --}}
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" id="save-assign-btn" class="btn btn-primary shadow-sm">
                            <i class="fas fa-save"></i> Assign Students
                        </button>
                        <a href="{{ route('employee.student_classes.index') }}" class="btn btn-secondary shadow-sm">
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
    const selectedAcademicYearId = '{{ old('academic_year_id') ?? '' }}';
    const selectedAcademicClassId = '{{ old('academic_class_id') ?? '' }}';

    // Load academic years
    function loadYears() {
        $.getJSON('{{ route('employee.academic_classes.api.years') }}')
            .done(function(data){
                const $year = $('#academic_year_id');
                $year.find('option:not(:first)').remove();
                data.forEach(function(y){
                    $year.append(`<option value="${y.academic_year_id}">${y.academic_year_id}</option>`);
                });
                if (selectedAcademicYearId) $year.val(selectedAcademicYearId).trigger('change');
            });
    }

    // Load classes based on year
    function loadAcademicClasses(yearId){
        const urlBase = '{{ url('academic-classes/api/by-year') }}';
        const $ac = $('#academic_class_id');

        $ac.find('option:not(:first)').remove();
        if (!yearId) return;

        $.getJSON(urlBase + '/' + yearId)
            .done(function(data){
                data.forEach(function(item){
                    const text = `${item.class_name} (Level ${item.class_level})`;
                    $ac.append($('<option>').val(item.academic_class_id).text(item.academic_class_id + ' - ' + text));
                });
                if (selectedAcademicClassId) $ac.val(selectedAcademicClassId);
            });
    }

    loadYears();

    $('#academic_year_id').on('change', function(){
        loadAcademicClasses($(this).val());
    });

    // Client-side checkbox validation
    $('#assign-student-form').on('submit', function(e){
        if ($('input[name="student_ids[]"]:checked').length === 0){
            alert('Please select at least one student.');
            e.preventDefault();
        }
    });
});
</script>
@endpush
