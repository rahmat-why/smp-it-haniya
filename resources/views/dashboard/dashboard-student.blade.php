@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="container-fluid mt-4">

    <!-- ========================= -->
    <!-- HEADER -->
    <!-- ========================= -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-primary text-white shadow-sm rounded-3">
                <div class="card-body py-4">
                    <h2 class="mb-0 fw-bold">Welcome, {{ $student->first_name }} {{ $student->last_name }}</h2>
                    <small class="opacity-75">{{ date('l, d F Y') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================= -->
    <!-- PEMBAYARAN -->
    <!-- ========================= -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm rounded-3 border-0">

                <div class="row g-0">

                    <!-- Total Tagihan -->
                    <div class="col-md-4 bg-primary text-white d-flex align-items-center justify-content-center p-4 rounded-start-3">
                        <div class="text-center">
                            <h6 class="mb-1 opacity-75">Total Tagihan</h6>
                            <h2 class="fw-bold">Rp {{ number_format($totalBill) }}</h2>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="col-md-8 p-3">
                        <table class="table table-bordered table-striped mb-0">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>No</th>
                                    <th>Bulan</th>
                                    <th>Tipe</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $i => $p)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $p->payment_date }}</td>
                                    <td>{{ $p->item_name }}</td>
                                    <td>Rp {{ number_format($p->total_payment) }}</td>
                                    <td>
                                        <span class="badge 
                                            {{ $p->status == 'Paid' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $p->status }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- Rincian Tetap -->
                <div class="border-top p-3">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <strong>Biaya SPP</strong><br> Rp 300.000
                        </div>
                        <div class="col-md-4">
                            <strong>Biaya Gedung</strong><br> Rp 400.000
                        </div>
                        <div class="col-md-4">
                            <strong>Biaya Seragam</strong><br> Rp 500.000
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ========================= -->
    <!-- NILAI -->
    <!-- ========================= -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm rounded-3 border-0">

                <div class="card-header bg-primary text-white rounded-top-3">
                    <h5 class="mb-0">Nilai</h5>
                </div>

                <div class="row p-3">

                    <div class="col-md-8">
                        <canvas id="chartNilai"></canvas>
                    </div>

                    <div class="col-md-4">
                        <table class="table table-bordered table-striped">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>No</th>
                                    <th>Academic Year</th>
                                    <th>Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grades as $i => $g)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $g->academic_year }}</td>
                                    <td>{{ $g->grade_value }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- ========================= -->
    <!-- KEHADIRAN -->
    <!-- ========================= -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm rounded-3 border-0">

                <div class="card-header bg-primary text-white rounded-top-3">
                    <h5 class="mb-0">Kehadiran</h5>
                </div>

                <div class="row p-3">
                    <div class="col-md-8">
                        <canvas id="chartAbsensi"></canvas>
                    </div>

                    <div class="col-md-4">
                        <table class="table table-bordered table-striped">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>No</th>
                                    <th>Tahun</th>
                                    <th>Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendance as $i => $a)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $a->academic_year }}</td>
                                    <td><strong>{{ $a->total }}%</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ========================= -->
    <!-- JADWAL + EVENT -->
    <!-- ========================= -->
    <div class="row mb-4">

        <!-- Jadwal -->
        <div class="col-md-9">
            <div class="card shadow-sm rounded-3 border-0">

                <div class="card-header bg-primary text-white rounded-top-3">
                    <h5 class="mb-0">Jadwal Mata Pelajaran</h5>
                </div>

                <table class="table table-bordered table-striped mb-0">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>Hari</th>
                            <th>Mapel</th>
                            <th>Guru</th>
                            <th>Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjects as $s)
                        <tr>
                            <td>{{ $s->day }}</td>
                            <td>{{ $s->subject_name }}</td>
                            <td>{{ $s->first_name }} {{ $s->last_name }}</td>
                            <td>{{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>

        <!-- EVENT -->
       <div class="col-md-3">

    <!-- Wrapper agar bisa scroll -->
    <div class="card shadow-sm rounded-3 border-0" style="max-height: 500px; overflow-y: auto;">

        <!-- Header tetap -->
        <div class="card-header bg-primary text-white rounded-top-3 sticky-top" style="top:0; z-index:10;">
            <h5 class="mb-0">EVENT</h5>
        </div>

        <!-- List Event -->
        <div class="p-2">
            @foreach($events as $e)
            <div class="card shadow-sm rounded-3 mb-3">
                @if($e->profile_photo)
                <img src="{{ asset('storage/' . $e->profile_photo) }}" class="card-img-top rounded-top-3">
                @endif
                <div class="card-body">
                    <h6 class="fw-bold mb-1">{{ $e->event_name }}</h6>
                   
                </div>
            </div>
            @endforeach
        </div>

    </div>

</div>


    </div>

</div>

<!-- SCRIPT CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById('chartNilai'), {
    type: 'line',
    data: {
        labels: {!! json_encode(array_map(fn($g) => $g->academic_year, $attendance_chart)) !!},
        datasets: [{
            label: "Nilai Siswa",
            data: {!! json_encode(array_map(fn($g) => $g->total, $attendance_chart)) !!},
            borderWidth: 3
        }]
    }
});

new Chart(document.getElementById('chartAbsensi'), {
    type: 'line',
    data: {
        labels: {!! json_encode(array_map(fn($a) => $a->academic_year, $attendance_chart)) !!},
        datasets: [{
            label: "Persentase Kehadiran",
            data: {!! json_encode(array_map(fn($a) => $a->total, $attendance_chart)) !!},
            borderWidth: 3
        }]
    }
});
</script>

@endsection
