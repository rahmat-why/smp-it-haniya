@extends('layouts.app')

@section('title', 'Grades')
@section('page-title', 'Grade Management')

@section('content')
<div class="container-fluid">
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 px-4">
            <h5 class="fw-bold mb-0">Grades</h5>
            <a href="{{ route('employee.grades.create') }}" class="btn btn-primary btn-sm">Add Grades</a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="gradesTable" class="table table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Teacher</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($grades as $g)
                        <tr>
                            <td>{{ $g->class_name }}</td>
                            <td>{{ $g->subject_name }}</td>
                            <td>{{ $g->grade_type }}</td>
                            <td>{{ $g->teacher_name }}</td>
                            <td>
                                <a href="{{ route('employee.grades.edit', $g->grade_id) }}" class="btn btn-sm btn-primary">
                                    Edit
                                </a>

                                <form action="{{ route('employee.grades.destroy', $g->grade_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Delete grade?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach

                        @if ($grades->count() == 0)
                        <tr>
                            <td colspan="5" class="text-center text-muted">No grades found.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $('#gradesTable').DataTable({
            "ordering": true,
            "searching": true,
            "paging": true,
            "lengthChange": true,
            "pageLength": 10,
        });
    });
</script>
@endsection
