@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Student Management')

@section('content')

<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-users text-primary"></i> Students List
            </h5>
            <a href="{{ route('employee.students.create') }}" 
               class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-plus"></i> Add New Student
            </a>
        </div>

        <!-- BODY -->
        <div class="card-body px-4">

            <!-- SUCCESS ALERT -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- DATATABLE WRAPPER -->
            <div class="bg-white border rounded p-3 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle" id="students-table">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Photo</th>
                                <th>Student ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>NIS</th>
                                <th>Gender</th>
                                <th>Birth Place</th>
                                <th>Birth Date</th>
                                <th>Address</th>
                                <th>Father Name</th>
                                <th>Mother Name</th>
                                <th>Father Phone</th>
                                <th>Mother Phone</th>
                                <th>Father Job</th>
                                <th>Mother Job</th>
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
</div>

@endsection

@push('scripts')
<script>
$(function() {

    $('#students-table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: "{{ route('employee.students.data') }}",
        columns: [
            {
                data: 'profile_photo',
                orderable: false,
                render: function(d){
                    if (!d)
                        return '<img src="/image/default.png" width="60" height="60" class="rounded-circle border shadow-sm" />';

                    return `<img src="${(d.startsWith('http') ? d : '/storage/' + d)}"
                                 width="60" height="60"
                                 class="rounded-circle border shadow-sm"
                                 style="object-fit:cover;" />`;
                }
            },
            { data: 'student_id' },
            { data: 'first_name' },
            { data: 'last_name' },
            { data: 'nis' },
            {
                data: 'gender',
                render: function(d){ 
                    return d === 'M' ? 'Male' : (d === 'F' ? 'Female' : '-'); 
                }
            },
            { data: 'birth_place', defaultContent: '-' },
            {
                data: 'birth_date',
                render: function(d){ 
                    return d ? moment(d).format('DD MMM YYYY') : '-'; 
                }
            },
            { data: 'address', defaultContent: '-' },
            { data: 'father_name', defaultContent: '-' },
            { data: 'mother_name', defaultContent: '-' },
            { data: 'father_phone', defaultContent: '-' },
            { data: 'mother_phone', defaultContent: '-' },
            { data: 'father_job', defaultContent: '-' },
            { data: 'mother_job', defaultContent: '-' },
            {
                data: 'entry_date',
                render: function(d){ 
                    return d ? moment(d).format('DD MMM YYYY') : '-'; 
                }
            },
            { data: 'status' },
            {
                data: 'student_id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(id) {
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light shadow-sm border dropdown-toggle" 
                                    type="button" 
                                    data-bs-toggle="dropdown">
                                ⋮
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="/students/edit/${id}">
                                        <i class="fas fa-edit text-primary"></i> Edit
                                    </a>
                                </li>
                                <li>
                                    <button class="dropdown-item text-danger btn-delete"
                                            data-id="${id}">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </li>
                            </ul>
                        </div>
                    `;
                }
            }
        ]
    });

    // DELETE ACTION
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');

        if (!confirm('Delete this student?')) return;

        $.ajax({
            url: `/students/${id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                $('#students-table').DataTable().ajax.reload();
            },
            error: function() {
                alert('Failed to delete student');
            }
        });
    });

});
</script>
@endpush
