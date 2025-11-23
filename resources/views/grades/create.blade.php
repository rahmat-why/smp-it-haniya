@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Record Grades by Class</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="grade-form" action="{{ route('employee.grades.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">-- Select Class --</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->class_id }}">{{ $class->class_name }} (Level {{ $class->class_level }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">-- Select Subject --</option>
                                @foreach ($subjects as $sub)
                                    <option value="{{ $sub->subject_id }}">{{ $sub->subject_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                <option value="">-- Select Teacher --</option>
                                @foreach ($teachers as $t)
                                    <option value="{{ $t->teacher_id }}">{{ $t->first_name }} {{ $t->last_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="grade_type" class="form-label">Grade Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="grade_type" name="grade_type" required>
                                <option value="">-- Select Grade Type --</option>
                                @if(!empty($gradeTypes))
                                    @foreach($gradeTypes as $gt)
                                        <option value="{{ $gt->item_code }}">{{ $gt->item_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div id="students-container" class="mb-3" style="display:none;">
                            <label class="form-label">Student Grades</label>
                            <div id="students-list" class="border rounded p-3" style="max-height:400px; overflow-y:auto;">
                                <p class="text-muted text-center">Select a class to load students</p>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success" id="save-btn" disabled>
                                <i class="fas fa-save"></i> Save Grades
                            </button>
                            <a href="{{ route('employee.grades.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(function(){
        const $container = $('#students-container');
        const $studentsList = $('#students-list');
        const $saveBtn = $('#save-btn');

        function renderStudents(students) {
            if (!Array.isArray(students) || students.length === 0) {
                $studentsList.html('<p class="text-muted text-center">No students in this class</p>');
                $container.show();
                $saveBtn.prop('disabled', true);
                return;
            }

            let html = '<table class="table table-sm table-bordered"><thead class="table-light"><tr><th>NIS</th><th>Name</th><th>Grade</th><th>Attitude</th><th>Notes</th></tr></thead><tbody>';

            students.forEach(function(s){
                const id = s.student_class_id;
                html += '<tr>' +
                    `<td><span class="badge bg-info">${s.student_id}</span></td>` +
                    `<td>${s.first_name} ${s.last_name}</td>` +
                    `<td><input type="number" step="0.01" name="grades[${id}][grade_value]" class="form-control form-control-sm"></td>` +
                    `<td><input type="text" name="grades[${id}][grade_attitude]" class="form-control form-control-sm"></td>` +
                    `<td><input type="text" name="grades[${id}][notes]" class="form-control form-control-sm"></td>` +
                    `<input type="hidden" name="grades[${id}][student_class_id]" value="${id}">` +
                    '</tr>';
            });

            html += '</tbody></table>';
            $studentsList.html(html);
            $container.show();
            $saveBtn.prop('disabled', false);
        }

        $('#class_id').on('change', function(){
            console.log('grade create: class change');
            const classId = $(this).val();
            if (!classId) { $container.hide(); $saveBtn.prop('disabled', true); return; }

            $studentsList.html('<p class="text-muted text-center">Loading...</p>');

            $.ajax({
                url: '/api/grade/students/' + encodeURIComponent(classId),
                method: 'GET',
                dataType: 'json'
            }).done(function(students){
                renderStudents(students);
            }).fail(function(xhr){
                let msg = 'Error loading students';
                try { msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : xhr.responseText; } catch(e){}
                $studentsList.html(`<p class="text-danger">${msg}</p>`);
                $saveBtn.prop('disabled', true);
                $container.show();
            });
        });

        // trigger on page load if class already selected
        const pre = $('#class_id').val();
        if (pre) { $('#class_id').trigger('change'); }
    
        // AJAX submit for grade form
        $(document).on('submit', '#grade-form', function(e){
            e.preventDefault();
            const $form = $(this);
            const url = '{{ route("employee.grades.store") }}';
            const $btn = $form.find('button[type=submit]');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: url,
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json'
            }).done(function(res){
                if (res && res.redirect) {
                    window.location.href = res.redirect;
                    return;
                }
                // fallback: if server returns success message
                alert(res.message || 'Grades saved');
                window.location.reload();
            }).fail(function(xhr){
                let msg = 'Error saving grades';
                try { msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : xhr.responseText; } catch(e){}
                alert(msg);
            }).always(function(){
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Grades');
            });
        });

    });
</script>
@endpush