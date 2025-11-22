@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-edit"></i> Edit Academic Class</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.academic_classes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Academic Classes
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('employee.academic_classes.update', $academicClass->academic_class_id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Academic Class ID</label>
                        <input type="text" class="form-control" value="{{ $academicClass->academic_class_id }}" disabled>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="academic_year_id" class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select class="form-select @error('academic_year_id') is-invalid @enderror" 
                                id="academic_year_id" name="academic_year_id" required>
                            <option value="">-- Select Academic Year --</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->academic_year_id }}" {{ old('academic_year_id', $academicClass->academic_year_id) == $year->academic_year_id ? 'selected' : '' }}>
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
                                <option value="{{ $class->class_id }}" {{ old('class_id', $academicClass->class_id) == $class->class_id ? 'selected' : '' }}>
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
                                <option value="{{ $teacher->employee_id }}" {{ old('homeroom_teacher_id', $academicClass->homeroom_teacher_id) == $teacher->employee_id ? 'selected' : '' }}>
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
                <h5>Assigned Students</h5>
                <p class="text-muted">Select students for this academic class. Students already assigned to another class in the same academic year are disabled.</p>
                <div class="mb-3">
                    <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                        @forelse ($students as $student)
                            @php
                                $sid = $student->student_id;
                                $isAssignedThis = in_array($sid, $assignedThis ?? []);
                                $assignedTo = $assignedMap[$sid] ?? null;
                            @endphp
                            <div class="form-check">
                                <input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]"
                                       value="{{ $sid }}" id="student_{{ $sid }}"
                                       {{ $isAssignedThis ? 'checked' : '' }}
                                       {{ ($assignedTo && $assignedTo !== $academicClass->academic_class_id) ? 'disabled' : '' }}>
                                <label class="form-check-label {{ ($assignedTo && $assignedTo !== $academicClass->academic_class_id) ? 'text-muted' : '' }}" for="student_{{ $sid }}">
                                    {{ $student->first_name }} {{ $student->last_name }} <small class="text-muted">({{ $sid }})</small>
                                    @if($assignedTo && $assignedTo !== $academicClass->academic_class_id)
                                        <small class="text-danger"> — already assigned ({{ $assignedTo }})</small>
                                    @endif
                                </label>
                            </div>
                        @empty
                            <p class="text-muted">No active students available</p>
                        @endforelse
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <a href="{{ route('employee.academic_classes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
