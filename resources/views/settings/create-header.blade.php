@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Create New Header Setting</h5>
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

                    <form action="{{ route('employee.settings.store-header') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="header_id" class="form-label">Header ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('header_id') is-invalid @enderror" 
                                   id="header_id" name="header_id" placeholder="e.g., HEADER001"
                                   value="{{ old('header_id') }}" required>
                            @error('header_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" placeholder="e.g., Application Settings"
                                   value="{{ old('title') }}" required>
                            @error('title')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Create Header
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
                    <h6 class="card-title"><i class="fas fa-info-circle"></i> Information</h6>
                    <p class="card-text small mb-0">
                        Headers are main categories that contain multiple detail settings. 
                        Each header can have multiple details configured with different values and types.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
