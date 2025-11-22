@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-edit"></i> Edit Academic Year</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('employee.academic_years.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Academic Years
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('employee.academic_years.update', $academicYear->academic_year_id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="academic_year_id" class="form-label">Academic Year ID</label>
                        <input type="text" class="form-control" id="academic_year_id" 
                               value="{{ $academicYear->academic_year_id }}" disabled>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                        <select class="form-select @error('semester') is-invalid @enderror" 
                                id="semester" name="semester" required>
                            <option value="1" {{ old('semester', $academicYear->semester) == '1' ? 'selected' : '' }}>Semester 1</option>
                            <option value="2" {{ old('semester', $academicYear->semester) == '2' ? 'selected' : '' }}>Semester 2</option>
                        </select>
                        @error('semester')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                               id="start_date" name="start_date" value="{{ old('start_date', $academicYear->start_date) }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                               id="end_date" name="end_date" value="{{ old('end_date', $academicYear->end_date) }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" 
                            id="status" name="status" required>
                        <option value="Active" {{ old('status', $academicYear->status) == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $academicYear->status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Academic Year
                    </button>
                    <a href="{{ route('employee.academic_years.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
