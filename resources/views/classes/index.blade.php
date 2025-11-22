@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-chalkboard"></i> Classes</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.classes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Class
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
            <table class="table table-bordered table-striped table-hover align-middle" id="classes-table">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Class ID</th>
                        <th>Class Name</th>
                        <th>Class Level</th>
                        <th>#</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @push('scripts')
    <script>
    $(function(){
        $('#classes-table').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            ajax: "{{ route('employee.classes.data') }}",
            columns: [
                { data: 'class_id' },
                { data: 'class_name' },
                { data: 'class_level' },
                { data: 'class_id', orderable: false, searchable: false, className: 'text-center', render: function(id){
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">⋮</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/classes/edit/${id}">Edit</a></li>
                                <li><button class="dropdown-item text-danger btn-delete" data-id="${id}">Delete</button></li>
                            </ul>
                        </div>
                    `;
                }}
            ]
        });

        $(document).on('click', '.btn-delete', function(){
            const id = $(this).data('id');
            if (!confirm('Delete this class?')) return;
            $.ajax({ url: `/classes/${id}`, type: 'DELETE', data: { _token: '{{ csrf_token() }}' }, success: function(){ $('#classes-table').DataTable().ajax.reload(); }, error: function(){ alert('Failed to delete class'); } });
        });
    });
    </script>
    @endpush
</div>
@endsection
