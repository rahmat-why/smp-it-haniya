@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 rounded-3">

        <!-- Page Header -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-chalkboard text-primary"></i> Add New Class
            </h5>
        </div>

        <div class="card-body px-4">

            {{-- GLOBAL VALIDATION ALERT --}}
            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <h6 class="alert-heading">
                    <i class="fas fa-exclamation-triangle"></i> Validation Errors
                </h6>
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            {{-- SUCCESS MESSAGE --}}
            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif


            {{-- FORM --}}
            <form action="{{ route('employee.classes.store') }}" method="POST">
                @csrf

                {{-- CLASS ID --}}
             <div class="col-md-6 d-none">
                    <label class="form-label fw-bold">
                        Class ID <span class="text-danger">*</span>
                    </label>

                    <input type="text"
                        name="class_id"
                        id="class_id"
                        value="{{ old('class_id') }}"
                        readonly
                        class="form-control @error('class_id') is-invalid @enderror"
                        required>

                    <small class="text-muted">
                        Auto-generated. Must contain alphanumeric characters only (A–Z, 0–9).
                    </small>

                    @error('class_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- CLASS NAME --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Class Name <span class="text-danger">*</span>
                    </label>

                    <input type="text"
                        name="class_name"
                        id="class_name"
                        value="{{ old('class_name') }}"
                        placeholder="Enter class name"
                        class="form-control @error('class_name') is-invalid @enderror"
                        required
                        oninvalid="this.classList.add('is-invalid')"
                        oninput="this.classList.remove('is-invalid')">

                    <small class="text-muted">
                        Required. May contain letters, numbers, spaces, dot (.), and comma (,).
                        Example: Class A, 1A, 2C.
                    </small>

                    @error('class_name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- CLASS LEVEL --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Class Level <span class="text-danger">*</span>
                    </label>

                    <input type="text"
                        name="class_level"
                        id="class_level"
                        value="{{ old('class_level') }}"
                        placeholder="Enter class level"
                        class="form-control @error('class_level') is-invalid @enderror"
                        required
                        oninvalid="this.classList.add('is-invalid')"
                        oninput="this.classList.remove('is-invalid')">

                    <small class="text-muted">
                        Required. Must be alphanumeric (letters and/or numbers).
                        Example: 10, 11, 12.
                    </small>

                    @error('class_level')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>


                {{-- ACTION BUTTONS --}}
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fas fa-save"></i> Save
                    </button>

                    <a href="{{ route('employee.classes.index') }}" class="btn btn-secondary shadow-sm">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const classId = document.getElementById('class_id');

    // Auto generate Class ID if empty
    if (classId && !classId.value) {
        fetch('{{ route('employee.classes.getNewId') }}')
            .then(res => res.json())
            .then(data => {
                if (data.class_id) {
                    classId.value = data.class_id;
                }
            })
            .catch(err => console.error(err));
    }

});
</script>
@endpush
