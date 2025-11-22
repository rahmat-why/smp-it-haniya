@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-calendar-alt"></i> Academic Years</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.academic_years.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Academic Year
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
            <table id="academicYearsTable" class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Academic Year ID</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#academicYearsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('employee.academic_years.data') }}',
                type: 'GET'
            },
            columns: [
                { data: 'academic_year_id', name: 'academic_year_id' },
                { data: 'start_date', name: 'start_date' },
                { data: 'end_date', name: 'end_date' },
                { data: 'semester', name: 'semester' },
                { data: 'status', name: 'status', render: function(data){
                    if (data === 'Active') return '<span class="badge bg-success">' + data + '</span>';
                    return '<span class="badge bg-secondary">' + data + '</span>';
                }},
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[1, 'desc']]
        });
    });
</script>
@endpush
@endsection