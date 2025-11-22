@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Edit Event</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Validation Errors</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('employee.events.update', $event->event_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="event_id" class="form-label">Event ID</label>
                            <input type="text" class="form-control" id="event_id" 
                                   value="{{ $event->event_id }}" disabled>
                            <small class="text-muted">Cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label for="event_name" class="form-label">Event Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('event_name') is-invalid @enderror" 
                                   id="event_name" name="event_name" placeholder="e.g., Annual Gathering"
                                   value="{{ old('event_name', $event->event_name) }}" required>
                            @error('event_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4" placeholder="Event description" required>{{ old('description', $event->description) }}</textarea>
                            @error('description')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                   id="location" name="location" placeholder="e.g., School Auditorium"
                                   value="{{ old('location', $event->location) }}" required>
                            @error('location')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">-- Select Status --</option>
                                <option value="Upcoming" {{ old('status', $event->status) == 'Upcoming' ? 'selected' : '' }}>Upcoming</option>
                                <option value="Ongoing" {{ old('status', $event->status) == 'Ongoing' ? 'selected' : '' }}>Ongoing</option>
                                <option value="Completed" {{ old('status', $event->status) == 'Completed' ? 'selected' : '' }}>Completed</option>
                                <option value="Cancelled" {{ old('status', $event->status) == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Update Event
                            </button>
                            <a href="{{ route('employee.events.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
