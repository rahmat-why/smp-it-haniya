@extends('layouts.app')

@section('title', 'Teachers')
@section('page-title', 'Teacher Management')

@section('content')

<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-chalkboard-user text-primary"></i> Teachers List
            </h5>

            <a href="{{ route('employee.teachers.create') }}" 
               class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-plus"></i> Add New Teacher
            </a>
        </div>

        <!-- BODY -->
        <div class="card-body px-4">

            <!-- SUCCESS & ERROR ALERT -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-times-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- DATATABLE WRAPPER -->
            <div class="bg-white border rounded p-3 shadow-sm table-responsive">
                <table class="table table-hover table-bordered align-middle w-100" id="teachersTable">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Photo</th>
                            
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
                            <th width="70" class="text-center">Action</th>
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
$(function() {

    let csrfToken = '{{ csrf_token() }}';

    $('#teachersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('employee.teachers.data') }}",
        columns: [
            { data: 'profile_photo', orderable: false, render: function(d){
                if (!d) return '<img src="/image/default.png" width="60" height="60" class="rounded-circle" />';
                return '<img src="'+(d.indexOf && d.indexOf('http')===0?d:('/storage/'+d))+'" width="60" height="60" class="rounded-circle" style="object-fit:cover;" />';
            }},
           
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
                            <button class="btn btn-sm btn-light shadow-sm border dropdown-toggle" type="button" data-bs-toggle="dropdown">⋮</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/teachers/edit/${id}">
                                    <i class="fas fa-edit text-primary"></i> Edit
                                </a></li>
                                <li><button class="dropdown-item text-danger btn-delete" data-id="${id}">
                                    <i class="fas fa-trash"></i> Delete
                                </button></li>
                            </ul>
                        </div>
                    `;
                }
            }
        ],
        order: [[1, 'desc']],
        autoWidth: false,
        responsive: true
    });

    // DELETE ACTION
    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        if (!confirm('Delete this teacher?')) return;

        $.ajax({
            url: `/teachers/${id}`,
            type: 'POST',
            data: {
                _token: csrfToken,
                _method: 'DELETE'
            },
            success: function () {
                $('#teachersTable').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                let message = "Failed to delete teacher";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                alert(message);
            }
        });
    });

});
</script>
@endpush
