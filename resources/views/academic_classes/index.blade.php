@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-book"></i> Academic Classes</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.academic_classes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Academic Class
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
            <table id="academicClassesTable" class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Academic Class ID</th>
                        <th>Academic Year</th>
                        <th>Class</th>
                        <th>Homeroom Teacher</th>
                        <th width="200">Actions</th>
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
        // Base URLs for building action links/forms
        var editBase = '{{ url("academic-classes/edit") }}';
        var deleteBase = '{{ url("academic-classes") }}';
        var csrfToken = '{{ csrf_token() }}';

        $('#academicClassesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('employee.academic_classes.data') }}',
                type: 'GET'
            },
            columns: [
                { data: 'academic_class_id', name: 'academic_class_id' },
                { data: 'academic_year_display', name: 'academic_year_display' },
                { data: 'class_display', name: 'class_display' },
                { data: 'teacher_name', name: 'teacher_name' },
                {
                    data: null,
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        // Build Edit button and Delete form; Detail was removed per controller change
                        var editUrl = editBase + '/' + row.academic_class_id;
                        var deleteUrl = deleteBase + '/' + row.academic_class_id;

                        var editBtn = "<a href='" + editUrl + "' class='btn btn-sm btn-warning me-1'><i class='fas fa-edit'></i></a>";

                        var form = "<form action='" + deleteUrl + "' method='POST' style='display:inline;' onsubmit=\"return confirm('Are you sure?');\">";
                        form += "<input type='hidden' name='_token' value='" + csrfToken + "'>";
                        form += "<input type='hidden' name='_method' value='DELETE'>";
                        form += "<button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button></form>";

                        return editBtn + form;
                    }
                }
            ],
            order: [[0, 'desc']]
        });
    });
</script>
@endpush
@endsection
