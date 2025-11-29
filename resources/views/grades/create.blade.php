@extends('layouts.app')

@section('title', 'Create Grade')
@section('page-title', 'Grade Management')

@section('content')

<div class="container-fluid">

    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Create Grade</h5>
        </div>

        <div class="card-body px-4 pb-5">

            <form id="grade-form">

                <div class="row mb-4">

                    {{-- CLASS --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            Class <span class="text-danger">*</span>
                        </label>
                        <select name="class_id"
                                id="class_id"
                                class="form-select"
                                required>
                            <option value="">-- Select Class --</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->class_id }}">
                                    {{ $class->class_name }} (Level {{ $class->class_level }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block">Choose a class to load students.</small>
                    </div>

                    {{-- SUBJECT --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Subject</label>
                        <select name="subject_id" id="subject_id" class="form-select" required>
                            <option value="">-- Select Subject --</option>
                            @foreach ($subjects as $sub)
                                <option value="{{ $sub->subject_id }}"
                                        data-min="{{ $sub->minimum_value }}">
                                    {{ $sub->subject_name }} (Min: {{ $sub->minimum_value }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Minimum value auto-applied to grade input.</small>
                    </div>

                    {{-- TEACHER --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Teacher</label>
                        <select name="teacher_id" id="teacher_id" class="form-select" required>
                            <option value="">-- Select Teacher --</option>
                            @foreach ($teachers as $t)
                                <option value="{{ $t->teacher_id }}">
                                    {{ $t->first_name }} {{ $t->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                {{-- GRADE TYPE --}}
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Grade Type</label>
                        <select name="grade_type" class="form-select" required>
                            <option value="">-- Select Grade Type --</option>
                            @foreach ($gradeTypes as $g)
                                <option value="{{ $g->item_code }}">{{ $g->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- STUDENT LIST --}}
                <div id="students-container" class="mt-4" style="display:none;">
                    <h6 class="fw-bold mb-3">Student List</h6>
                    <div id="students-list"></div>
                </div>

                {{-- SUBMIT --}}
                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" id="save-btn" class="btn btn-primary px-5" disabled>
                        <i class="fas fa-save"></i> Save Grades
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>


@push('scripts')
<script>
$(function(){

    // =============================================================
    // FIX CSRF TOKEN WAJIB UNTUK AJAX POST
    // =============================================================
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const $container = $('#students-container');
    const $studentsList = $('#students-list');
    const $saveBtn = $('#save-btn');



    // =============================================================
    // RENDER STUDENTS TABLE
    // =============================================================
    function renderStudents(students) {
        const minVal = $('#subject_id').find(':selected').data('min') || 0;

        if (!Array.isArray(students) || students.length === 0) {
            $studentsList.html('<p class="text-muted text-center">No students in this class</p>');
            $container.show();
            $saveBtn.prop('disabled', true);
            return;
        }

        let html = `
            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NIS</th>
                        <th>Name</th>
                        <th style="width:120px">
                            Minimum Value
                            <br><small class="text-muted">Min: ${minVal}</small>
                        </th>
                        <th style="width:160px">Grade</th>
                        <th style="width:160px">Attitude</th>
                        <th style="width:200px">Notes</th>
                    </tr>
                </thead>
                <tbody>
        `;

        students.forEach(s => {
            const id = s.student_class_id;

            html += `
                <tr>
                    <td><span class="badge bg-info">${s.student_id}</span></td>
                    <td>${s.first_name} ${s.last_name ?? ''}</td>

                    <td>
                        <input type="number"
                               step="0.01"
                               min="${minVal}"
                               placeholder="Min: ${minVal}"
                               class="form-control form-control-sm grade-input"
                               name="grades[${id}][grade_min]">
                    </td>

                    <td>
                        <input type="number"
                               class="form-control form-control-sm"
                               min="${minVal}"
                               name="grades[${id}][grade_value]">
                    </td>

                    <td>
                        <input type="text"
                               class="form-control form-control-sm"
                               name="grades[${id}][grade_attitude]">
                    </td>

                    <td>
                        <input type="text"
                               class="form-control form-control-sm"
                               name="grades[${id}][notes]">
                    </td>

                    <input type="hidden"
                           name="grades[${id}][student_class_id]"
                           value="${id}">
                </tr>
            `;
        });

        html += `</tbody></table>`;

        $studentsList.html(html);
        $container.show();
        $saveBtn.prop('disabled', false);
    }



    // =============================================================
    // LOAD STUDENTS WHEN CLASS SELECTED
    // =============================================================
    $('#class_id').on('change', function(){

        const classId = $(this).val();

        if (!classId) {
            $container.hide();
            $saveBtn.prop('disabled', true);
            return;
        }

        $studentsList.html('<p class="text-muted text-center">Loading...</p>');

        $.ajax({
            url: '/api/grade/students/' + encodeURIComponent(classId),
            method: 'GET',
            dataType: 'json'
        })
        .done(renderStudents)
        .fail(function(xhr){
            $studentsList.html(`
                <p class="text-danger">${xhr.responseJSON?.message || 'Failed to load students'}</p>
            `);
            $container.show();
            $saveBtn.prop('disabled', true);
        });

    });



    // =============================================================
    // UPDATE MINIMUM VALUE WHEN SUBJECT CHANGES
    // =============================================================
    $('#subject_id').on('change', function(){
        const min = $(this).find(':selected').data('min') || 0;

        $('.grade-input').each(function () {
            $(this).attr('min', min);
            $(this).attr('placeholder', 'Min: ' + min);
        });
    });



    // =============================================================
    // AJAX SUBMIT FORM
    // =============================================================
    $(document).on('submit', '#grade-form', function(e){
        e.preventDefault();

        const $btn = $('#save-btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '{{ route("employee.grades.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        })
        .done(function(res){
            if (res.redirect) {
                window.location.href = res.redirect;
                return;
            }
            alert(res.message || 'Grades saved');
        })
        .fail(function(xhr){
            alert(xhr.responseJSON?.message || 'Error saving grades');
        })
        .always(function(){
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Grades');
        });
    });

});
</script>
@endpush

@endsection
