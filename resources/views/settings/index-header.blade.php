@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Settings Management</h5>
                    <a href="{{ route('employee.settings.create-header') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Add New Header
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
                                    <th>Header ID</th>
                                    <th>Title</th>
                                    <th>Details Count</th>
                                    <th>Created By</th>
                                    <th>Updated At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($headers as $header)
                                    <tr>
                                        <td><span class="badge bg-info">{{ $header->header_id }}</span></td>
                                        <td>{{ $header->title }}</td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $header->detail_count ?? 0 }} Details
                                            </span>
                                        </td>
                                        <td>{{ $header->created_by }}</td>
                                        <td>{{ $header->updated_at ? date('d M Y H:i', strtotime($header->updated_at)) : '-' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('employee.settings.detail', $header->header_id) }}" 
                                                   class="btn btn-info" title="View Details">
                                                    <i class="fas fa-list"></i>
                                                </a>
                                                <a href="{{ route('employee.settings.edit-header', $header->header_id) }}" 
                                                   class="btn btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('employee.settings.destroy-header', $header->header_id) }}" 
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
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"></i> No settings found
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
