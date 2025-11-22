@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Articles Management</h5>
                    <a href="{{ route('employee.articles.create') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Add New Article
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
                                    <th>Article ID</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Tags</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($articles as $article)
                                    <tr>
                                        <td><span class="badge bg-info">{{ $article->article_id }}</span></td>
                                        <td>{{ $article->title }}</td>
                                        
                                        <td>
                                            @if ($article->status === 'Published')
                                                <span class="badge bg-success">{{ $article->status }}</span>
                                            @elseif ($article->status === 'Draft')
                                                <span class="badge bg-warning">{{ $article->status }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $article->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($article->tags))
                                                @foreach($article->tags as $tag)
                                                    <span class="badge bg-secondary">{{ $tag }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">No tags</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('employee.articles.edit', $article->article_id) }}" 
                                                   class="btn btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('employee.articles.destroy', $article->article_id) }}" 
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
                                            <i class="fas fa-inbox"></i> No articles found
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
