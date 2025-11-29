@extends('layouts.app')

@section('title', 'Attendance Records')
@section('page-title', 'Manage Attendance Records')

@section('content')
<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-user-check text-primary"></i> Attendance Records
            </h5>
            <a href="{{ route('employee.attendances.create') }}" class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-plus"></i> Record Attendance
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
                <table id="attendanceTable" class="table table-hover table-bordered align-middle w-100">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Date</th>
                            <th>Class</th>
                            <th>Teacher</th>
                            <th>Total Present</th>
                            <th width="120" class="text-center">Actions</th>
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
    $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: "{{ route('employee.attendances.data') }}",
        columns: [
            { data: 'attendance_date', name: 'attendance_date', render: function(d){ return d ? moment(d).format('DD MMM YYYY') : '-'; } },
            { data: 'class_name', name: 'class_name' },
            { 
                data: null,
                name: 'teacher',
                render: function(data,type,row){
                    return row.teacher_first_name ? row.teacher_first_name+' '+row.teacher_last_name : '-';
                }
            },
            { data: 'total_present', name: 'total_present', orderable: false, searchable: false },
            {
                data: 'attendance_id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(id){
                    let detailUrl = "{{ url('attendances') }}/" + id;
                    let editUrl = "{{ url('attendances/edit') }}/" + id;
                    let deleteUrl = "{{ url('attendances') }}/" + id;
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light shadow-sm border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                ⋮
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="${detailUrl}">
                                        <i class="fas fa-info-circle text-info"></i> Detail
                                    </a>
                                </li>
                               
                                <li>
                                    <form action="${deleteUrl}" method="POST" class="d-inline" onsubmit="return confirm('Delete this attendance record?')">
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
