@extends('layouts.app')
@section('title', 'Teacher Login - School Management System')

@section('content')
<div class="login-container">

    <div class="login-box">

        <!-- LOGO -->
        <img src="{{ asset('assets/image/logo_hania.png') }}" class="login-logo">

        <h2><i class="fas fa-chalkboard-user"></i> Teacher Login</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Login Failed!</strong>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('teacher.authenticate') }}">
            @csrf

            <div class="form-group">
                <label for="npk" class="form-label">NPK (Teacher ID)</label>
                <input type="text"
                       class="form-control @error('npk') is-invalid @enderror"
                       id="npk"
                       name="npk"
                       value="{{ old('npk') }}"
                       required autofocus>
                @error('npk')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       id="password"
                       name="password"
                       required>
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
            <a href="{{ route('employee.login') }}" class="btn btn-outline-secondary btn-sm">Employee Login</a>
            <a href="{{ route('student.login') }}" class="btn btn-outline-secondary btn-sm">Student Login</a>
        </div>

    </div>

</div>

{{-- CSS --}}
<style>
    body {
        background: url('{{ asset('assets/image/bg_3.jpg') }}') no-repeat center center;
        background-size: cover;
        height: 100vh;
    }

    .login-container {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        height: 100%;
        padding-right: 50px;
    }

    .login-box {
        width: 360px;
        background: #ffffff;
        padding: 35px;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        text-align: center;
    }

    .login-logo {
        width: 120px;
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

    @media(max-width: 768px) {
        .login-container {
            justify-content: center;
            padding-right: 0;
        }
    }
</style>
@endsection
