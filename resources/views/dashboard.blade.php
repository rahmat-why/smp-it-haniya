@extends('layouts.app')

@section('title', 'Employee Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h5 class="card-title">Total Employees</h5>
                <h2 class="text-primary">{{ $total_employees }}</h2>
                <a href="{{ route('employee.employees.index') }}" class="btn btn-sm btn-primary mt-2">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-chalkboard-user fa-3x text-success mb-3"></i>
                <h5 class="card-title">Teachers</h5>
                <h2 class="text-success">0</h2>
                <a href="{{ route('employee.teachers.index') }}" class="btn btn-sm btn-success mt-2">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-book fa-3x text-info mb-3"></i>
                <h5 class="card-title">Students</h5>
                <h2 class="text-info">0</h2>
                <a href="{{ route('employee.students.index') }}" class="btn btn-sm btn-info mt-2">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Welcome</h5>
            </div>
            <div class="card-body">
                <p>Welcome to the School Management System! You are logged in as <strong>{{ session('name') }}</strong>.</p>
                <p>Use the sidebar menu to navigate to different sections of the application.</p>
                <ul>
                    <li><strong>Employees:</strong> Manage employee accounts and information</li>
                    <li><strong>Teachers:</strong> Manage teacher data and assignments</li>
                    <li><strong>Students:</strong> Manage student enrollment and information</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
