@extends('layouts.app')

@section('title', 'Student Class Assignments')
@section('page-title', 'Manage Student Class Assignments')

@section('content')
<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-users text-primary"></i> Student Class Assignments
            </h5>
            <a href="{{ route('employee.student_classes.create') }}" class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-plus"></i> Assign Students to Class
            </a>
        </div>

        <!-- BODY -->
        <div class="card-body px-4">

            <!-- SUCCESS & ERROR ALERT -->
            @if ($message = Session::get('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle"></i> {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($message = Session::get('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-times-circle"></i> {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- DATATABLE WRAPPER -->
            <div class="table-responsive">
                <table id="studentClassesTable" class="table table-hover table-bordered align-middle w-100">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Academic Year</th>
                            <th width="120" class="text-center">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#studentClassesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: "{{ route('employee.student_classes.data') }}",
        columns: [
           
            { data: 'student_name', name: 'student_name' },
            { data: 'class_display', name: 'class_display' },
            { data: 'academic_display', name: 'academic_display' },
            {
                data: 'student_class_id', orderable: false, searchable: false, className: 'text-center',
                render: function(id) {
                    // Sesuaikan route edit sesuai /edit/{id}
                    let editUrl = "{{ url('student-classes/edit') }}/" + id;
                    let deleteUrl = "{{ url('student-classes') }}/" + id;

                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light shadow-sm border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                ⋮
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="${editUrl}">
                                        <i class="fas fa-edit text-primary"></i> Edit
                                    </a>
                                </li>
                                <li>
                                    <form action="${deleteUrl}" method="POST" class="d-inline" onsubmit="return confirm('Delete this assignment?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']]
    });
});
</script>
@endpush
