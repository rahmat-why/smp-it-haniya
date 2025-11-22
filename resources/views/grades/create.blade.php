@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Record Grades for Class</h5>
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

                    <form action="{{ route('employee.grades.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                                <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                                    <option value="">-- Select Class --</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->class_id }}" {{ old('class_id') == $class->class_id ? 'selected' : '' }}>
                                            {{ $class->class_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_id')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                                <select class="form-select @error('subject_id') is-invalid @enderror" id="subject_id" name="subject_id" required>
                                    <option value="">-- Select Subject --</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->subject_id }}" {{ old('subject_id') == $subject->subject_id ? 'selected' : '' }}>
                                            {{ $subject->subject_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_id')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                                <select class="form-select @error('teacher_id') is-invalid @enderror" id="teacher_id" name="teacher_id" required>
                                    <option value="">-- Select Teacher --</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->teacher_id }}" {{ old('teacher_id') == $teacher->teacher_id ? 'selected' : '' }}>
                                            {{ $teacher->first_name }} {{ $teacher->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('teacher_id')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="grade_type" class="form-label">Grade Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('grade_type') is-invalid @enderror" id="grade_type" name="grade_type" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="Midterm" {{ old('grade_type') == 'Midterm' ? 'selected' : '' }}>Midterm</option>
                                    <option value="Final" {{ old('grade_type') == 'Final' ? 'selected' : '' }}>Final</option>
                                    <option value="Assignment" {{ old('grade_type') == 'Assignment' ? 'selected' : '' }}>Assignment</option>
                                    <option value="Practical" {{ old('grade_type') == 'Practical' ? 'selected' : '' }}>Practical</option>
                                </select>
                                @error('grade_type')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- Students list will be loaded here via AJAX -->
                        <div id="students-container" class="mb-3" style="display: none;">
                            <label class="form-label">Student Grades <span class="text-danger">*</span></label>
                            <div class="table-responsive">
                                <table class="table table-sm" id="grades-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Grade (0-100)</th>
                                            <th>Attitude</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="students-list"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success" id="submit-btn" disabled>
                                <i class="fas fa-save"></i> Save Grades
                            </button>
                            <a href="{{ route('employee.grades.index') }}" class="btn btn-secondary">
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
    document.getElementById('class_id').addEventListener('change', loadStudents);

    function loadStudents() {
        const classId = document.getElementById('class_id').value;
        const container = document.getElementById('students-container');
        const studentsList = document.getElementById('students-list');
        const submitBtn = document.getElementById('submit-btn');

        if (!classId) {
            container.style.display = 'none';
            submitBtn.disabled = true;
            return;
        }

        fetch(`/employee/api/grades/students/${classId}`)
            .then(response => response.json())
            .then(students => {
                if (students.length === 0) {
                    studentsList.innerHTML = '<tr><td colspan="4" class="text-muted text-center">No students in this class</td></tr>';
                    submitBtn.disabled = true;
                    return;
                }

                let html = '';
                students.forEach(student => {
                    html += `
                        <tr>
                            <td>
                                ${student.first_name} ${student.last_name}
                                <input type="hidden" name="grades[${student.student_class_id}][student_class_id]" value="${student.student_class_id}">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" 
                                       name="grades[${student.student_class_id}][grade_value]" 
                                       min="0" max="100" step="0.01" required 
                                       placeholder="0-100">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" 
                                       name="grades[${student.student_class_id}][grade_attitude]" 
                                       placeholder="e.g., Excellent" maxlength="255">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" 
                                       name="grades[${student.student_class_id}][notes]" 
                                       placeholder="Notes (optional)" maxlength="500">
                            </td>
                        </tr>
                    `;
                });

                studentsList.innerHTML = html;
                container.style.display = 'block';
                submitBtn.disabled = false;
            })
            .catch(error => {
                studentsList.innerHTML = `<tr><td colspan="4" class="text-danger">Error loading students: ${error.message}</td></tr>`;
                submitBtn.disabled = true;
            });
    }
</script>
@endsection
