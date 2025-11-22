@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <a href="{{ route('employee.settings.detail', $header->header_id) }}" class="text-white text-decoration-none">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        Create New Detail for: {{ $header->title }}
                    </h5>
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

                    <form action="{{ route('employee.settings.store-detail', $header->header_id) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="detail_id" class="form-label">Detail ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('detail_id') is-invalid @enderror" 
                                   id="detail_id" name="detail_id" placeholder="e.g., DETAIL001"
                                   value="{{ old('detail_id') }}" required>
                            @error('detail_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="item_code" class="form-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('item_code') is-invalid @enderror" 
                                   id="item_code" name="item_code" placeholder="e.g., CODE01"
                                   value="{{ old('item_code') }}" required>
                            @error('item_code')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('item_name') is-invalid @enderror" 
                                   id="item_name" name="item_name" placeholder="e.g., Setting Name"
                                   value="{{ old('item_name') }}" required>
                            @error('item_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="item_desc" class="form-label">Description</label>
                            <textarea class="form-control @error('item_desc') is-invalid @enderror" 
                                      id="item_desc" name="item_desc" rows="3" placeholder="Optional description">{{ old('item_desc') }}</textarea>
                            @error('item_desc')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="item_type" class="form-label">Item Type <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('item_type') is-invalid @enderror" 
                                   id="item_type" name="item_type" placeholder="e.g., Text, Number, Boolean"
                                   value="{{ old('item_type') }}" required>
                            @error('item_type')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">-- Select Status --</option>
                                <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Create Detail
                            </button>
                            <a href="{{ route('employee.settings.detail', $header->header_id) }}" class="btn btn-secondary">
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
