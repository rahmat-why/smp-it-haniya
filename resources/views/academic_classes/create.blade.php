@extends('layouts.app')

@section('title', 'Create Academic Class')
@section('page-title', 'Create New Academic Class')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">

        {{-- Alert Boxes --}}
        <div class="alert alert-danger d-none" id="error-box"></div>
        <div class="alert alert-success d-none" id="success-box"></div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-layer-group"></i> Add New Academic Class
                </h5>
            </div>

            <div class="card-body">
                <form id="academicClassForm">
                    @csrf

                    
                    <div class="row mb-3">
                        <div class="col-md-6 d-none">
                            <label class="form-label">Academic Class ID *</label>
                            <input type="text" name="academic_class_id" id="academic_class_id"
                                   class="form-control" readonly>
                            <small class="text-danger error" data-error="academic_class_id"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Academic Year *</label>
                            <select name="academic_year_id" id="academic_year_id" class="form-select">
                                <option value="">-- Select Academic Year --</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->academic_year_id }}">
                                        {{ $year->academic_year_id }} - Semester {{ $year->semester }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-danger error" data-error="academic_year_id"></small>
                        </div>
                    </div>


                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Class *</label>
                            <select name="class_id" id="class_id" class="form-select">
                                <option value="">-- Select Class --</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->class_id }}">
                                        {{ $class->class_name }} (Level {{ $class->class_level }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-danger error" data-error="class_id"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Homeroom Teacher</label>
                            <select name="homeroom_teacher_id" class="form-select">
                                <option value="">-- Select Teacher --</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->employee_id }}">
                                        {{ $teacher->first_name }} {{ $teacher->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-danger error" data-error="homeroom_teacher_id"></small>
                        </div>
                    </div>


                    <hr>
                    <h5>Assign Students</h5>
                    <p class="text-muted">Students already assigned in this year will be disabled.</p>

                    <div class="border rounded p-3 mb-4" style="max-height:400px;overflow-y:auto;">
                        @forelse ($students as $student)
                            <div class="form-check">
                                <input class="form-check-input student-checkbox"
                                       type="checkbox"
                                       value="{{ $student->student_id }}"
                                       id="student_{{ $student->student_id }}"
                                       name="student_ids[]">

                                <label for="student_{{ $student->student_id }}" class="form-check-label">
                                    {{ $student->first_name }} {{ $student->last_name }}
                                    <small class="text-muted">({{ $student->student_id }})</small>
                                </label>
                            </div>
                        @empty
                            <p class="text-muted">No active students available</p>
                        @endforelse
                    </div>


                    <div class="d-flex gap-2">
                        <button type="submit" id="btnSave" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Academic Class
                        </button>

                        <a href="{{ route('employee.academic_classes.index') }}" class="btn btn-secondary">
                            Cancel
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
function validateForm() {
    let errors = {};
    let academic_class_id = $("[name=academic_class_id]").val().trim();
    let academic_year_id  = $("[name=academic_year_id]").val().trim();
    let class_id          = $("[name=class_id]").val().trim();

    if (academic_class_id === "") errors.academic_class_id = "ID is required";
    if (academic_year_id === "") errors.academic_year_id = "Academic Year is required";
    if (class_id === "") errors.class_id = "Class is required";

    $(".error").text("");
    for (let key in errors) {
        $(`[data-error=${key}]`).text(errors[key]);
    }

    return Object.keys(errors).length === 0;
}


$("#academicClassForm").submit(function(e){
    e.preventDefault();
    if (!validateForm()) return;

    let formData = new FormData(this);

    $("#btnSave").prop("disabled", true)
        .html('<i class="fas fa-spinner fa-spin"></i> Saving...');

    $.ajax({
        url: "{{ route('employee.academic_classes.store') }}",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(){
            $('#success-box').removeClass('d-none').text('Academic Class created successfully!');
            setTimeout(() => window.location.href = "{{ route('employee.academic_classes.index') }}", 600);
        },
        error: function(xhr){
            $("#btnSave").prop("disabled", false)
                .html('<i class="fas fa-save"></i> Save Academic Class');

            if (xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function(key, val){
                    $(`[data-error=${key}]`).text(val[0]);
                });
            }
        }
    });
});



// Auto-generate academic_class_id
$(document).ready(function(){
    $.ajax({
        url: "{{ route('employee.academic_classes.getNewId') }}",
        method: "GET",
        success: function(res){
            $('#academic_class_id').val(res.academic_class_id);
        }
    });
});


// Disable students already assigned in selected year
document.addEventListener('DOMContentLoaded', function(){
    const yearSelect = document.getElementById('academic_year_id');

    if (yearSelect) {
        yearSelect.addEventListener('change', function(){
            const yearId = this.value;
            const url = "{{ url('academic-classes/api/assigned-students') }}/" + yearId;

            if (!yearId) {
                document.querySelectorAll('.student-checkbox').forEach(cb=>{
                    cb.disabled = false;
                    cb.parentElement.classList.remove('text-muted');
                });
                return;
            }

            fetch(url)
                .then(res => res.json())
                .then(assignedIds => {
                    const assignedSet = new Set(assignedIds);

                    document.querySelectorAll('.student-checkbox').forEach(cb => {
                        if (assignedSet.has(cb.value)) {
                            cb.disabled = true;
                            cb.checked = false;
                            cb.parentElement.classList.add('text-muted');
                        } else {
                            cb.disabled = false;
                            cb.parentElement.classList.remove('text-muted');
                        }
                    });

                }).catch(err => console.error('Failed to load student data', err));
        });
    }
});
</script>
@endpush
