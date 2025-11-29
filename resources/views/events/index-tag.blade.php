@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>
                <a href="{{ route('employee.events.index') }}" class="text-dark text-decoration-none">
                    <i class="fas fa-arrow-left"></i>
                </a>
                Tags for Event: <strong>{{ $event->event_name }}</strong>
            </h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.events.create-tag', $event->event_id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Tags
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
            <table class="table table-bordered table-striped table-hover align-middle" id="tags-table">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Tag ID</th>
                        <th>Tag Code</th>
                        <th>Created At</th>
                        <th>#</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function(){
    $('#tags-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('employee.events.tagsData', $event->event_id) }}",
        columns: [
            { data: 'tag_id', className: 'text-center' },
            { data: 'tag_code', className: 'text-center' },
            { data: 'created_at', className: 'text-center', render: function(data){
                return new Date(data).toLocaleString();
            }},
            { 
                data: 'tag_id', orderable: false, searchable: false, className: 'text-center',
                render: function(id){
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu">
                                <!-- Edit Button (opsional) -->
                                <li>
                                    <a class="dropdown-item" href="/events/{{ $event->event_id }}/tags/edit/${id}">
                                        <i class="fas fa-edit text-primary"></i> Edit
                                    </a>
                                </li>
                                <!-- Delete Button -->
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
        if(!confirm('Delete this tag?')) return;

        $.ajax({
            url: `/events/{{ $event->event_id }}/tags/${id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(){
                $('#tags-table').DataTable().ajax.reload();
            },
            error: function(){
                alert('Failed to delete tag');
            }
        });
    });
});
</script>
@endpush
@endsection
