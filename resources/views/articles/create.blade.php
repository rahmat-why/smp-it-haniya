@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .is-invalid {
        border-color: #dc3545 !important;
    }
</style>

<div class="container-fluid">
    <div class="card shadow-sm border-0 rounded-3">

        <!-- Page Header -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-file-alt text-success"></i> Add New Article
            </h5>
            
        </div>

        <!-- Card Body -->
        <div class="card-body px-4">

            {{-- VALIDATION ALERT --}}
            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Validation Errors</h6>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            {{-- FORM --}}
            <form action="{{ route('employee.articles.store') }}" method="POST">
                @csrf

                <!-- ARTICLE ID -->
               <div class="col-md-6 d-none">
                    <label for="article_id" class="form-label fw-bold">
                        Article ID <span class="text-danger">*</span>
                    </label>
                   <input type="text"
       name="article_id"
       id="article_id"
       class="form-control"
       value="{{ $newArticleId }}"
       readonly>


                    <small class="text-muted">Must contain numbers only (Example: 1001).</small>

                    @error('article_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- TITLE -->
                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">
                        Title <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="title"
                        id="title"
                        placeholder="Enter article title"
                        value="{{ old('title') }}"
                        class="form-control @error('title') is-invalid @enderror"
                        required
                        oninvalid="this.classList.add('is-invalid')"
                        oninput="this.classList.remove('is-invalid')">

                    <small class="text-muted">Must contain letters, numbers, or spaces.</small>

                    @error('title')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- SLUG -->
                <div class="mb-3">
                    <label for="slug" class="form-label fw-bold">
                        Slug <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="slug"
                        id="slug"
                        placeholder="Enter slug (e.g. article-about-science)"
                        value="{{ old('slug') }}"
                        class="form-control @error('slug') is-invalid @enderror"
                        required
                        oninvalid="this.classList.add('is-invalid')"
                        oninput="this.classList.remove('is-invalid')">

                    <small class="text-muted">Lowercase, letters, numbers, and hyphens only.</small>

                    @error('slug')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- CONTENT -->
                <div class="mb-3">
                    <label for="content" class="form-label fw-bold">
                        Content <span class="text-danger">*</span>
                    </label>
                    <textarea name="content"
                        id="content"
                        rows="6"
                        placeholder="Write the article content here..."
                        class="form-control @error('content') is-invalid @enderror"
                        required
                        oninvalid="this.classList.add('is-invalid')"
                        oninput="this.classList.remove('is-invalid')">{{ old('content') }}</textarea>

                    <small class="text-muted">Provide a clear and descriptive article body.</small>

                    @error('content')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- STATUS -->
                <div class="mb-3">
                    <label for="status" class="form-label fw-bold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select name="status"
                        id="status"
                        class="form-select @error('status') is-invalid @enderror"
                        required
                        oninvalid="this.classList.add('is-invalid')"
                        oninput="this.classList.remove('is-invalid')">
                        <option value="Draft" {{ old('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                        <option value="Published" {{ old('status') == 'Published' ? 'selected' : '' }}>Published</option>
                        <option value="Archived" {{ old('status') == 'Archived' ? 'selected' : '' }}>Archived</option>
                    </select>

                    <small class="text-muted">Choose the publication status.</small>

                    @error('status')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- TAGS -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Tags</label>
                    <small class="text-muted d-block mb-2">
                        Select one or more tags related to the article.
                    </small>

                    @forelse ($availableTags as $tag)
                    <div class="form-check mb-1">
                        <input class="form-check-input"
                            type="checkbox"
                            name="tag_codes[]"
                            id="tag_{{ $tag->tag_code }}"
                            value="{{ $tag->tag_code }}"
                            {{ is_array(old('tag_codes')) && in_array($tag->tag_code, old('tag_codes')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="tag_{{ $tag->tag_code }}">
                            {{ $tag->item_name ?? $tag->tag_code }}
                        </label>
                        <small class="text-muted d-block">
                            Article relates to "{{ $tag->item_name ?? $tag->tag_code }}".
                        </small>
                    </div>
                    @empty
                    <small class="text-muted">No tags available.</small>
                    @endforelse

                    @error('tag_codes')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- ACTION BUTTONS -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success shadow-sm">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <a href="{{ route('employee.articles.index') }}" class="btn btn-secondary shadow-sm">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection
