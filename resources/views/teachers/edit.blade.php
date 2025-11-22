@extends('layouts.app')

@section('title', 'Edit Teacher')
@section('page-title', 'Edit Teacher')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-edit"></i> Edit Teacher</h5>
            </div>
            <div class="card-body">
                <form id="teacher-form" action="{{ route('employee.teachers.update', $teacher->teacher_id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" name="first_name" value="{{ old('first_name', $teacher->first_name) }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" name="last_name" value="{{ old('last_name', $teacher->last_name) }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="npk" class="form-label">NPK *</label>
                        <input type="text" class="form-control @error('npk') is-invalid @enderror" 
                               id="npk" name="npk" value="{{ old('npk', $teacher->npk) }}" required>
                        @error('npk')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-label">Password (leave blank to keep current)</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" 
                                       id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">-- Select Gender --</option>
                                    <option value="M" @selected(old('gender', $teacher->gender) === 'M')>Male</option>
                                    <option value="F" @selected(old('gender', $teacher->gender) === 'F')>Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="birth_date" class="form-label">Birth Date</label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                       value="{{ old('birth_date', $teacher->birth_date) }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="birth_place" class="form-label">Birth Place</label>
                        <input type="text" class="form-control" id="birth_place" name="birth_place" 
                               value="{{ old('birth_place', $teacher->birth_place) }}">
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $teacher->address) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $teacher->phone) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="level" class="form-label">Level</label>
                                <input type="text" class="form-control" id="level" name="level" value="{{ old('level', $teacher->level) }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="entry_date" class="form-label">Entry Date</label>
                        <input type="date" class="form-control" id="entry_date" name="entry_date" 
                               value="{{ old('entry_date', $teacher->entry_date) }}">
                    </div>

                    <div class="form-group mt-3">
                        <label for="profile_photo" class="form-label">Profile Photo</label>
                        <div class="mb-2 text-center">
                            @if(!empty($teacher->profile_photo))
                                <img id="photoPreview" src="{{ asset('storage/' . $teacher->profile_photo) }}" alt="photo" width="120" height="120" class="rounded-circle mb-2" style="object-fit:cover;" />
                            @else
                                <img id="photoPreview" src="/image/default.png" alt="photo" width="120" height="120" class="rounded-circle mb-2" style="object-fit:cover;" />
                            @endif
                        </div>
                        <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Teacher
                        </button>
                        <a href="{{ route('employee.teachers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
    function showValidationErrors($form, errors){
        for (const field in errors) {
            const $el = $form.find('[name="'+field+'"]').first();
            if ($el.length) {
                $el.addClass('is-invalid');
                $el.after('<div class="invalid-feedback ajax">'+errors[field][0]+'</div>');
            }
        }
    }

    // preview image on change
    $('#profile_photo').on('change', function(){
        const file = this.files && this.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        $('#photoPreview').attr('src', url);
    });

    $('#teacher-form').on('submit', function(e){
        e.preventDefault();
        const $form = $(this);
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax').remove();

        const hasFile = $('#profile_photo').length && $('#profile_photo')[0].files.length > 0;
        let ajaxOptions = {
            url: $form.attr('action'),
            method: 'POST',
            success: function(res){ window.location = "{{ route('employee.teachers.index') }}"; },
            error: function(xhr){
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    alert(xhr.responseJSON?.message || 'Failed to update teacher');
                }
            }
        };

        if (hasFile) {
            const fd = new FormData(this);
            ajaxOptions.data = fd;
            ajaxOptions.processData = false;
            ajaxOptions.contentType = false;
        } else {
            ajaxOptions.data = $form.serialize();
        }

        $.ajax(ajaxOptions);
    });
});
</script>
@endpush
