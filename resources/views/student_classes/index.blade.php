@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-users"></i> Student Class Assignments</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.student_classes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Assign Students to Class
            </a>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($message = Session::get('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table id="studentClassesTable" class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Assignment ID</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Academic Year</th>
                        <th width="160">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#studentClassesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('employee.student_classes.data') }}',
                type: 'GET'
            },
            columns: [
                { data: 'student_class_id', name: 'student_class_id' },
                { data: 'student_name', name: 'student_name' },
                { data: 'class_display', name: 'class_display' },
                { data: 'academic_display', name: 'academic_display' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']]
        });
    });
</script>
@endpush
@endsection
