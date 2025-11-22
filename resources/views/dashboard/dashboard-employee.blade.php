@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
<div class="container-fluid mt-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h1 class="card-title mb-0">
                        @if(Auth::check())
                        Welcome, {{ Auth::user()->name }}
                        @elseif(session()->has('name'))
                        Welcome, {{ session('name') }}
                        @else
                        Welcome, Employee
                        @endif
                    </h1>
                    <p class="card-text mt-2">Employee Dashboard - {{ now()->format('l, F d, Y') }}</p>

                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Students</h6>
                            <h3 class="text-primary mb-0">125</h3>
                        </div>
                        <i class="fas fa-users fa-3x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Teachers</h6>
                            <h3 class="text-success mb-0">28</h3>
                        </div>
                        <i class="fas fa-chalkboard-user fa-3x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Classes</h6>
                            <h3 class="text-warning mb-0">12</h3>
                        </div>
                        <i class="fas fa-school fa-3x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Active Sessions</h6>
                            <h3 class="text-danger mb-0">15</h3>
                        </div>
                        <i class="fas fa-heartbeat fa-3x text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Quick Links -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('employee.employees.index') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-tie"></i> Manage Employees
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Sections -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">No recent activity to display.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .opacity-50 {
        opacity: 0.5;
    }
</style>
@endsection