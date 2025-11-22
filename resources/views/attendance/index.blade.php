@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Attendance Records</h5>
                    <a href="{{ route('employee.attendances.create') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Record Attendance
                    </a>
                </div>
                <div class="card-body">
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Class</th>
                                    <th>Teacher</th>
                                    <th>Total Present</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attendances as $attendance)
                                    <tr>
                                        <td>{{ date('d M Y', strtotime($attendance->attendance_date)) }}</td>
                                        <td>{{ $attendance->class_name ?? '-' }}</td>
                                        <td>{{ $attendance->teacher_first_name ? $attendance->teacher_first_name . ' ' . $attendance->teacher_last_name : '-' }}</td>
                                        <td>
                                            @php
                                                $present = (int) $attendance->total_present;
                                                $total = (int) $attendance->total_students;
                                                $allPresent = ($total > 0 && $present === $total);
                                                $nonePresent = ($present === 0);
                                            @endphp
                                            @if ($allPresent)
                                                <span class="badge bg-success">{{ $present }} / {{ $total }}</span>
                                            @elseif ($nonePresent)
                                                <span class="badge bg-danger">{{ $present }} / {{ $total }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ $present }} / {{ $total }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form action="{{ route('employee.attendances.destroy', $attendance->attendance_id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete"
                                                        onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('employee.attendances.show', $attendance->attendance_id) }}" class="btn btn-info btn-sm ms-1" title="View Details">
                                                <i class="fas fa-list"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"></i> No attendance records found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
