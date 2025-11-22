@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Record Attendance by Class</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Validation Errors</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form id="attendance-form" action="{{ route('employee.attendances.store') }}" method="POST">
                        @csrf

                        <div id="ajax-alert"></div>

                        <div class="mb-3">
                            <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                            <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                                <option value="">-- Select Class --</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->class_id }}" {{ old('class_id') == $class->class_id ? 'selected' : '' }}>
                                        {{ $class->class_name }} (Level {{ $class->class_level }})
                                    </option>
                                @endforeach
                            </select>
                            @error('class_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="attendance_date" class="form-label">Attendance Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('attendance_date') is-invalid @enderror" 
                                   id="attendance_date" name="attendance_date" value="{{ old('attendance_date', date('Y-m-d')) }}" required>
                            @error('attendance_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                            <select class="form-select @error('teacher_id') is-invalid @enderror" id="teacher_id" name="teacher_id" required>
                                <option value="">-- Select Teacher --</option>
                                @foreach ($teachers as $t)
                                    <option value="{{ $t->teacher_id }}" {{ old('teacher_id') == $t->teacher_id ? 'selected' : '' }}>
                                        {{ $t->first_name }} {{ $t->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('teacher_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Students list will be loaded here via AJAX -->
                        <div id="students-container" class="mb-3" style="display: none;">
                            <label class="form-label">Student Attendance <span class="text-danger">*</span></label>
                            <div id="students-list" class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                <p class="text-muted text-center">Select a class to load students</p>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success" id="submit-btn" disabled>
                                <i class="fas fa-save"></i> Save Attendance
                            </button>
                            <a href="{{ route('employee.attendances.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const container = document.getElementById('students-container');
    const studentsList = document.getElementById('students-list');
    const submitBtn = document.getElementById('submit-btn');

    function renderStudentsTable(students) {
        if (!students || students.length === 0) {
            studentsList.innerHTML = '<p class="text-muted text-center">No students in this class</p>';
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
                        <th style="width:30%">Absence</th>
                        <th style="width:20%">Notes</th>
                    </tr>
                </thead>
                <tbody>`;

        students.forEach(student => {
            const id = student.student_class_id;
            const nis = student.student_id;
            const name = `${student.first_name} ${student.last_name}`;

                    html += `
                <tr>
                    <td><span class="badge bg-info">${nis}</span></td>
                    <td>${name}</td>
                    <td>
                        <div class="btn-group" role="group" aria-label="absence-${id}">
                            <input type="radio" class="btn-check" name="attendances[${id}][status]" id="status_present_${id}" value="Present" autocomplete="off" checked>
                            <label class="btn btn-sm btn-outline-success" for="status_present_${id}">Present</label>

                            <input type="radio" class="btn-check" name="attendances[${id}][status]" id="status_sick_${id}" value="Sick" autocomplete="off">
                            <label class="btn btn-sm btn-outline-warning" for="status_sick_${id}">Sick</label>

                            <input type="radio" class="btn-check" name="attendances[${id}][status]" id="status_permit_${id}" value="Permit" autocomplete="off">
                            <label class="btn btn-sm btn-outline-primary" for="status_permit_${id}">Permit</label>

                            <input type="radio" class="btn-check" name="attendances[${id}][status]" id="status_none_${id}" value="No Information" autocomplete="off">
                            <label class="btn btn-sm btn-outline-secondary" for="status_none_${id}">No Information</label>
                        </div>
                        <input type="hidden" name="attendances[${id}][student_class_id]" value="${id}">
                    </td>
                    <td>
                        <input type="text" id="notes-${id}" name="attendances[${id}][notes]" class="form-control form-control-sm" placeholder="Notes">
                    </td>
                </tr>`;
        });

        html += `</tbody></table>`;

        studentsList.innerHTML = html;
        container.style.display = 'block';
        submitBtn.disabled = false;

        // Attach change listeners to toggle notes required state when status changes
        students.forEach(student => {
            const id = student.student_class_id;
            ['present','sick','permit','none'].forEach(kind => {
                const el = document.getElementById(`status_${kind}_${id}`);
                if (el) {
                    el.addEventListener('change', function() {
                        const notes = document.getElementById('notes-' + id);
                        if (this.value === 'Present') {
                            // Present: notes optional
                            notes.required = false;
                            notes.placeholder = 'Notes (optional)';
                        } else {
                            // Non-present: notes required
                            notes.required = true;
                            notes.placeholder = 'Notes (required)';
                        }
                    });
                }
            });

            // Initialize required state based on default checked (Present is default)
            const initialNotes = document.getElementById('notes-' + id);
            if (initialNotes) {
                // default status is Present -> not required
                initialNotes.required = false;
                initialNotes.placeholder = 'Notes (optional)';
            }
        });
    }

    document.getElementById('class_id').addEventListener('change', function() {
        const classId = this.value;
        if (!classId) {
            container.style.display = 'none';
            submitBtn.disabled = true;
            return;
        }

        studentsList.innerHTML = '<p class="text-muted text-center">Loading students...</p>';
        fetch(`/api/attendance/students/${classId}`, { credentials: 'same-origin' })
            .then(async response => {
                if (!response.ok) {
                    const txt = await response.text();
                    throw new Error('Server returned ' + response.status + ': ' + (txt.substring(0, 200)));
                }
                return response.json();
            })
            .then(students => renderStudentsTable(students))
            .catch(error => {
                studentsList.innerHTML = `<p class="text-danger">Error loading students: ${error.message}</p>`;
                submitBtn.disabled = true;
                container.style.display = 'block';
            });
    });

    // Auto-load if a class was pre-selected (old input or preset)
    document.addEventListener('DOMContentLoaded', function() {
        const pre = document.getElementById('class_id').value;
        if (pre) {
            // trigger change to load students for pre-selected class
            document.getElementById('class_id').dispatchEvent(new Event('change'));
        }
    });

    // AJAX submit handler
    (function() {
        const form = document.getElementById('attendance-form');
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const alertBox = document.getElementById('ajax-alert');
            alertBox.innerHTML = '';

            // simple client-side validation for required notes (browser will also enforce required)
            // prepare FormData (works with array inputs)
            const formData = new FormData(form);

            // disable submit button
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            try {
                const token = formData.get('_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const res = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (res.status === 422) {
                    const json = await res.json();
                    const errors = json.errors || {};
                    let html = '<div class="alert alert-danger">\n<ul class="mb-0">';
                    for (const key in errors) {
                        errors[key].forEach(msg => {
                            html += `<li>${msg}</li>`;
                        });
                    }
                    html += '</ul></div>';
                    alertBox.innerHTML = html;
                    // scroll to alert
                    alertBox.scrollIntoView({ behavior: 'smooth' });
                    return;
                }

                if (!res.ok) {
                    const txt = await res.text();
                    alertBox.innerHTML = `<div class="alert alert-danger">Server error: ${res.status}</div>`;
                    console.error('Save error:', res.status, txt);
                    return;
                }

                const json = await res.json();
                // Show success and redirect to index
                alertBox.innerHTML = `<div class="alert alert-success">${json.message || 'Saved'}</div>`;
                // small delay then redirect
                setTimeout(() => {
                    window.location.href = '{{ route('employee.attendances.index') }}';
                }, 900);

            } catch (err) {
                alertBox.innerHTML = `<div class="alert alert-danger">Network or server error. See console for details.</div>`;
                console.error(err);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    })();
</script>
@endsection
