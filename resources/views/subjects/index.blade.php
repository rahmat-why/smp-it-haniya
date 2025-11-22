@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-book"></i> Subjects</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.subjects.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Subject
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
            <table class="table table-bordered table-striped table-hover align-middle" id="subjects-table">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Subject ID</th>
                        <th>Subject Name</th>
                        <th>Subject Code</th>
                        <th>Class Level</th>
                        <th>Description</th>
                        <th>#</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @push('scripts')
    <script>
    $(function(){
        $('#subjects-table').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            ajax: "{{ route('employee.subjects.data') }}",
            columns: [
                { data: 'subject_id' },
                { data: 'subject_name' },
                { data: 'subject_code' },
                { data: 'class_level' },
                { data: 'description', render: function(d){ return d ? d.substring(0,50) : '-'; } },
                { data: 'subject_id', orderable: false, searchable: false, className: 'text-center', render: function(id){
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">⋮</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/subjects/edit/${id}">Edit</a></li>
                                <li><button class="dropdown-item text-danger btn-delete" data-id="${id}">Delete</button></li>
                            </ul>
                        </div>
                    `;
                }}
            ]
        });

        $(document).on('click', '.btn-delete', function(){
            const id = $(this).data('id');
            if (!confirm('Delete this subject?')) return;
            $.ajax({ url: `/subjects/${id}`, type: 'DELETE', data: { _token: '{{ csrf_token() }}' }, success: function(){ $('#subjects-table').DataTable().ajax.reload(); }, error: function(){ alert('Failed to delete subject'); } });
        });
    });
    </script>
    @endpush
</div>
@endsection
