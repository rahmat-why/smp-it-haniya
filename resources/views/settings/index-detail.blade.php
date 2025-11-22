@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <a href="{{ route('employee.settings.index-header') }}" class="text-white text-decoration-none">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        Details for: <strong>{{ $header->title }}</strong>
                    </h5>
                    <a href="{{ route('employee.settings.create-detail', $header->header_id) }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Add Detail
                    </a>
                </div>
                <div class="card-body">
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Detail ID</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($details as $detail)
                                    <tr>
                                        <td><span class="badge bg-info">{{ $detail->detail_id }}</span></td>
                                        <td>{{ $detail->item_code }}</td>
                                        <td>{{ $detail->item_name }}</td>
                                        <td><span class="badge bg-secondary">{{ $detail->item_type }}</span></td>
                                        <td>
                                            @if ($detail->status === 'Active')
                                                <span class="badge bg-success">{{ $detail->status }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $detail->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('employee.settings.edit-detail', [$header->header_id, $detail->detail_id]) }}" 
                                                   class="btn btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('employee.settings.destroy-detail', [$header->header_id, $detail->detail_id]) }}" 
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
                                            <i class="fas fa-inbox"></i> No details found
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
