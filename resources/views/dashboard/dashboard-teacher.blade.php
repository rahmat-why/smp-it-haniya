@extends('layouts.app')

@section('title', 'Teacher Dashboard')

@section('content')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid mt-4">

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h1 class="card-title mb-0">Welcome, {{ Auth::user()->name ?? 'Teacher' }}</h1>
                    <p class="card-text mt-2">Teacher Dashboard - {{ date('l, F d, Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tahun Ajaran -->
    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET" class="d-flex gap-2">
                <select name="academic_year" class="form-control">
                    <option value="">-- Filter Tahun Ajaran --</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year['id'] }}" {{ request('academic_year') == $year['id'] ? 'selected' : '' }}>
                            {{ $year['name'] }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Students</h6>
                    <h3 class="text-primary mb-0">{{ $total_students }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Classes</h6>
                    <h3 class="text-success mb-0">{{ $students_per_class->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Attendance Logs</h6>
                    <h3 class="text-warning mb-0">{{ $attendance_chart->sum('total') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Grades Recorded</h6>
                    <h3 class="text-danger mb-0">{{ $grade_chart->sum('total') }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Layout Card & Tabel: Card kiri, Tabel kanan -->
    <div class="row mb-4">
    <!-- Grade Chart (masih di kiri full) -->
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Grade Chart</h5>
            </div>
            <div class="card-body">
                <canvas id="gradeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Jadwal Mata Pelajaran (kanan full) -->
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Jadwal Mata Pelajaran</h5>
                <form method="GET">
                    <select name="class_id" class="form-control w-auto">
                        <option value="">Filter Kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->class_id }}" {{ request('class_id')==$c->class_id?'selected':'' }}>
                                {{ $c->class_name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mata Pelajaran</th>
                            <th>Guru</th>
                            <th>Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedule as $s)
                        <tr>
                            <td>{{ $s->subject_name }}</td>
                            <td>{{ $s->teacher_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($s->start_time)->format('H.i') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('H.i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">Tidak ada data jadwal</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Row baru: Attendance Chart (kiri) & Daftar Kelas (kanan) -->
<div class="row mb-4">
    <!-- Attendance Chart -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Attendance Chart</h5>
            </div>
            <div class="card-body">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Daftar Kelas -->
    <div class="col-md-6">
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Daftar Kehadiran perkelas</h5>
        </div>
        <div class="table-responsive">
      <table class="table mb-0">
    <thead class="table-light">
        <tr>
            <th>Kelas</th>
            <th>Total Siswa</th>
            <th>Kehadiran (%)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($students_per_class as $item)
            <tr>
                <td>{{ $item->class_name }}</td>
                <td>{{ $item->total_students }}</td>
                <td><strong>{{ $item->percentage }}%</strong></td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center">Tidak ada data siswa</td>
            </tr>
        @endforelse
    </tbody>
</table>


        </div>
    </div>
</div>

</div>


    <!-- Event -->
    <h5>Event</h5>
    <div class="row mb-4">
        @foreach($events as $e)
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                @if($e->profile_photo)
                    <img src="{{ asset('storage/'.$e->profile_photo) }}" class="card-img-top" alt="{{ $e->event_name }}">
                @endif
                <div class="card-body">
                    <h6>{{ $e->event_name }}</h6>
                    <p>{{ $e->description }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>

<script>
const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
new Chart(attendanceCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($attendance_chart->pluck('date')) !!},
        datasets: [{
            label: 'Attendance',
            data: {!! json_encode($attendance_chart->pluck('total')) !!},
            borderWidth: 2,
            borderColor: 'green',
            backgroundColor: 'lightgreen',
            fill: true
        }]
    }
});

const gradeCtx = document.getElementById('gradeChart').getContext('2d');
new Chart(gradeCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($grade_chart->pluck('subject_name')) !!},
        datasets: [{
            label: 'Total Grades',
            data: {!! json_encode($grade_chart->pluck('total')) !!},
            borderWidth: 2,
            backgroundColor: 'lightblue',
            borderColor: 'blue'
        }]
    }
});
</script>

@endsection
