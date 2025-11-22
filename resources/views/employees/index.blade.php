@extends('layouts.app')

@section('title', 'Employee')
@section('page-title', 'Employee')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Employees</h4>

            <a href="{{ route('employee.employees.create') }}" class="btn btn-primary">
                + Add Employee
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle" id="employees-table">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Profile Photo</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Username</th>
                        <th>Gender</th>
                        <th>Birth Place</th>
                        <th>Birth Date</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Entry Date</th>
                        <th>Level</th>
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
    $('#employees-table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
    ajax: "{{ route('employee.employees.data') }}",
        columns: [
            {
                data: 'profile_photo',
                render: function(data) {
                    if (!data) {
                        return '<img src="/image/default.png" width="60" height="60" class="rounded-circle">';
                    }
                    return `<img src="/storage/${data}" width="60" height="60" class="rounded-circle">`;
                },
                orderable: false,
                searchable: false
            },
            { data: 'first_name' },
            { data: 'last_name' },
            { data: 'username' },
            { data: 'gender' },
            { data: 'birth_place' },
            {
                data: 'birth_date',
                render: function(data) {
                    if (!data) return '-';
                    return moment(data).format('DD MMM YYYY');
                }
            },
            { data: 'address' },
            { data: 'phone' },
            {
                data: 'entry_date',
                render: function(data) {
                    if (!data) return '-';
                    return moment(data).format('DD MMM YYYY');
                }
            },
            { data: 'level' },
            { data: 'status' },
            {
                data: 'employee_id',
                orderable: false,
                searchable: false,
                className: "text-center",
                render: function(id) {
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                ⋮
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="/employees/edit/${id}">Edit</a>
                                </li>
                                <li>
                                    <button class="dropdown-item text-danger btn-delete" data-id="${id}">
                                        Delete
                                    </button>
                                </li>
                            </ul>
                        </div>
                    `;
                }
            }
        ]
    });

    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');

        if (!confirm('Delete this employee?')) return;

        $.ajax({
            url: `/employees/${id}`,
            type: "DELETE",
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                $('#employees-table').DataTable().ajax.reload();
            },
            error: function(err) {
                alert("Failed to delete employee");
            }
        });
    });
});
</script>
@endpush