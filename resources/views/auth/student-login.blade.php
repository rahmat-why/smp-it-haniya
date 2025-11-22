@extends('layouts.app')

@section('title', 'Student Login - School Management System')

@section('content')
<div class="login-container">
    <div class="login-box">
        <h2><i class="fas fa-book"></i> Student Login</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Login Failed!</strong>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('student.authenticate') }}">
            @csrf

            <div class="form-group">
                <label for="nis" class="form-label">NIS (Student ID)</label>
                <input type="text" class="form-control @error('nis') is-invalid @enderror" 
                       id="nis" name="nis" value="{{ old('nis') }}" required autofocus>
                @error('nis')
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

            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-lock"></i> Login
            </button>
        </form>

        <hr class="my-3">

        <div class="text-center">
            <p class="text-muted">Other login options:</p>
            <a href="{{ route('employee.login') }}" class="btn btn-outline-secondary btn-sm">Employee Login</a>
            <a href="{{ route('teacher.login') }}" class="btn btn-outline-secondary btn-sm">Teacher Login</a>
        </div>
    </div>
</div>
@endsection
