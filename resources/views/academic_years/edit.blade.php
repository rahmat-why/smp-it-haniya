@extends('layouts.app')

@section('title', 'Edit Academic Year')
@section('page-title', 'Edit Academic Year')

@section('content')

<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-calendar-alt text-primary"></i> Edit Academic Year
            </h5>
            <a href="{{ route('employee.academic_years.index') }}" 
               class="btn btn-secondary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <!-- BODY -->
        <div class="card-body px-4">

            <form action="{{ route('employee.academic_years.update', $academicYear->academic_year_id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white border rounded p-4 shadow-sm">

                    <!-- ROW 1 -->
                    <div class="row">
                       <div class="col-md-6 d-none">
                            <label class="form-label fw-bold">Academic Year ID</label>
                            <input type="text" class="form-control" 
                                   value="{{ $academicYear->academic_year_id }}" disabled>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Semester <span class="text-danger">*</span></label>
                            <select class="form-select @error('semester') is-invalid @enderror" 
                                    name="semester" required>
                                <option value="1" {{ old('semester', $academicYear->semester) == '1' ? 'selected' : '' }}>
                                    Semester 1
                                </option>
                                <option value="2" {{ old('semester', $academicYear->semester) == '2' ? 'selected' : '' }}>
                                    Semester 2
                                </option>
                            </select>
                            @error('semester')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- ROW 2 -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Start Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   class="form-control @error('start_date') is-invalid @enderror"
                                   name="start_date"
                                   value="{{ old('start_date', $academicYear->start_date) }}"
                                   required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">End Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   class="form-control @error('end_date') is-invalid @enderror"
                                   name="end_date"
                                   value="{{ old('end_date', $academicYear->end_date) }}"
                                   required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- ROW 3 -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                name="status" required>
                            <option value="Active" {{ old('status', $academicYear->status) == 'Active' ? 'selected' : '' }}>
                                Active
                            </option>
                            <option value="Inactive" {{ old('status', $academicYear->status) == 'Inactive' ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- BUTTONS -->
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="fas fa-save"></i> Update Academic Year
                        </button>
                        <a href="{{ route('employee.academic_years.index') }}" 
                           class="btn btn-secondary shadow-sm">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>

                </div>
            </form>

        </div>

    </div>

</div>

@endsection
