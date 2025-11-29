@extends('layouts.app')

@section('title', 'Create Teacher')
@section('page-title', 'Create New Teacher')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-plus"></i> Add New Teacher</h5>
            </div>
            <div class="card-body">
                <form id="teacher-form" action="{{ route('employee.teachers.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row mb-3">
   <div class="col-md-6 d-none">
        <label for="teacher_id" class="form-label fw-bold">
            Teacher ID <span class="text-danger">*</span>
        </label>
        <input type="text" readonly
               name="teacher_id"
               id="teacher_id"
               class="form-control @error('teacher_id') is-invalid @enderror"
               value="{{ old('teacher_id') }}"
               placeholder="Auto-generated"
               required
               oninvalid="this.classList.add('is-invalid')"
               oninput="this.classList.remove('is-invalid')">
        <small class="text-muted d-block">
            Required. This ID will be automatically generated.
        </small>
        @error('teacher_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="npk" class="form-label fw-bold">
            NPK <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="npk"
               id="npk"
               class="form-control @error('npk') is-invalid @enderror"
               value="{{ old('npk') }}"
               placeholder="Enter NPK"
               required
               oninvalid="this.classList.add('is-invalid')"
               oninput="this.classList.remove('is-invalid')">
        <small class="text-muted d-block">
            Required. Enter the teacher's NPK number.
        </small>
        @error('npk')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="first_name" class="form-label fw-bold">
            First Name <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="first_name"
               id="first_name"
               class="form-control @error('first_name') is-invalid @enderror"
               value="{{ old('first_name') }}"
               placeholder="Enter first name"
               required
               oninvalid="this.classList.add('is-invalid')"
               oninput="this.classList.remove('is-invalid')">
        <small class="text-muted d-block">
            Required. Enter the teacher's first name.
        </small>
        @error('first_name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="last_name" class="form-label fw-bold">
            Last Name <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="last_name"
               id="last_name"
               class="form-control @error('last_name') is-invalid @enderror"
               value="{{ old('last_name') }}"
               placeholder="Enter last name"
               required
               oninvalid="this.classList.add('is-invalid')"
               oninput="this.classList.remove('is-invalid')">
        <small class="text-muted d-block">
            Required. Enter the teacher's last name.
        </small>
        @error('last_name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="password" class="form-label fw-bold">
            Password <span class="text-danger">*</span>
        </label>
        <input type="password"
               name="password"
               id="password"
               class="form-control @error('password') is-invalid @enderror"
               placeholder="Enter password"
               required
               oninvalid="this.classList.add('is-invalid')"
               oninput="this.classList.remove('is-invalid')">
        <small class="text-muted d-block">
            Required. Enter a password for the teacher account.
        </small>
        @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label fw-bold">
            Confirm Password <span class="text-danger">*</span>
        </label>
        <input type="password"
               name="password_confirmation"
               id="password_confirmation"
               class="form-control"
               placeholder="Confirm password"
               required
               oninvalid="this.classList.add('is-invalid')"
               oninput="this.classList.remove('is-invalid')">
        <small class="text-muted d-block">
            Required. Re-enter the password to confirm.
        </small>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="gender" class="form-label fw-bold">
            Gender <span class="text-danger">*</span>
        </label>
        <select name="gender"
                id="gender"
                class="form-select @error('gender') is-invalid @enderror"
                required
                oninvalid="this.classList.add('is-invalid')"
                oninput="this.classList.remove('is-invalid')">
            <option value="">-- Select Gender --</option>
            <option value="M" @selected(old('gender')=='M')>Male</option>
            <option value="F" @selected(old('gender')=='F')>Female</option>
        </select>
        <small class="text-muted d-block">
            Required. Select the teacher's gender.
        </small>
        @error('gender')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="birth_date" class="form-label fw-bold">
            Birth Date <span class="text-danger">*</span>
        </label>
        <input type="date"
               name="birth_date"
               id="birth_date"
               class="form-control @error('birth_date') is-invalid @enderror"
               value="{{ old('birth_date') }}"
               required
               oninvalid="this.classList.add('is-invalid')"
               oninput="this.classList.remove('is-invalid')">
        <small class="text-muted d-block">
            Required. Enter the teacher's date of birth.
        </small>
        @error('birth_date')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label for="birth_place" class="form-label fw-bold">
        Birth Place <span class="text-danger">*</span>
    </label>
    <input type="text"
           name="birth_place"
           id="birth_place"
           class="form-control @error('birth_place') is-invalid @enderror"
           value="{{ old('birth_place') }}"
           placeholder="Enter birth place"
           required
           oninvalid="this.classList.add('is-invalid')"
           oninput="this.classList.remove('is-invalid')">
    <small class="text-muted d-block">
        Required. Enter the teacher's birth place.
    </small>
    @error('birth_place')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="address" class="form-label fw-bold">
        Address <span class="text-danger">*</span>
    </label>
    <textarea name="address"
              id="address"
              class="form-control @error('address') is-invalid @enderror"
              rows="3"
              placeholder="Enter address"
              required
              oninvalid="this.classList.add('is-invalid')"
              oninput="this.classList.remove('is-invalid')">{{ old('address') }}</textarea>
    <small class="text-muted d-block">
        Required. Enter the teacher's address.
    </small>
    @error('address')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="phone" class="form-label fw-bold">
            Phone <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="phone"
               id="phone"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone') }}"
               placeholder="Enter phone number"
               required
               oninvalid="this.classList.add('is-invalid')"
               oninput="this.classList.remove('is-invalid')">
        <small class="text-muted d-block">
            Required. Enter the teacher's phone number.
        </small>
        @error('phone')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="level" class="form-label fw-bold">
            Level <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="level"
               id="level"
               class="form-control @error('level') is-invalid @enderror"
               value="{{ old('level', 'Teacher') }}"
               placeholder="Enter level"
               required
               oninvalid="this.classList.add('is-invalid')"
               oninput="this.classList.remove('is-invalid')">
        <small class="text-muted d-block">
            Required. Enter the teacher's level (default: Teacher).
        </small>
        @error('level')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label for="entry_date" class="form-label fw-bold">
        Entry Date <span class="text-danger">*</span>
    </label>
    <input type="date"
           name="entry_date"
           id="entry_date"
           class="form-control @error('entry_date') is-invalid @enderror"
           value="{{ old('entry_date') }}"
           required
           oninvalid="this.classList.add('is-invalid')"
           oninput="this.classList.remove('is-invalid')">
    <small class="text-muted d-block">
        Required. Enter the teacher's entry date.
    </small>
    @error('entry_date')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mt-3 text-center">
    <label for="profile_photo" class="form-label d-block fw-bold">
        Profile Photo
    </label>
    <img id="photoPreview" src="/image/default.png" width="120" height="120" class="rounded-circle mb-2" style="object-fit:cover;" />
    <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
    <small class="text-muted d-block">Optional. Upload a profile photo for the teacher.</small>
</div>


                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Teacher
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

    // fetch new teacher id
    $.getJSON("{{ route('employee.teachers.getNewId') }}").done(function(res){
        if (res.teacher_id) $('#teacher_id').val(res.teacher_id);
    });

    // preview on change
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
            method: $form.attr('method') || 'POST',
            success: function(res){ window.location = "{{ route('employee.teachers.index') }}"; },
            error: function(xhr){
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    alert(xhr.responseJSON?.message || 'Failed to create teacher');
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
