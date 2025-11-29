<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- DataTables Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.css" />

    <style>
        #sidebarMenu.offcanvas {
            width: 250px;
            max-width: 80%;
        }

        .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .sidebar-logo {
            width: 120px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

@if(session('user_type'))

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container-fluid">

            <!-- Sidebar Toggle -->
            <button class="btn btn-outline-primary me-3" 
                    type="button" 
                    data-bs-toggle="offcanvas" 
                    data-bs-target="#sidebarMenu"
                    aria-controls="sidebarMenu">
                <i class="fas fa-bars"></i>
            </button>

            <a class="navbar-brand fw-bold">@yield('page-title', 'Dashboard')</a>

            <div class="ms-auto">
                <span class="me-3">
                    Welcome, <strong>{{ session('name') }}</strong>
                </span>
            </div>
        </div>
    </nav>

    <!-- SIDEBAR -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header d-block text-center">
            
            <!-- LOGO -->
            <img src="{{ asset('assets/image/logo_hania.png') }}" class="sidebar-logo">

            <h5 class="offcanvas-title">SMPIT Hania</h5>

            <button type="button" class="btn-close position-absolute end-0 top-0 m-3"
                    data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush">

                {{-- EMPLOYEE MENU --}}
                @if(session('user_type') === 'employee')

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}" 
                       href="{{ route('employee.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.employees.*') ? 'active' : '' }}" 
                       href="{{ route('employee.employees.index') }}">
                        <i class="fas fa-id-card me-2"></i> Employees
                    </a>

                   

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.academic_classes.*') ? 'active' : '' }}" 
                       href="{{ route('employee.academic_classes.index') }}">
                        <i class="fas fa-chalkboard-teacher me-2"></i> Academic Classes
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.academic_years.*') ? 'active' : '' }}" 
                       href="{{ route('employee.academic_years.index') }}">
                        <i class="fas fa-calendar-alt me-2"></i> Academic Years
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.articles.*') ? 'active' : '' }}" 
                       href="{{ route('employee.articles.index') }}">
                        <i class="fas fa-newspaper me-2"></i> Articles
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.attendances.*') ? 'active' : '' }}" 
                       href="{{ route('employee.attendances.index') }}">
                        <i class="fas fa-clipboard-check me-2"></i> Attendances
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.classes.*') ? 'active' : '' }}" 
                       href="{{ route('employee.classes.index') }}">
                        <i class="fas fa-school me-2"></i> Classes
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.events.*') ? 'active' : '' }}" 
                       href="{{ route('employee.events.index') }}">
                        <i class="fas fa-calendar-check me-2"></i> Events
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.teachers.*') ? 'active' : '' }}" 
                       href="{{ route('employee.teachers.index') }}">
                        <i class="fas fa-chalkboard-user me-2"></i> Teachers
                    </a>

                   

            
                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.student_classes.*') ? 'active' : '' }}" 
                       href="{{ route('employee.student_classes.index') }}">
                        <i class="fas fa-users-between-lines me-2"></i> Student Classes
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.subjects.*') ? 'active' : '' }}" 
                       href="{{ route('employee.subjects.index') }}">
                        <i class="fas fa-book me-2"></i> Subjects
                    </a>

                      <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.payments.*') ? 'active' : '' }}" 
                       href="{{ route('employee.payments.index') }}">
                        <i class="fas fa-book me-2"></i> payments
                    </a>

                    <a class="list-group-item list-group-item-action" href="{{ route('employee.logout') }}">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>

                @endif


                {{-- TEACHER MENU --}}
                @if(session('user_type') === 'teacher')

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}" 
                       href="{{ route('teacher.dashboard') }}">
                        <i class="fas fa-chart-line me-2"></i> Dashboard
                    </a>
                     <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.students.*') ? 'active' : '' }}" 
                       href="{{ route('employee.students.index') }}">
                        <i class="fas fa-user-graduate me-2"></i> Students
                    </a>
        <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.schedules.*') ? 'active' : '' }}" 
                       href="{{ route('employee.schedules.index') }}">
                        <i class="fas fa-clock me-2"></i> Schedules
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.grades.*') ? 'active' : '' }}" 
                       href="{{ route('employee.grades.index') }}">
                        <i class="fas fa-star me-2"></i> Grades
                    </a>

                    <a class="list-group-item list-group-item-action" href="{{ route('teacher.logout') }}">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                @endif


                {{-- STUDENT MENU --}}
                @if(session('user_type') === 'student')

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" 
                       href="{{ route('student.dashboard') }}">
                        <i class="fas fa-chart-line me-2"></i> Dashboard
                    </a>
                    
                    <a class="list-group-item list-group-item-action {{ request()->routeIs('student.profile') ? 'active' : '' }}" 
                       href="{{ route('student.profile') }}">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>

                    <a class="list-group-item list-group-item-action" href="{{ route('student.logout') }}">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>

                @endif

            </div>
        </div>
    </div>
@endif

<div class="container-fluid mt-4">
    @yield('content')
</div>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- DataTables -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net/1.13.6/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.js"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Moment -->
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

@stack('scripts')

</body>
</html>
