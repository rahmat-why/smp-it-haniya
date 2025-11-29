@extends('layouts.app')

@section('title', 'Subjects')
@section('page-title', 'Manage Subjects')

@section('content')
<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-book text-primary"></i> Subjects
            </h5>
            <a href="{{ route('employee.subjects.create') }}" class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-plus"></i> Add New Subject
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
                <table id="subjects-table" class="table table-hover table-bordered align-middle w-100">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Subject Name</th>
                            <th>Subject Code</th>
                            <th>Class Level</th>
                            <th>Description</th>
                            <th width="100" class="text-center">Actions</th>
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
$(function(){
    $('#subjects-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: "{{ route('employee.subjects.data') }}",
        columns: [
           
            { data: 'subject_name', name: 'subject_name' },
            { data: 'subject_code', name: 'subject_code' },
            { data: 'class_level', name: 'class_level' },
            { data: 'description', render: function(d){ return d ? d.substring(0,50) : '-'; } },
            {
                data: 'subject_id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(id){
                    let editUrl = "{{ url('subjects/edit') }}/" + id;
                    let deleteUrl = "{{ url('subjects') }}/" + id;
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light shadow-sm border dropdown-toggle" type="button" data-bs-toggle="dropdown">⋮</button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="${editUrl}">
                                        <i class="fas fa-edit text-primary"></i> Edit
                                    </a>
                                </li>
                                <li>
                                    <form action="${deleteUrl}" method="POST" class="d-inline" onsubmit="return confirm('Delete this subject?')">
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
        order: [[0,'desc']]
    });
});
</script>
@endpush
