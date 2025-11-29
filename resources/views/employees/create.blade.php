@extends('layouts.app')

@section('title', 'Create Employee')
@section('page-title', 'Create New Employee')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">

        <div class="alert alert-danger d-none" id="error-box"></div>
        <div class="alert alert-success d-none" id="success-box"></div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus"></i> Add New Employee
                </h5>
            </div>

            <div class="card-body">

                <form id="employeeForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Profile Photo --}}
                    <div class="mb-3 text-center">
                        <img id="photoPreview"
                             src="{{ asset('no-image.png') }}"
                             width="120" height="120"
                             class="rounded-circle mb-2"
                             style="object-fit: cover;">

                        <input type="file" name="profile_photo" id="profile_photo"
                               accept="image/*" class="form-control mt-2">

                        <small class="text-danger error" data-error="profile_photo"></small>
                    </div>

                    <script>
                        document.getElementById('profile_photo').addEventListener('change', function(e) {
                            let file = e.target.files[0];
                            if (!file) return;

                            let url = URL.createObjectURL(file);
                            document.getElementById('photoPreview').src = url;
                        });
                    </script>


                    <div class="row mb-3">
                        <div class="col-md-6 d-none">
    <label class="form-label">Employee ID *</label>
    <input type="text" name="employee_id" id="employee_id"
           class="form-control"
           value="{{ session('employee_id') }}">
    <small class="text-danger error" data-error="employee_id"></small>
</div>


                        <div class="col-md-6">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control">
                            <small class="text-danger error" data-error="username"></small>
                        </div>
                    </div>


                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control">
                            <small class="text-danger error" data-error="first_name"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control">
                            <small class="text-danger error" data-error="last_name"></small>
                        </div>
                    </div>


                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control">
                            <small class="text-danger error" data-error="password"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" name="password_confirm" class="form-control">
                            <small class="text-danger error" data-error="password_confirm"></small>
                        </div>
                    </div>


                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">-- Select Gender --</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                            <small class="text-danger error" data-error="gender"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Birth Date</label>
                            <input type="date" name="birth_date" class="form-control">
                            <small class="text-danger error" data-error="birth_date"></small>
                        </div>
                    </div>


                    <div class="mb-3">
                        <label class="form-label">Birth Place</label>
                        <input type="text" name="birth_place" class="form-control">
                        <small class="text-danger error" data-error="birth_place"></small>
                    </div>


                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                        <small class="text-danger error" data-error="address"></small>
                    </div>


                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                            <small class="text-danger error" data-error="phone"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Level *</label>
                            <select name="level" class="form-select">
                                <option value="">-- Select Level --</option>
                                <option value="HONORER">HONORER</option>
                                <option value="PNS">PNS</option>
                            </select>
                            <small class="text-danger error" data-error="level"></small>
                        </div>
                    </div>


                    <div class="mb-3">
                        <label class="form-label">Entry Date</label>
                        <input type="date" name="entry_date" class="form-control">
                        <small class="text-danger error" data-error="entry_date"></small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" id="btnSave" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Employee
                        </button>

                        <a href="{{ route('employee.employees.index') }}" class="btn btn-secondary">
                            Cancel
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
function validateForm() {
    let errors = {};

    let employee_id       = $("[name=employee_id]").val().trim();
    let username          = $("[name=username]").val().trim();
    let first_name        = $("[name=first_name]").val().trim();
    let last_name         = $("[name=last_name]").val().trim();
    let password          = $("[name=password]").val().trim();
    let password_confirm  = $("[name=password_confirm]").val().trim();
    let level             = $("[name=level]").val().trim();

    if (employee_id === "") errors.employee_id = "Employee ID is required";
    if (username === "") errors.username = "Username is required";
    if (first_name === "") errors.first_name = "First name is required";
    if (last_name === "") errors.last_name = "Last name is required";
    if (password === "") errors.password = "Password is required";
    if (level === "") errors.level = "Level is required";
    if (password !== password_confirm) 
        errors.password_confirm = "Password confirmation does not match";

    $(".error").text("");

    for (let key in errors) {
        $(`[data-error=${key}]`).text(errors[key]);
    }

    return Object.keys(errors).length === 0;
}


// === AJAX Submit ===
$("#employeeForm").submit(function(e) {
    e.preventDefault();

    if (!validateForm()) return;

    let formData = new FormData(this);

    $("#btnSave").prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

    $.ajax({
        url: "{{ route('employee.employees.store') }}",
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            $('#success-box').removeClass('d-none').text('Employee created successfully!');
            setTimeout(() => window.location.href = "{{ route('employee.employees.index') }}", 600);
        },
        error: function(xhr) {
            $("#btnSave").prop("disabled", false).html('<i class="fas fa-save"></i> Save Employee');

            if (xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function(key, val) {
                    $(`[data-error=${key}]`).text(val[0]);
                });
            }
        }
    });
});

$(document).ready(function() {
    // Ambil employee_id baru via AJAX
    $.ajax({
        url: "{{ route('employee.employees.getNewId') }}",
        method: "GET",
        success: function(res) {
            $('#employee_id').val(res.employee_id);
        },
        error: function() {
            alert("Failed to generate Employee ID");
        }
    });
});
</script>
@endpush