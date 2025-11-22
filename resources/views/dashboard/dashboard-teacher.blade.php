@extends('layouts.app')

@section('title', 'Teacher Dashboard')

@section('content')
<div class="container-fluid mt-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h1 class="card-title mb-0">
                        @if(session()->has('name'))
                            Welcome, {{ session('name') }}
                        @elseif(isset($teacher) && !empty($teacher->first_name))
                            Welcome, {{ $teacher->first_name }} {{ $teacher->last_name }}
                        @elseif(Auth::check())
                            Welcome, {{ Auth::user()->name }}
                        @else
                            Welcome, Teacher
                        @endif
                    </h1>
                    <p class="card-text mt-2">Teacher Dashboard - {{ date('l, F d, Y') }}</p>
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
                            <h6 class="text-muted mb-2">Classes Assigned</h6>
                            <h3 class="text-success mb-0">5</h3>
                        </div>
                        <i class="fas fa-school fa-3x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Students</h6>
                            <h3 class="text-info mb-0">145</h3>
                        </div>
                        <i class="fas fa-user-graduate fa-3x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Grades Recorded</h6>
                            <h3 class="text-warning mb-0">320</h3>
                        </div>
                        <i class="fas fa-star fa-3x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Absences This Month</h6>
                            <h3 class="text-danger mb-0">12</h3>
                        </div>
                        <i class="fas fa-calendar-times fa-3x text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Class Schedule -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">My Classes</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Mathematics - Class 10A</h6>
                                <small class="text-muted">Mon, Wed, Fri</small>
                            </div>
                            <p class="mb-1 small">09:00 - 10:30</p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Science - Class 10B</h6>
                                <small class="text-muted">Tue, Thu</small>
                            </div>
                            <p class="mb-1 small">10:45 - 12:15</p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">English - Class 11A</h6>
                                <small class="text-muted">Mon, Wed, Fri</small>
                            </div>
                            <p class="mb-1 small">13:00 - 14:30</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Schedule -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Today's Classes</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>09:00 - 10:30</td>
                                <td>Class 10A</td>
                                <td>Mathematics</td>
                                <td>Room 101</td>
                            </tr>
                            <tr>
                                <td>10:45 - 12:15</td>
                                <td>Class 10B</td>
                                <td>Science</td>
                                <td>Room 102</td>
                            </tr>
                            <tr>
                                <td>13:00 - 14:30</td>
                                <td>Class 11A</td>
                                <td>English</td>
                                <td>Room 103</td>
                            </tr>
                        </tbody>
                    </table>
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
