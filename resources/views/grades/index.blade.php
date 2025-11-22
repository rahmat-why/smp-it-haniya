@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Grade Records</h5>
                    <a href="{{ route('employee.grades.create') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Record Grades
                    </a>
                </div>
                <div class="card-body">
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Type</th>
                                    <th>Grade</th>
                                    <th>Attitude</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($grades as $grade)
                                    <tr>
                                        <td>
                                            <strong>{{ $grade->first_name }} {{ $grade->last_name }}</strong>
                                            <br><small class="text-muted">{{ $grade->class_name }}</small>
                                        </td>
                                        <td>{{ $grade->subject_name }}</td>
                                        <td>{{ $grade->teacher_name }}</td>
                                        <td><span class="badge bg-secondary">{{ $grade->grade_type }}</span></td>
                                        <td>
                                            <span class="badge bg-info">{{ $grade->grade_value }}</span>
                                        </td>
                                        <td>{{ $grade->grade_attitude ?? '-' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('employee.grades.edit', $grade->grade_id) }}" 
                                                   class="btn btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('employee.grades.destroy', $grade->grade_id) }}" 
                                                      method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="Delete"
                                                            onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"></i> No grade records found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
