@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-eye"></i> Academic Class Detail</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.academic_classes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Academic Classes
            </a>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Academic Class Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><strong>Academic Class ID</strong></label>
                        <p class="form-control-plaintext">{{ $academicClass->academic_class_id }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Academic Year</strong></label>
                        <p class="form-control-plaintext">
                            {{ $academicClass->academic_year_id }}
                            ({{ $academicClass->start_date ? \Carbon\Carbon::parse($academicClass->start_date)->format('Y') : '' }}-{{ $academicClass->end_date ? \Carbon\Carbon::parse($academicClass->end_date)->format('Y') : '' }})
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Class</strong></label>
                        <p class="form-control-plaintext">
                            {{ $academicClass->class_name }} (Level {{ $academicClass->class_level }})
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Homeroom Teacher</strong></label>
                        <p class="form-control-plaintext">
                            {{ trim(($academicClass->first_name ?? '') . ' ' . ($academicClass->last_name ?? '')) ?: '-' }}
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('employee.academic_classes.edit', $academicClass->academic_class_id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form action="{{ route('employee.academic_classes.destroy', $academicClass->academic_class_id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Student Assignments ({{ count($students) }})</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    @forelse ($students as $student)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>
                                    <div class="small text-muted">{{ $student->student_id }}</div>
                                </div>
                                <form action="{{ route('employee.student_classes.destroy', $student->student_class_id) }}" 
                                      method="POST" style="display:inline;" 
                                      onsubmit="return confirm('Remove this student?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4">No students assigned to this class yet.</p>
                    @endforelse
                </div>
                <div class="card-footer bg-light">
                    <a href="{{ route('employee.student_classes.create', ['academic_class_id' => $academicClass->academic_class_id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add Students
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
