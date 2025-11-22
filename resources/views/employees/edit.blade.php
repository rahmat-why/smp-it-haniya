@extends('layouts.app')

@section('title', 'Edit Employee')
@section('page-title', 'Edit Employee')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">

        <div class="alert alert-danger d-none" id="error-box"></div>
        <div class="alert alert-success d-none" id="success-box"></div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-edit"></i> Edit Employee
                </h5>
            </div>

            <div class="card-body">

                <form id="editEmployeeForm" enctype="multipart/form-data">
                    @csrf

                    <input type="hidden" id="employee_id">

                    {{-- Photo --}}
                    <div class="mb-3 text-center">
                        <img id="photoPreview"
                             src="{{ asset('no-image.png') }}"
                             class="rounded-circle mb-2"
                             width="120" height="120"
                             style="object-fit: cover;">
                        <input type="file" class="form-control mt-2"
                               name="profile_photo" id="profile_photo" accept="image/*">
                    </div>

                    {{-- Fields --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>First Name *</label>
                            <input type="text" name="first_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Username *</label>
                            <input type="text" name="username" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Password (optional)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">-- Select Gender --</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Birth Date</label>
                            <input type="date" name="birth_date" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Birth Place</label>
                        <input type="text" name="birth_place" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Level</label>
                            <select name="level" class="form-select">
                                <option value="">-- Select Level --</option>
                                <option value="HONORER">HONORER</option>
                                <option value="PNS">PNS</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label>Entry Date</label>
                        <input type="date" name="entry_date" class="form-control">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" id="btnUpdate" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Employee
                        </button>
                        <a href="{{ route('employee.employees.index') }}" class="btn btn-secondary">
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
const id = window.location.pathname.split("/").pop();

// Load employee via AJAX
$.get("/employees/get/" + id, function (emp) {

    $("#employee_id").val(emp.employee_id);
    $('[name="first_name"]').val(emp.first_name);
    $('[name="last_name"]').val(emp.last_name);
    $('[name="username"]').val(emp.username);
    $('[name="gender"]').val(emp.gender);
    $('[name="birth_date"]').val(emp.birth_date);
    $('[name="birth_place"]').val(emp.birth_place);
    $('[name="address"]').val(emp.address);
    $('[name="phone"]').val(emp.phone);
    $('[name="level"]').val(emp.level);
    $('[name="entry_date"]').val(emp.entry_date);

    if (emp.profile_photo) {
        $("#photoPreview").attr("src", "/storage/" + emp.profile_photo);
    }
});

// Preview image chosen
$('#profile_photo').on('change', function(e) {
    let file = e.target.files[0];
    if (!file) return;
    $("#photoPreview").attr("src", URL.createObjectURL(file));
});

// Update
$('#btnUpdate').on('click', function () {

    let formData = new FormData(document.getElementById("editEmployeeForm"));
    let errors = {};

    if (!$('[name="first_name"]').val()) errors.first_name = "First name required";
    if (!$('[name="last_name"]').val()) errors.last_name = "Last name required";
    if (!$('[name="username"]').val()) errors.username = "Username required";

    let pass = $('[name="password"]').val();

    if (Object.keys(errors).length > 0) {
        let html = "<ul>";
        for (let e in errors) html += "<li>" + errors[e] + "</li>";
        html += "</ul>";
        $('#error-box').removeClass('d-none').html(html);
        return;
    }

    $.ajax({
        url: "/employees/" + id,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: { 'X-HTTP-Method-Override': 'PUT' },
        success: function () {
            $('#success-box').removeClass('d-none').html("Updated successfully");
            setTimeout(() => window.location.href = "{{ route('employee.employees.index') }}", 800);
        },
        error: function () {
            $('#error-box').removeClass('d-none').html("Failed to update employee");
        }
    });

});
</script>
@endpush
