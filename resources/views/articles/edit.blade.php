@extends('layouts.app')

@section('title', 'Edit Article')
@section('page-title', 'Article Management')

@section('content')

<div class="container-fluid">

    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <!-- Ganti icon menjadi biru -->
                <i class="fas fa-edit text-primary"></i> Edit Article
            </h5>
        </div>

        <div class="card-body px-4">

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <h6 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-triangle"></i> Validation Errors
                    </h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="bg-white border rounded p-4 shadow-sm">
               <form action="{{ route('employee.articles.update', $article->article_id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">

                    <!-- TITLE -->
                    <div class="col-md-6 mb-4">
                        <label for="title" class="form-label fw-bold">
                            Title <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            placeholder="Enter article title (letters and numbers only)"
                            value="{{ old('title', $article->title) }}"
                            class="form-control @error('title') is-invalid @enderror"
                            required
                            pattern="[A-Za-z0-9 ]+"
                        >
                        <small class="text-muted d-block mt-1">
                            Title must contain letters and numbers only (e.g., "New Student Intake 2025").
                        </small>
                        @error('title')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- SLUG -->
                    <div class="col-md-6 mb-4">
                        <label for="slug" class="form-label fw-bold">
                            Slug <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="slug"
                            id="slug"
                            placeholder="e.g., new-student-intake-2025"
                            value="{{ old('slug', $article->slug) }}"
                            class="form-control @error('slug') is-invalid @enderror"
                            required
                            pattern="[a-z0-9-]+"
                        >
                        <small class="text-muted d-block mt-1">
                            Slug must be lowercase, contain only numbers, letters, and hyphens.
                        </small>
                        @error('slug')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- CONTENT -->
                    <div class="col-md-8 mb-4">
                        <label for="content" class="form-label fw-bold">
                            Content <span class="text-danger">*</span>
                        </label>
                        <textarea
                            name="content"
                            id="content"
                            rows="6"
                            placeholder="Enter detailed article content here"
                            class="form-control @error('content') is-invalid @enderror"
                            required>{{ old('content', $article->content) }}</textarea>
                        <small class="text-muted d-block mt-1">
                            Write the full article content here.
                        </small>
                        @error('content')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- STATUS -->
                    <div class="col-md-4 mb-4">
                        <label for="status" class="form-label fw-bold">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select
                            name="status"
                            id="status"
                            class="form-select @error('status') is-invalid @enderror"
                            required>
                            <option value="Draft"     {{ old('status', $article->status) === 'Draft' ? 'selected' : '' }}>Draft</option>
                            <option value="Published" {{ old('status', $article->status) === 'Published' ? 'selected' : '' }}>Published</option>
                            <option value="Archived"  {{ old('status', $article->status) === 'Archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                        <small class="text-muted d-block mt-1">
                            Choose the article publishing status.
                        </small>
                        @error('status')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- TAGS -->
                    <div class="col-md-12 mb-4">
                        <label class="form-label fw-bold">Tags</label>
                        <small class="text-muted d-block mb-2">
                            Select one or more tags related to the article.
                        </small>

                        <div class="row">
                            @forelse ($availableTags as $tag)
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            name="tag_codes[]"
                                            id="tag_{{ $tag->tag_code }}"
                                            value="{{ $tag->tag_code }}"
                                            class="form-check-input"
                                            {{ in_array($tag->tag_code, old('tag_codes', $assignedTags ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="tag_{{ $tag->tag_code }}">
                                            {{ $tag->item_name ?? $tag->tag_code }}
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <small class="text-muted">No tags available.</small>
                            @endforelse
                        </div>

                        @error('tag_codes')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                </div>

                <div class="d-flex gap-2">
                    <!-- Ganti button menjadi biru -->
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fas fa-save"></i> Update Article
                    </button>

                    <a href="{{ route('employee.articles.index') }}"
                       class="btn btn-secondary shadow-sm">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>

            </form>

            </div>

        </div>
    </div>
</div>

@endsection
