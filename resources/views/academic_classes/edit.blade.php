@extends('layouts.app')

@section('title', 'Edit Academic Class')
@section('page-title', 'Edit Academic Class')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Academic Class</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('employee.academic_classes.update', $academicClass->academic_class_id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Section 1: Basic Information --}}
                    <h6 class="mb-3 text-primary">Class Information</h6>

                    <div class="row">
                       <div class="col-md-6 d-none">
                            <label class="form-label">Academic Class ID</label>
                            <input type="text" class="form-control" value="{{ $academicClass->academic_class_id }}" disabled>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="academic_year_id" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <select class="form-select @error('academic_year_id') is-invalid @enderror" 
                                    id="academic_year_id" name="academic_year_id" required>
                                <option value="">-- Select Academic Year --</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->academic_year_id }}" 
                                        {{ old('academic_year_id', $academicClass->academic_year_id) == $year->academic_year_id ? 'selected' : '' }}>
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
                                    <option value="{{ $class->class_id }}" 
                                        {{ old('class_id', $academicClass->class_id) == $class->class_id ? 'selected' : '' }}>
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
                                    <option value="{{ $teacher->employee_id }}" 
                                        {{ old('homeroom_teacher_id', $academicClass->homeroom_teacher_id) == $teacher->employee_id ? 'selected' : '' }}>
                                        {{ $teacher->first_name }} {{ $teacher->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('homeroom_teacher_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Section 2 --}}
                    <h6 class="mt-4 mb-3 text-primary">Assign Students</h6>
                    <p class="text-muted">Students already assigned to another class in the same year will be disabled.</p>

                    <div class="mb-3">
                        <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                            @forelse ($students as $student)
                                @php
                                    $sid = $student->student_id;
                                    $isAssignedThis = in_array($sid, $assignedThis ?? []);
                                    $assignedTo = $assignedMap[$sid] ?? null;
                                @endphp

                                <div class="form-check mb-1">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="student_ids[]"
                                           value="{{ $sid }}"
                                           id="student_{{ $sid }}"
                                           {{ $isAssignedThis ? 'checked' : '' }}
                                           {{ ($assignedTo && $assignedTo !== $academicClass->academic_class_id) ? 'disabled' : '' }}>

                                    <label class="form-check-label {{ ($assignedTo && $assignedTo !== $academicClass->academic_class_id) ? 'text-muted' : '' }}" 
                                           for="student_{{ $sid }}">
                                        {{ $student->first_name }} {{ $student->last_name }}
                                        <small class="text-muted">({{ $sid }})</small>

                                        @if ($assignedTo && $assignedTo !== $academicClass->academic_class_id)
                                            <small class="text-danger"> — already assigned ({{ $assignedTo }})</small>
                                        @endif
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">No active students available</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Class
                        </button>
                        <a href="{{ route('employee.academic_classes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>

                </form>
            </div>

        </div>

    </div>
</div>
@endsection
