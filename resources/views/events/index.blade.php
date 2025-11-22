@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
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
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Event ID</th>
                                    <th>Event Name</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Tags</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($events as $event)
                                    <tr>
                                        <td><span class="badge bg-info">{{ $event->event_id }}</span></td>
                                        <td>{{ $event->event_name }}</td>
                                        <td>{{ $event->location }}</td>
                                        <td>
                                            @if ($event->status === 'Ongoing')
                                                <span class="badge bg-danger">{{ $event->status }}</span>
                                            @elseif ($event->status === 'Upcoming')
                                                <span class="badge bg-warning">{{ $event->status }}</span>
                                            @elseif ($event->status === 'Completed')
                                                <span class="badge bg-success">{{ $event->status }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $event->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $event->tag_count ?? 0 }} Tags</span>
                                        </td>
                                        <td>{{ $event->created_by }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('employee.events.tag', $event->event_id) }}" 
                                                   class="btn btn-info" title="Manage Tags">
                                                    <i class="fas fa-tags"></i>
                                                </a>
                                                <a href="{{ route('employee.events.edit', $event->event_id) }}" 
                                                   class="btn btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('employee.events.destroy', $event->event_id) }}" 
                                                      method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="Delete"
                                                            onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"></i> No events found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
