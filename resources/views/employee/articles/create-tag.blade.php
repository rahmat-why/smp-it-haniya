@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <a href="{{ route('employee.articles.tag', $article->article_id) }}" class="text-white text-decoration-none">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        Add Tags to Article: {{ $article->title }}
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

                    <form action="{{ route('employee.articles.store-tag', $article->article_id) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="tag_codes" class="form-label">Select Tags <span class="text-danger">*</span></label>
                            <select class="form-select @error('tag_codes') is-invalid @enderror" 
                                    id="tag_codes" name="tag_codes[]" multiple required>
                                @forelse ($availableTags as $tag)
                                    <option value="{{ $tag->tag_code }}"
                                            {{ in_array($tag->tag_code, $assignedTags) ? 'selected' : '' }}>
                                        {{ $tag->item_name ?? $tag->tag_code }}
                                    </option>
                                @empty
                                    <option value="" disabled>No tags available</option>
                                @endforelse
                            </select>
                            @error('tag_codes')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <small class="form-text text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i> Select one or more tags using Ctrl+Click or Cmd+Click
                            </small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Add Tags
                            </button>
                            <a href="{{ route('employee.articles.tag', $article->article_id) }}" class="btn btn-secondary">
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
<script>
    $(document).ready(function() {
        $('#tag_codes').select2({
            placeholder: 'Select tags...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return 'No tags found';
                }
            }
        });
    });
</script>
@endsection
