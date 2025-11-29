@extends('layouts.app')

@section('title', 'Edit Event')
@section('page-title', 'Event Management')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">

    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-calendar-edit text-warning"></i> Edit Event: {{ $event->event_name }}
            </h5>
        </div>

        <div class="card-body px-4">

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- FORM MULAI -->
            <form action="{{ route('employee.events.update', $event->event_id) }}" 
                  method="POST" 
                  enctype="multipart/form-data"> {{-- WAJIB UNTUK UPLOAD --}}
                  
                @csrf
                @method('PUT')

                <div class="row">

                    <!-- FOTO EVENT -->
                    <div class="col-12 mb-4 text-center">
                        <img id="photoPreview"
                             src="{{ $event->profile_photo ? asset('storage/'.$event->profile_photo) : asset('no-image.png') }}"
                             class="rounded mb-2"
                             width="150"
                             height="150"
                             style="object-fit: cover; border:3px solid #ddd;">
                        
                        <input type="file" 
                               name="profile_photo" 
                               id="profile_photo"
                               accept="image/*"
                               class="form-control mt-2">

                        <small class="text-muted">Upload photo event (optional)</small>
                    </div>

                    <!-- EVENT NAME -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold">Event Name <span class="text-danger">*</span></label>
                        <input type="text"
                               name="event_name"
                               class="form-control"
                               value="{{ old('event_name', $event->event_name) }}"
                               required>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea name="description"
                                  class="form-control"
                                  rows="4"
                                  required>{{ old('description', $event->description) }}</textarea>
                    </div>

                    <!-- LOCATION -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                        <input type="text"
                               name="location"
                               value="{{ old('location', $event->location) }}"
                               class="form-control"
                               required>
                    </div>

                    <!-- STATUS -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Upcoming"  {{ $event->status == 'Upcoming' ? 'selected' : '' }}>Upcoming</option>
                            <option value="Ongoing"   {{ $event->status == 'Ongoing' ? 'selected' : '' }}>Ongoing</option>
                            <option value="Completed" {{ $event->status == 'Completed' ? 'selected' : '' }}>Completed</option>
                            <option value="Cancelled" {{ $event->status == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <!-- TAGS -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold">Tags</label>
                        <select name="tag_codes[]" id="tag_codes" class="form-select" multiple>
                            @foreach ($availableTags as $tag)
                                <option value="{{ $tag->tag_code }}"
                                    {{ in_array($tag->tag_code, $assignedTags ?? []) ? 'selected' : '' }}>
                                    {{ $tag->item_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-warning shadow-sm">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <a href="{{ route('employee.events.index') }}" class="btn btn-secondary shadow-sm">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>

            </form>
            <!-- FORM SELESAI -->

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function(){
        $('#tag_codes').select2({ width: '100%' });

        // PREVIEW FOTO BARU
        $('#profile_photo').on('change', function(e){
            let file = e.target.files[0];
            if (!file) return;
            $('#photoPreview').attr('src', URL.createObjectURL(file));
        });
    });
</script>

@endsection
