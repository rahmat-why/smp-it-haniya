@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Grade Details</h5>
        </div>
        <div class="card-body">
            <p><strong>Student:</strong> {{ $grade->first_name }} {{ $grade->last_name }}</p>
            <p><strong>Subject:</strong> {{ $grade->subject_name ?? '-' }}</p>
            <p><strong>Type:</strong> {{ $grade->grade_type }}</p>
            <p><strong>Grade:</strong> {{ $grade->grade_value }}</p>
            <p><strong>Notes:</strong> {{ $grade->notes ?? '-' }}</p>

            <div class="d-flex gap-2 mt-3">
                <a href="{{ route('employee.grade.edit', $grade->grade_id) }}" class="btn btn-primary">Edit</a>
                <a href="{{ route('employee.grade.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</div>
@endsection
