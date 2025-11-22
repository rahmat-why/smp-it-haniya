<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome (icons) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- DataTables Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.css" />

    <!-- Small custom styles for sidebar width and active link appearance -->
    <style>
        /* make the offcanvas sidebar a fixed, usable width */
        #sidebarMenu.offcanvas {
            width: 240px;
            max-width: 80%;
        }

        /* emphasize active list-group links */
        .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }
    </style>

</head>
<body>

@if(session('user_type'))

    <!-- NAVBAR TOP -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container-fluid">

            <!-- Sidebar Toggle Button -->
            <button class="btn btn-outline-primary me-3" 
                    type="button" 
                    data-bs-toggle="offcanvas" 
                    data-bs-target="#sidebarMenu"
                    aria-controls="sidebarMenu">
                <i class="fas fa-bars"></i>
            </button>

            <a class="navbar-brand fw-bold">@yield('page-title', 'Dashboard')</a>

            <div class="ms-auto">
                <span class="me-3">Welcome, <strong>{{ session('name') }}</strong></span>
            </div>
        </div>
    </nav>

    <!-- SIDEBAR (Bootstrap Offcanvas) -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="sidebarLabel">SMS - School Management</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body p-0">

            <!-- MENU -->
            <div class="list-group list-group-flush">

                {{-- EMPLOYEE MENU --}}
                @if(session('user_type') === 'employee')

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}" href="{{ route('employee.dashboard') }}">
                        <i class="fas fa-chart-line me-2"></i> Dashboard
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('employee.employees.*') ? 'active' : '' }}" href="{{ route('employee.employees.index') }}">
                        <i class="fas fa-users me-2"></i> Employees
                    </a>

                    <a class="list-group-item list-group-item-action" href="{{ route('employee.logout') }}">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>

                @endif


                {{-- TEACHER MENU --}}
                @if(session('user_type') === 'teacher')

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}" href="{{ route('teacher.dashboard') }}">
                        <i class="fas fa-chart-line me-2"></i> Dashboard
                    </a>

                    <a class="list-group-item list-group-item-action" href="{{ route('teacher.logout') }}">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>

                @endif


                {{-- STUDENT MENU --}}
                @if(session('user_type') === 'student')

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" href="{{ route('student.dashboard') }}">
                        <i class="fas fa-chart-line me-2"></i> Dashboard
                    </a>

                    <a class="list-group-item list-group-item-action {{ request()->routeIs('student.profile') ? 'active' : '' }}" href="{{ route('student.profile') }}">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>

                    <a class="list-group-item list-group-item-action" href="{{ route('student.logout') }}">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>

                @endif

            </div>

        </div>
    </div>

    <!-- MAIN CONTENT (always rendered) -->
@endif

<div class="container-fluid mt-4">
    @yield('content')
</div>

<!-- jQuery (Cloudflare — keep on top for any scripts that need it) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- DataTables (Cloudflare) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net/1.13.6/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.js"></script>

<!-- Bootstrap Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

<!-- STACK: semua halaman bisa push script -->
@stack('scripts')

</body>
</html>