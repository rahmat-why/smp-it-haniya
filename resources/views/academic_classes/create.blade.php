@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-plus-circle"></i> Create New Academic Class</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.academic_classes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Academic Classes
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('employee.academic_classes.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="academic_class_id" class="form-label">Academic Class ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('academic_class_id') is-invalid @enderror" 
                               id="academic_class_id" name="academic_class_id" value="{{ old('academic_class_id') }}" required>
                        @error('academic_class_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="academic_year_id" class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select class="form-select @error('academic_year_id') is-invalid @enderror" 
                                id="academic_year_id" name="academic_year_id" required>
                            <option value="">-- Select Academic Year --</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->academic_year_id }}" {{ old('academic_year_id') == $year->academic_year_id ? 'selected' : '' }}>
                                    {{ $year->academic_year_id }} - Semester {{ $year->semester }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_year_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                        <select class="form-select @error('class_id') is-invalid @enderror" 
                                id="class_id" name="class_id" required>
                            <option value="">-- Select Class --</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->class_id }}" {{ old('class_id') == $class->class_id ? 'selected' : '' }}>
                                    {{ $class->class_name }} (Level {{ $class->class_level }})
                                </option>
                            @endforeach
                        </select>
                        @error('class_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="homeroom_teacher_id" class="form-label">Homeroom Teacher</label>
                        <select class="form-select @error('homeroom_teacher_id') is-invalid @enderror" 
                                id="homeroom_teacher_id" name="homeroom_teacher_id">
                            <option value="">-- Select Teacher --</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->employee_id }}" {{ old('homeroom_teacher_id') == $teacher->employee_id ? 'selected' : '' }}>
                                    {{ $teacher->first_name }} {{ $teacher->last_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('homeroom_teacher_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                    <hr />
                    <h5>Assign Students to this Academic Class</h5>
                    <p class="text-muted">Select students to add to this academic class. Students already assigned to another class for the same academic year will be disabled.</p>
                    <div class="mb-3">
                        <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                            @forelse ($students as $student)
                                <div class="form-check">
                                    <input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]"
                                           value="{{ $student->student_id }}" id="student_{{ $student->student_id }}">
                                    <label class="form-check-label" for="student_{{ $student->student_id }}">
                                        {{ $student->first_name }} {{ $student->last_name }} <small class="text-muted">({{ $student->student_id }})</small>
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">No active students available</p>
                            @endforelse
                        </div>
                    </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Academic Class
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
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('academic_class_id');
    if (input && !input.value) {
        fetch('{{ route('employee.academic_classes.getNewId') }}')
            .then(res => res.json())
            .then(data => {
                if (data.academic_class_id) input.value = data.academic_class_id;
            }).catch(err => console.error(err));
    }

    // When academic year changes, fetch student IDs already assigned in that year
    const $year = document.getElementById('academic_year_id');
    if ($year) {
        $year.addEventListener('change', function() {
            const yearId = this.value;
            const url = '{{ url('academic-classes/api/assigned-students') }}' + '/' + encodeURIComponent(yearId);
            if (!yearId) {
                // enable all checkboxes
                document.querySelectorAll('.student-checkbox').forEach(cb => {
                    cb.disabled = false;
                    cb.parentElement.classList.remove('text-muted');
                    cb.title = '';
                });
                return;
            }

            fetch(url)
                .then(res => res.json())
                .then(assignedIds => {
                    const assignedSet = new Set(assignedIds);
                    document.querySelectorAll('.student-checkbox').forEach(cb => {
                        const val = cb.value;
                        if (assignedSet.has(val)) {
                            cb.disabled = true;
                            cb.checked = false;
                            cb.title = 'Already assigned in this academic year';
                            cb.parentElement.classList.add('text-muted');
                        } else {
                            cb.disabled = false;
                            cb.title = '';
                            cb.parentElement.classList.remove('text-muted');
                        }
                    });
                }).catch(err => {
                    console.error('Failed to load assigned students', err);
                });
        });

        // If a year was preselected (old input), trigger change to apply disabling
        if ($year.value) {
            $year.dispatchEvent(new Event('change'));
        }
    }
});
</script>
@endpush
@endsection
