@extends('layouts.app')

@section('title', 'Academic Years')
@section('page-title', 'Academic Year Management')

@section('content')

<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-calendar-alt text-primary"></i> Academic Years
            </h5>

            <a href="{{ route('employee.academic_years.create') }}" 
               class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-plus"></i> Add New Academic Year
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

            <!-- ERROR ALERT -->
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-times-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- DATATABLE WRAPPER -->
            <div class="bg-white border rounded p-3 shadow-sm">
                <div class="table-responsive">
                    <table id="academicYearsTable" class="table table-hover table-bordered align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Semester</th>
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

   $('#academicYearsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: "{{ route('employee.academic_years.data') }}",
    columns: [
        
        { 
            data: 'start_date',
            render: function(d) {
                return d ? moment(d).format('DD MMM YYYY') : '-';
            }
        },
        { 
            data: 'end_date',
            render: function(d) {
                return d ? moment(d).format('DD MMM YYYY') : '-';
            }
        },
        { data: 'semester' },
        { 
            data: 'status',
            render: function (data) {
                if (data === 'Active') 
                    return `<span class="badge bg-success">${data}</span>`;
                return `<span class="badge bg-secondary">${data}</span>`;
            }
        },
        {
            data: 'academic_year_id',
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
                                <a class="dropdown-item" href="/academic-years/edit/${id}">
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
    ],
    order: [[1, 'desc']]
});


    // DELETE ACTION
  $(document).on('click', '.btn-delete', function() {
    const id = $(this).data('id');

    if (!confirm('Delete this academic year?')) return;

    $.ajax({
        url: `/academic-years/${id}`,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            _method: 'DELETE'
        },
        success: function() {
            $('#academicYearsTable').DataTable().ajax.reload(null, false);
        },
        error: function() {
            alert('Failed to delete academic year');
        }
    });
});


});
</script>
@endpush
