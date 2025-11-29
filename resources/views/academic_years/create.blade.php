@extends('layouts.app')

@section('title', 'Create Academic Year')
@section('page-title', 'Create New Academic Year')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">

        {{-- Alert Boxes --}}
        @if (session('error'))
            <div class="alert alert-danger" id="error-box">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success" id="success-box">
                {{ session('success') }}
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt"></i> Add New Academic Year
                </h5>
            </div>

            <div class="card-body">
                <form id="academicYearForm" method="POST" action="{{ route('employee.academic_years.store') }}">
                    @csrf

                    {{-- ROW 1 --}}
                    <div class="row mb-3">
                        <div class="col-md-6 d-none">
                            <label class="form-label">Academic Year ID *</label>
                            <input type="text"
                                   name="academic_year_id"
                                   id="academic_year_id"
                                   class="form-control @error('academic_year_id') is-invalid @enderror"
                                   value="{{ old('academic_year_id') }}"
                                   placeholder="2024-2025"
                                   required>

                            <small class="text-danger">
                                @error('academic_year_id') {{ $message }} @enderror
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Semester *</label>
                            <select name="semester"
                                    id="semester"
                                    class="form-select @error('semester') is-invalid @enderror"
                                    required>
                                <option value="">-- Select Semester --</option>
                                <option value="1" {{ old('semester') == '1' ? 'selected' : '' }}>Semester 1</option>
                                <option value="2" {{ old('semester') == '2' ? 'selected' : '' }}>Semester 2</option>
                            </select>

                            <small class="text-danger">
                                @error('semester') {{ $message }} @enderror
                            </small>
                        </div>
                    </div>

                    {{-- ROW 2 --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date *</label>
                            <input type="date"
                                   name="start_date"
                                   id="start_date"
                                   class="form-control @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date') }}"
                                   required>

                            <small class="text-danger">
                                @error('start_date') {{ $message }} @enderror
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">End Date *</label>
                            <input type="date"
                                   name="end_date"
                                   id="end_date"
                                   class="form-control @error('end_date') is-invalid @enderror"
                                   value="{{ old('end_date') }}"
                                   required>

                            <small class="text-danger">
                                @error('end_date') {{ $message }} @enderror
                            </small>
                        </div>
                    </div>

                    {{-- STATUS --}}
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status"
                                id="status"
                                class="form-select @error('status') is-invalid @enderror"
                                required>
                            <option value="">-- Select Status --</option>
                            <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>

                        <small class="text-danger">
                            @error('status') {{ $message }} @enderror
                        </small>
                    </div>

                    {{-- BUTTONS --}}
                    <div class="d-flex gap-2">
                        <button type="submit" id="btnSave" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Academic Year
                        </button>

                        <a href="{{ route('employee.academic_years.index') }}" 
                           class="btn btn-secondary">
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
    // Auto-generate Academic Year ID if empty
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('academic_year_id');

        if (input && !input.value) {
            fetch('{{ route('employee.academic_years.getNewId') }}')
                .then(res => res.json())
                .then(data => {
                    if (data.academic_year_id) input.value = data.academic_year_id;
                })
                .catch(err => console.error(err));
        }
    });
</script>
@endpush
