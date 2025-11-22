@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Edit Event: {{ $event->event_name }}</h5>
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
                            <label for="event_name" class="form-label">Event Name <span class="text-danger">*</span></label>
                            <input type="text" name="event_name" id="event_name" class="form-control @error('event_name') is-invalid @enderror" value="{{ old('event_name', $event->event_name) }}" required>
                            @error('event_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description', $event->description) }}</textarea>
                            @error('description')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" name="location" id="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $event->location) }}" required>
                            @error('location')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="Upcoming" {{ old('status', $event->status) === 'Upcoming' ? 'selected' : '' }}>Upcoming</option>
                                <option value="Ongoing" {{ old('status', $event->status) === 'Ongoing' ? 'selected' : '' }}>Ongoing</option>
                                <option value="Completed" {{ old('status', $event->status) === 'Completed' ? 'selected' : '' }}>Completed</option>
                                <option value="Cancelled" {{ old('status', $event->status) === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="tag_codes" class="form-label">Tags</label>
                            <select name="tag_codes[]" id="tag_codes" class="form-select @error('tag_codes') is-invalid @enderror" multiple>
                                @forelse ($availableTags as $tag)
                                    <option value="{{ $tag->tag_code }}" {{ in_array($tag->tag_code, old('tag_codes', $assignedTags ?? [])) ? 'selected' : '' }}>{{ $tag->item_name ?? $tag->tag_code }}</option>
                                @empty
                                    <!-- No tags available -->
                                @endforelse
                            </select>
                            @error('tag_codes')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <small class="form-text text-muted d-block mt-2">Select one or more tags (optional)</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Update
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

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection
