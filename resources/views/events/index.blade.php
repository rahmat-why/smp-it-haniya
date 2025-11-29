@extends('layouts.app')

@section('title', 'Events')
@section('page-title', 'Event Management')

@section('content')
<div class="container-fluid">

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Events Management</h5>
            <a href="{{ route('employee.events.create') }}" class="btn btn-light btn-sm">
                <i class="fas fa-plus"></i> Add New Event
            </a>
        </div>

        <div class="card-body">

            @if ($message = Session::get('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($message = Session::get('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="events-table" class="table table-bordered table-striped" width="100%">
                    <thead class="table-light">
                        <tr>
                            
                            <th>Event Name</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Tags</th>
                            <th width="120px">Actions</th>
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
    $('#events-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('employee.events.data') }}",
        columns: [
          
            { data: 'event_name', name: 'event_name' },
            { data: 'location', name: 'location' },
            { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
            { data: 'tags', name: 'tags', orderable: false, searchable: false },
            {
                data: 'event_id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(id, type, row){
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light shadow-sm border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="/events/edit/${id}">
                                        <i class="fas fa-edit text-primary"></i> Edit
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/events/${id}/tags">
                                        <i class="fas fa-tags text-info"></i> Tags
                                    </a>
                                </li>
                                <li>
                                    <button class="dropdown-item text-danger btn-delete" data-id="${id}">
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
    $(document).on('click', '.btn-delete', function(){
        const id = $(this).data('id');
        if(!confirm('Delete this event?')) return;

        $.ajax({
            url: `/events/${id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(){
                $('#events-table').DataTable().ajax.reload();
            },
            error: function(){
                alert('Failed to delete event');
            }
        });
    });
});
</script>
@endpush
