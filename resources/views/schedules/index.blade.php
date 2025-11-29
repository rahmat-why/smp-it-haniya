@extends('layouts.app')

@section('title', 'Class Schedules')
@section('page-title', 'Class Schedule Management')

@section('content')

<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-calendar-alt text-primary"></i> Class Schedules
            </h5>
            <a href="{{ route('employee.schedules.create') }}" 
               class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-plus"></i> Add Schedule
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
                <table id="schedules-table" class="table table-hover table-bordered align-middle w-100">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Class</th>
                            <th>Day</th>
                            <th>Created At</th>
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
$(function () {
    $('#schedules-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: "{{ route('employee.schedules.data') }}",
        columns: [
         
            { data: 'class_name', name: 'class_name' },
            { data: 'day', name: 'day' },
            { data: 'created_at', name: 'created_at', render: function(d){ return d ? moment(d).format('DD MMM YYYY HH:mm') : '-'; } },
            {
                data: 'schedule_id', orderable: false, searchable: false, className: 'text-center',
                render: function(id) {
                    // Gunakan helper route Laravel
                    let editUrl = "{{ url('schedules') }}/" + id + "/edit";

                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light shadow-sm border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                ⋮
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="${editUrl}">
                                        <i class="fas fa-edit text-primary"></i> Edit
                                    </a>
                                </li>
                                <li>
                                    <form action="{{ url('schedules') }}/` + id + `" method="POST" class="d-inline" onsubmit="return confirm('Delete this schedule?')">
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
        ]
    });
});
</script>
@endpush
