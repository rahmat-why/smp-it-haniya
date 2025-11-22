<?php echo ""; ?>
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Attendance Details</h5>
                        <small class="text-muted">Date: {{ date('d M Y', strtotime($header->attendance_date)) }}</small>
                    </div>
                    <div>
                        <a href="{{ route('employee.attendances.index') }}" class="btn btn-secondary btn-sm">Back</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Class:</strong> {{ $class->class_name ?? '-' }}<br>
                        <strong>Teacher:</strong> {{ $teacher ? $teacher->first_name . ' ' . $teacher->last_name : '-' }}
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>NIS</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($details as $d)
                                    <tr>
                                        <td>{{ $d->student_id }}</td>
                                        <td>{{ $d->first_name }} {{ $d->last_name }}</td>
                                        <td>
                                            @if ($d->status === 'Present')
                                                <span class="badge bg-success">Present</span>
                                            @elseif ($d->status === 'Sick')
                                                <span class="badge bg-warning">Sick</span>
                                            @elseif ($d->status === 'Permit')
                                                <span class="badge bg-primary">Permit</span>
                                            @elseif ($d->status === 'No Information')
                                                <span class="badge bg-secondary">No Information</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $d->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $d->notes }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No details available</td>
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
