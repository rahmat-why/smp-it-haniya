@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Attendance Details - {{ $header->attendance_id }}</h5>
                    <a href="{{ route('employee.attendances.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Date:</strong> {{ date('d M Y', strtotime($header->attendance_date)) }}
                        @if(isset($header->academic_class_id))
                            | <strong>Class Academic ID:</strong> {{ $header->academic_class_id }}
                        @endif
                    </p>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($details as $d)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><span class="badge bg-info">{{ $d->student_id }}</span></td>
                                        <td>{{ $d->first_name }} {{ $d->last_name }}</td>
                                        <td>{{ $d->status }}</td>
                                        <td>{{ $d->notes ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No detail records found.</td>
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
