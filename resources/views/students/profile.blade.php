@extends('layouts.app')

@section('title', 'Student Profiles')
@section('page-title', 'Student Profiles')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center"> <!-- Tambahkan justify-content-center -->
        @foreach($students as $student)
            <div class="col-md-4 mb-4 d-flex justify-content-center"> <!-- Tambahkan d-flex justify-content-center -->
                <div class="card shadow-sm rounded-3" style="width: 100%; max-width: 350px;"> <!-- Optional: batasi max-width card -->
                    <div class="card-body text-center">
                        <img src="{{ $student->profile_photo ? asset('storage/'.$student->profile_photo) : asset('image/default.png') }}"
                             class="rounded-circle mb-3"
                             width="120" height="120" style="object-fit: cover;">

                        <h5 class="fw-bold">{{ $student->first_name }} {{ $student->last_name }}</h5>
                        <p class="mb-1"><strong>NIS:</strong> {{ $student->nis }}</p>
                        <p class="mb-1"><strong>Gender:</strong> {{ $student->gender == 'M' ? 'Male' : 'Female' }}</p>
                        <p class="mb-1"><strong>Birth Date:</strong> {{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d M Y') : '-' }}</p>
                        <p class="mb-1"><strong>Address:</strong> {{ $student->address ?? '-' }}</p>
                        <p class="mb-1"><strong>Father:</strong> {{ $student->father_name ?? '-' }} ({{ $student->father_phone ?? '-' }})</p>
                        <p class="mb-1"><strong>Mother:</strong> {{ $student->mother_name ?? '-' }} ({{ $student->mother_phone ?? '-' }})</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection