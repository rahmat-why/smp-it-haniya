@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Edit Header Setting</h5>
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

                    <form action="{{ route('employee.settings.update-header', $header->header_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="header_id" class="form-label">Header ID</label>
                            <input type="text" class="form-control" id="header_id" 
                                   value="{{ $header->header_id }}" disabled>
                            <small class="text-muted">Cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" placeholder="e.g., Application Settings"
                                   value="{{ old('title', $header->title) }}" required>
                            @error('title')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Update Header
                            </button>
                            <a href="{{ route('employee.settings.index-header') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-history"></i> Information</h6>
                    <p class="card-text small mb-2">
                        <strong>Created By:</strong> {{ $header->created_by }}<br>
                        <strong>Created At:</strong> {{ date('d M Y H:i', strtotime($header->created_at)) }}<br>
                        <strong>Last Updated:</strong> {{ date('d M Y H:i', strtotime($header->updated_at)) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
