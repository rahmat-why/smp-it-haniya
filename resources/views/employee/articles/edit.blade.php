@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Edit Article: {{ $article->title }}</h5>
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

                    <form action="{{ route('employee.articles.update', $article->article_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $article->title) }}" required>
                            @error('title')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $article->slug) }}" required>
                            @error('slug')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea name="content" id="content" class="form-control @error('content') is-invalid @enderror" rows="6">{{ old('content', $article->content) }}</textarea>
                            @error('content')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- article_type removed (not used anymore) -->

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="Draft" {{ old('status', $article->status) === 'Draft' ? 'selected' : '' }}>Draft</option>
                                <option value="Published" {{ old('status', $article->status) === 'Published' ? 'selected' : '' }}>Published</option>
                                <option value="Archived" {{ old('status', $article->status) === 'Archived' ? 'selected' : '' }}>Archived</option>
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
                            <a href="{{ route('employee.articles.index') }}" class="btn btn-secondary">
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
