@extends('layouts.app')

@section('title', 'Create Student')
@section('page-title', 'Create New Student')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-plus"></i> Add New Student</h5>
            </div>
            <div class="card-body">
                <form id="student-form" action="{{ route('employee.students.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <h6 class="mb-3 text-primary">Personal Information</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="student_id" class="form-label">Student ID *</label>
                                <input type="text" readonly class="form-control @error('student_id') is-invalid @enderror" 
                                       id="student_id" name="student_id" value="{{ old('student_id') }}" required>
                                @error('student_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nis" class="form-label">NIS *</label>
                                <input type="text" class="form-control @error('nis') is-invalid @enderror" 
                                       id="nis" name="nis" value="{{ old('nis') }}" required>
                                @error('nis')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password_confirmation" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" 
                                       id="password_confirmation" name="password_confirmation" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">-- Select Gender --</option>
                                    <option value="M" @selected(old('gender') === 'M')>Male</option>
                                    <option value="F" @selected(old('gender') === 'F')>Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="birth_date" class="form-label">Birth Date</label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" value="{{ old('birth_date') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="birth_place" class="form-label">Birth Place</label>
                        <input type="text" class="form-control" id="birth_place" name="birth_place" value="{{ old('birth_place') }}">
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3">{{ old('address') }}</textarea>
                    </div>

                    <h6 class="mb-3 mt-4 text-primary">Parent/Guardian Information</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="father_name" class="form-label">Father Name</label>
                                <input type="text" class="form-control" id="father_name" name="father_name" value="{{ old('father_name') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="mother_name" class="form-label">Mother Name</label>
                                <input type="text" class="form-control" id="mother_name" name="mother_name" value="{{ old('mother_name') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="father_phone" class="form-label">Father Phone</label>
                                <input type="text" class="form-control" id="father_phone" name="father_phone" value="{{ old('father_phone') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="mother_phone" class="form-label">Mother Phone</label>
                                <input type="text" class="form-control" id="mother_phone" name="mother_phone" value="{{ old('mother_phone') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="father_job" class="form-label">Father Job</label>
                                <input type="text" class="form-control" id="father_job" name="father_job" value="{{ old('father_job') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="mother_job" class="form-label">Mother Job</label>
                                <input type="text" class="form-control" id="mother_job" name="mother_job" value="{{ old('mother_job') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="entry_date" class="form-label">Entry Date</label>
                        <input type="date" class="form-control" id="entry_date" name="entry_date" value="{{ old('entry_date') }}">
                    </div>

                        <div class="form-group mt-3 text-center">
                            <label for="profile_photo" class="form-label d-block">Profile Photo</label>
                            <img id="photoPreview" src="/image/default.png" width="120" height="120" class="rounded-circle mb-2" style="object-fit:cover;" />
                            <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                        </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Student
                        </button>
                        <a href="{{ route('employee.students.index') }}" class="btn btn-secondary">
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

    // Fetch new student id and set readonly field
    $.getJSON("{{ route('employee.students.getNewId') }}").done(function(res){
        if (res.student_id) {
            $('#student_id').val(res.student_id);
        }
    });

    // preview image when file selected
    $('#profile_photo').on('change', function(e){
        const file = this.files && this.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        $('#photoPreview').attr('src', url);
    });

    $('#student-form').on('submit', function(e){
        e.preventDefault();
        const $form = $(this);
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax').remove();

        const hasFile = $('#profile_photo').length && $('#profile_photo')[0].files.length > 0;
        let ajaxOptions = {
            url: $form.attr('action'),
            method: $form.attr('method') || 'POST',
            success: function(res){ window.location = "{{ route('employee.students.index') }}"; },
            error: function(xhr){
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    alert(xhr.responseJSON?.message || 'Failed to create student');
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
