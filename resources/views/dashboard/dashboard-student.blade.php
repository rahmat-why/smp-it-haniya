@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="container-fluid mt-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <h1 class="card-title mb-0">
                        @if(isset($student) && !empty($student->first_name))
                            Welcome, {{ $student->first_name }} {{ $student->last_name }}
                        @elseif(session()->has('name'))
                            Welcome, {{ session('name') }}
                        @elseif(Auth::check())
                            Welcome, {{ Auth::user()->name }}
                        @else
                            Welcome, Student
                        @endif
                    </h1>
                    <p class="card-text mt-2">Student Dashboard - {{ date('l, F d, Y') }}</p>
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
                            <h6 class="text-muted mb-2">GPA</h6>
                            <h3 class="text-info mb-0">3.85</h3>
                        </div>
                        <i class="fas fa-chart-line fa-3x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Classes</h6>
                            <h3 class="text-success mb-0">5</h3>
                        </div>
                        <i class="fas fa-book fa-3x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Attendance</h6>
                            <h3 class="text-warning mb-0">95%</h3>
                        </div>
                        <i class="fas fa-check-circle fa-3x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Assignments</h6>
                            <h3 class="text-danger mb-0">3</h3>
                        </div>
                        <i class="fas fa-tasks fa-3x text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- My Subjects -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">My Subjects</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Mathematics</td>
                                <td>Mr. Smith</td>
                                <td><span class="badge bg-success">A+</span></td>
                            </tr>
                            <tr>
                                <td>Science</td>
                                <td>Ms. Johnson</td>
                                <td><span class="badge bg-success">A</span></td>
                            </tr>
                            <tr>
                                <td>English</td>
                                <td>Mr. Brown</td>
                                <td><span class="badge bg-info">B+</span></td>
                            </tr>
                            <tr>
                                <td>History</td>
                                <td>Ms. Davis</td>
                                <td><span class="badge bg-success">A</span></td>
                            </tr>
                            <tr>
                                <td>Physical Education</td>
                                <td>Mr. Wilson</td>
                                <td><span class="badge bg-warning">B</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('student.profile') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-star"></i> View Grades
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt"></i> View Schedule
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-files"></i> View Announcements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Class Schedule -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Weekly Schedule</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Monday</th>
                                <th>Tuesday</th>
                                <th>Wednesday</th>
                                <th>Thursday</th>
                                <th>Friday</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>09:00 - 10:30</strong></td>
                                <td>Mathematics</td>
                                <td>English</td>
                                <td>Science</td>
                                <td>Mathematics</td>
                                <td>History</td>
                            </tr>
                            <tr>
                                <td><strong>10:45 - 12:15</strong></td>
                                <td>Science</td>
                                <td>History</td>
                                <td>English</td>
                                <td>PE</td>
                                <td>Science</td>
                            </tr>
                            <tr>
                                <td><strong>13:00 - 14:30</strong></td>
                                <td>PE</td>
                                <td>Mathematics</td>
                                <td>History</td>
                                <td>English</td>
                                <td>Mathematics</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Announcements -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Recent Announcements</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">No announcements at this time.</p>
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
