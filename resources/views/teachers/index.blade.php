@extends('layouts.app')

@section('title', 'Teachers')
@section('page-title', 'Teacher Management')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-chalkboard-user"></i> Teachers List</h5>
        <a href="{{ route('employee.teachers.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add New Teacher
        </a>
    </div>
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle" id="teachers-table">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Photo</th>
                        <th>Teacher ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>NPK</th>
                        <th>Phone</th>
                        <th>Level</th>
                        <th>Birth Place</th>
                        <th>Birth Date</th>
                        <th>Address</th>
                        <th>Entry Date</th>
                        <th>Status</th>
                        <th>#</th>
                    </tr>
                </thead>
            </table>
        </div>

        @push('scripts')
        <script>
        $(function() {
            $('#teachers-table').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: "{{ route('employee.teachers.data') }}",
                columns: [
                    { data: 'profile_photo', orderable: false, render: function(d){
                        if (!d) return '<img src="/image/default.png" width="60" height="60" class="rounded-circle" />';
                        return '<img src="'+(d.indexOf && d.indexOf('http')===0?d:('/storage/'+d))+'" width="60" height="60" class="rounded-circle" style="object-fit:cover;" />';
                    } },
                    { data: 'teacher_id' },
                    { data: 'first_name' },
                    { data: 'last_name' },
                    { data: 'npk' },
                    { data: 'phone', defaultContent: '-' },
                    { data: 'level' },
                    { data: 'birth_place', defaultContent: '-' },
                    { data: 'birth_date', render: function(d){ return d ? moment(d).format('DD MMM YYYY') : '-'; } },
                    { data: 'address', defaultContent: '-' },
                    { data: 'entry_date', render: function(d){ return d ? moment(d).format('DD MMM YYYY') : '-'; } },
                    { data: 'status' },
                    {
                        data: 'teacher_id', orderable: false, searchable: false, className: 'text-center',
                        render: function(id) {
                            return `
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">⋮</button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="/teachers/edit/${id}">Edit</a></li>
                                        <li><button class="dropdown-item text-danger btn-delete" data-id="${id}">Delete</button></li>
                                    </ul>
                                </div>
                            `;
                        }
                    }
                ]
            });

            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                if (!confirm('Delete this teacher?')) return;
                $.ajax({ url: `/teachers/${id}`, type: 'DELETE', data: { _token: '{{ csrf_token() }}' },
                    success: function(){ $('#teachers-table').DataTable().ajax.reload(); },
                    error: function(){ alert('Failed to delete teacher'); }
                });
            });
        });
        </script>
        @endpush
    </div>
</div>
@endsection
