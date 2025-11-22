@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Student Management')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-book"></i> Students List</h5>
        <a href="{{ route('employee.students.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add New Student
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
            <table class="table table-bordered table-striped table-hover align-middle" id="students-table">
                <thead class="table-dark text-center">
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
                        <th>#</th>
                    </tr>
                </thead>
            </table>
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
            { data: 'profile_photo', orderable: false, render: function(d){
                    if (!d) return '<img src="/image/default.png" width="60" height="60" class="rounded-circle" />';
                    return '<img src="'+(d.indexOf && d.indexOf('http')===0?d:('/storage/'+d))+'" width="60" height="60" class="rounded-circle" style="object-fit:cover;" />';
                } },
            { data: 'student_id' },
            { data: 'first_name' },
            { data: 'last_name' },
            { data: 'nis' },
            { data: 'gender', render: function(d){ return d === 'M' ? 'Male' : (d === 'F' ? 'Female' : '-'); } },
            { data: 'birth_place', defaultContent: '-' },
            { data: 'birth_date', render: function(d){ return d ? moment(d).format('DD MMM YYYY') : '-'; } },
            { data: 'address', defaultContent: '-' },
            { data: 'father_name', defaultContent: '-' },
            { data: 'mother_name', defaultContent: '-' },
            { data: 'father_phone', defaultContent: '-' },
            { data: 'mother_phone', defaultContent: '-' },
            { data: 'father_job', defaultContent: '-' },
            { data: 'mother_job', defaultContent: '-' },
            { data: 'entry_date', render: function(d){ return d ? moment(d).format('DD MMM YYYY') : '-'; } },
            { data: 'status' },
            {
                data: 'student_id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(id) {
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">⋮</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/students/edit/${id}">Edit</a></li>
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
        if (!confirm('Delete this student?')) return;
        $.ajax({
            url: `/students/${id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function() { $('#students-table').DataTable().ajax.reload(); },
            error: function() { alert('Failed to delete student'); }
        });
    });
});
</script>
@endpush
