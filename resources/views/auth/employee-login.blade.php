@extends('layouts.app')

@section('title', 'Employee Login - School Management System')

@section('content')
<div class="login-container">
    <div class="login-box">

        <!-- LOGO -->
        <img src="{{ asset('assets/image/logo_hania.png') }}" class="login-logo">

        <h2><i class="fas fa-sign-in-alt"></i> Employee Login</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Login Failed!</strong>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('employee.authenticate') }}">
            @csrf

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control @error('username') is-invalid @enderror"
                       id="username" name="username" value="{{ old('username') }}" required autofocus>
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror"
                       id="password" name="password" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-login">
                <i class="fas fa-lock"></i> Login
            </button>
        </form>

        <hr class="my-3">

        <div class="text-center">
            <p class="text-muted">Other login options:</p>
            <a href="{{ route('teacher.login') }}" class="btn btn-outline-secondary btn-sm">Teacher Login</a>
            <a href="{{ route('student.login') }}" class="btn btn-outline-secondary btn-sm">Student Login</a>
        </div>
    </div>
</div>

{{-- CSS --}}
<style>
    .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding-top: 60px;
        padding-bottom: 60px;
    }

    .login-box {
        width: 420px;
        background: #ffffff;
        padding: 35px;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        text-align: center;
    }

    /* LOGO */
    .login-logo {
        width: 120px;        /* BESARKAN LOGO */
        height: auto;
        margin-bottom: 15px;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }

    h2 {
        margin-bottom: 20px;
        font-weight: bold;
    }

    .btn-login {
        width: 100%;
        margin-top: 10px;
        padding: 10px 0;
    }

    .form-group {
        text-align: left;
        margin-bottom: 15px;
    }
</style>
@endsection
