@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
<div class="container-fluid mt-4">

    <!-- FILTER BULAN & TAHUN -->
    <div class="card mb-4 shadow-sm border-primary">
        <div class="card-body d-flex gap-2 flex-wrap align-items-center">
            <form method="GET" class="d-flex gap-2 flex-wrap w-100">
                <select name="year" class="form-control w-auto">
                    @for($y=date('Y')-5; $y<=date('Y'); $y++)
                        <option value="{{ $y }}" {{ $filter_year==$y?'selected':'' }}>{{ $y }}</option>
                    @endfor
                </select>
                <select name="month" class="form-control w-auto">
                    @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}" {{ $filter_month==$m?'selected':'' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endfor
                </select>
                <button class="btn btn-primary shadow-sm">Filter</button>
            </form>
        </div>
    </div>

    <!-- TOTAL TAGIHAN / DIBAYAR -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card text-center shadow-sm border-primary h-100">
                <div class="card-body">
                    <h6 class="text-primary">Total Tagihan</h6>
                    <h3 class="fw-bold">Rp {{ number_format($totalTagihan,0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-center shadow-sm border-primary h-100">
                <div class="card-body">
                    <h6 class="text-primary">Total Dibayar</h6>
                    <h3 class="fw-bold">Rp {{ number_format($totalDibayar,0) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- DAFTAR KELAS -->
    <div class="card mb-4 shadow-sm border-primary">
        <div class="card-header bg-primary text-white fw-bold">Daftar Kelas</div>
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th>Kelas</th>
                        <th>Total Siswa</th>
                        <th>Total Sudah Bayar</th>
                        <th>Total Belum Bayar</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    @foreach($classes as $c)
                        <tr>
                            <td>{{ $c->class_name }}</td>
                            <td>{{ $c->total_siswa }}</td>
                            <td>{{ $c->total_sudah_bayar }}</td>
                            <td>{{ $c->total_belum_bayar }}</td>
                           
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- JADWAL MATA PELAJARAN & EVENTS -->
    <div class="row g-3">
        <!-- Jadwal Mata Pelajaran -->
        <div class="col-md-8">
            <h5 class="mb-3 text-primary fw-bold">Jadwal Mata Pelajaran</h5>
            <div class="card shadow-sm border-primary">
                <div class="card-body p-0">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="table-primary text-center">
                            <tr>
                                <th>Hari</th>
                                <th>Pelajaran</th>
                                <th>Guru</th>
                                <th>Kelas</th>
                                <th>Jam</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @foreach($schedules as $s)
                            <tr>
                                <td>{{ $s->day }}</td>
                                <td>{{ $s->subject_name }}</td>
                                <td>{{ $s->teacher_name }}</td>
                                <td>{{ $s->class_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($s->start_time)->format('H.i') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('H.i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Event -->
        <div class="col-md-4">
            <h5 class="mb-3 text-primary fw-bold">Event</h5>
            <div class="card shadow-sm border-primary" style="max-height: 500px; overflow-y: auto;">
                <div class="card-body p-2">
                    @foreach($events as $e)
                        <div class="card mb-2 shadow-sm border-primary">
                            @if($e->profile_photo)
                                <img src="{{ asset('storage/'.$e->profile_photo) }}" class="card-img-top" alt="{{ $e->event_name }}">
                            @endif
                            <div class="card-body">
                                <h6 class="fw-bold text-primary mb-1">{{ $e->event_name }}</h6>
                                <p class="mb-0">{{ $e->description ?? '-' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
