@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <a href="{{ route('employee.articles.index') }}" class="text-white text-decoration-none">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        Tags for Article: <strong>{{ $article->title }}</strong>
                    </h5>
                    <a href="{{ route('employee.articles.create-tag', $article->article_id) }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Add Tags
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
                                    <th>Tag ID</th>
                                    <th>Tag Code</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tags as $tag)
                                    <tr>
                                        <td><span class="badge bg-info">{{ $tag->tag_id }}</span></td>
                                        <td><span class="badge bg-secondary">{{ $tag->tag_code }}</span></td>
                                        <td>{{ date('d M Y H:i', strtotime($tag->created_at)) }}</td>
                                        <td>
                                            <form action="{{ route('employee.articles.destroy-tag', [$article->article_id, $tag->tag_id]) }}" 
                                                  method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete"
                                                        onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"></i> No tags assigned
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

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection
