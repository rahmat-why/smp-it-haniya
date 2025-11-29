@extends('layouts.app')

@section('title', 'Record Attendance')
@section('page-title', 'Record Attendance by Class')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">

        {{-- ALERT BOXES --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <h6 class="mb-2"><i class="fas fa-exclamation-triangle"></i> Validation Errors</h6>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="ajax-alert"></div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-check"></i> Record Attendance
                </h5>
            </div>

            <div class="card-body">
                <form id="attendance-form"
                      action="{{ route('employee.attendances.store') }}"
                      method="POST">

                    @csrf

                    {{-- CLASS --}}
                    <div class="mb-3">
                        <label class="form-label">Class *</label>
                        <select class="form-select @error('class_id') is-invalid @enderror"
                                id="class_id"
                                name="class_id"
                                required>
                            <option value="">-- Select Class --</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->class_id }}"
                                    {{ old('class_id') == $c->class_id ? 'selected' : '' }}>
                                    {{ $c->class_name }} (Level {{ $c->class_level }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger">@error('class_id') {{ $message }} @enderror</small>
                    </div>

                    {{-- DATE --}}
                    <div class="mb-3">
                        <label class="form-label">Attendance Date *</label>
                        <input type="date"
                               class="form-control @error('attendance_date') is-invalid @enderror"
                               id="attendance_date"
                               name="attendance_date"
                               value="{{ old('attendance_date', date('Y-m-d')) }}"
                               required>
                        <small class="text-danger">@error('attendance_date') {{ $message }} @enderror</small>
                    </div>

                    {{-- TEACHER --}}
                    <div class="mb-3">
                        <label class="form-label">Teacher *</label>
                        <select class="form-select @error('teacher_id') is-invalid @enderror"
                                id="teacher_id"
                                name="teacher_id"
                                required>
                            <option value="">-- Select Teacher --</option>
                            @foreach ($teachers as $t)
                                <option value="{{ $t->teacher_id }}"
                                    {{ old('teacher_id') == $t->teacher_id ? 'selected' : '' }}>
                                    {{ $t->first_name }} {{ $t->last_name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger">@error('teacher_id') {{ $message }} @enderror</small>
                    </div>

                    {{-- STUDENT LIST LOADED VIA AJAX --}}
                    <div id="students-container" class="mb-3" style="display:none;">
                        <label class="form-label">Student Attendance *</label>
                        <div id="students-list"
                             class="border rounded p-3"
                             style="max-height:400px; overflow-y:auto;">
                            <p class="text-muted text-center">Select a class to load students</p>
                        </div>
                    </div>

                    {{-- BUTTONS --}}
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit"
                                class="btn btn-success"
                                id="submit-btn"
                                disabled>
                            <i class="fas fa-save"></i> Submit Attendance
                        </button>

                        <a href="{{ route('employee.attendances.index') }}"
                           class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

{{-- ========== JAVASCRIPT ========== --}}
<script>
    const container = document.getElementById('students-container');
    const studentsList = document.getElementById('students-list');
    const submitBtn = document.getElementById('submit-btn');

    function renderStudentsTable(students) {
        if (!students || students.length === 0) {
            studentsList.innerHTML =
                '<p class="text-muted text-center">No students in this class</p>';
            container.style.display = 'block';
            submitBtn.disabled = true;
            return;
        }

        let html = `
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width:12%">NIS</th>
                        <th style="width:38%">Name</th>
                        <th style="width:30%">Status</th>
                        <th style="width:20%">Notes</th>
                    </tr>
                </thead>
                <tbody>`;


        students.forEach(st => {
            const id = st.student_class_id;
            const name = `${st.first_name} ${st.last_name}`;

            html += `
                <tr>
                    <td><span class="badge bg-info">${st.student_id}</span></td>
                    <td>${name}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check"
                                   name="attendances[${id}][status]"
                                   id="status_present_${id}"
                                   value="Present"
                                   checked>
                            <label class="btn btn-sm btn-outline-success"
                                   for="status_present_${id}">
                                   Present
                            </label>

                            <input type="radio" class="btn-check"
                                   name="attendances[${id}][status]"
                                   id="status_sick_${id}"
                                   value="Sick">
                            <label class="btn btn-sm btn-outline-warning"
                                   for="status_sick_${id}">
                                   Sick
                            </label>

                            <input type="radio" class="btn-check"
                                   name="attendances[${id}][status]"
                                   id="status_permit_${id}"
                                   value="Permit">
                            <label class="btn btn-sm btn-outline-primary"
                                   for="status_permit_${id}">
                                   Permit
                            </label>

                            <input type="radio" class="btn-check"
                                   name="attendances[${id}][status]"
                                   id="status_none_${id}"
                                   value="No Information">
                            <label class="btn btn-sm btn-outline-secondary"
                                   for="status_none_${id}">
                                   No Info
                            </label>
                        </div>

                        <input type="hidden"
                               name="attendances[${id}][student_class_id]"
                               value="${id}">
                    </td>
                    <td>
                        <input type="text"
                               id="notes-${id}"
                               name="attendances[${id}][notes]"
                               class="form-control form-control-sm"
                               placeholder="Notes (optional)">
                    </td>
                </tr>`;
        });

        html += `</tbody></table>`;

        studentsList.innerHTML = html;
        container.style.display = 'block';
        submitBtn.disabled = false;
    }

    document.getElementById('class_id').addEventListener('change', function() {
        const classId = this.value;

        if (!classId) {
            container.style.display = 'none';
            submitBtn.disabled = true;
            return;
        }

        studentsList.innerHTML =
            '<p class="text-muted text-center">Loading students...</p>';

        fetch(`/api/attendance/students/${classId}`)
            .then(res => res.json())
            .then(data => renderStudentsTable(data))
            .catch(() => {
                studentsList.innerHTML =
                    '<p class="text-danger">Error loading students</p>';
            });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const pre = document.getElementById('class_id').value;
        if (pre) {
            document.getElementById('class_id').dispatchEvent(new Event('change'));
        }
    });
</script>
@endsection
