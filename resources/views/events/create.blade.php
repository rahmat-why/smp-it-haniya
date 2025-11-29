@extends('layouts.app')

@section('title', 'Create Event')
@section('page-title', 'Add New Event')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">

        {{-- GLOBAL ALERT --}}
        @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Validation Errors</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-plus"></i> Add New Event
                </h5>
            </div>

            <div class="card-body">
                <form action="{{ route('employee.events.store') }}" 
                      method="POST" 
                      enctype="multipart/form-data">
                    @csrf

                    {{-- HIDDEN EVENT ID --}}
                    <input type="hidden" name="event_id" value="{{ $newEventId }}">

                    {{-- EVENT NAME --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Event Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="event_name" 
                               class="form-control @error('event_name') is-invalid @enderror"
                               value="{{ old('event_name') }}"
                               required>
                        @error('event_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- DESCRIPTION --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" 
                                  class="form-control @error('description') is-invalid @enderror"
                                  rows="4"
                                  required>{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- LOCATION + STATUS --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Location <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="location"
                                   class="form-control @error('location') is-invalid @enderror"
                                   value="{{ old('location') }}"
                                   required>
                            @error('location')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                            <select name="status" 
                                    class="form-select @error('status') is-invalid @enderror"
                                    required>
                                <option value="">-- Select Status --</option>
                                <option value="Upcoming"  {{ old('status')=='Upcoming'?'selected':'' }}>Upcoming</option>
                                <option value="Ongoing"   {{ old('status')=='Ongoing'?'selected':'' }}>Ongoing</option>
                                <option value="Completed" {{ old('status')=='Completed'?'selected':'' }}>Completed</option>
                                <option value="Cancelled" {{ old('status')=='Cancelled'?'selected':'' }}>Cancelled</option>
                            </select>
                            @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- PROFILE PHOTO --}}
                    <div class="form-group mb-3">
                            <label>Profile Photo</label>
                            <input type="file" name="profile_photo" class="form-control" accept="image/*">
                        </div>

                    {{-- TAGS --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tags</label>
                        <div class="border rounded p-3">
                            @forelse ($availableTags as $tag)
                            <div class="form-check mb-1">
                                <input type="checkbox"
                                       class="form-check-input"
                                       name="tag_codes[]"
                                       value="{{ $tag->tag_code }}"
                                       @if(is_array(old('tag_codes')) && in_array($tag->tag_code, old('tag_codes'))) checked @endif>
                                <label class="form-check-label">
                                    {{ $tag->item_name ?? $tag->tag_code }}
                                </label>
                            </div>
                            @empty
                            <p class="text-muted">No tags available.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- BUTTONS --}}
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Event
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
@endsection
