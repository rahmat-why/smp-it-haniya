@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Edit Attendance — {{ $class->class_name }} on {{ date('d M Y', strtotime($date)) }}</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('employee.attendances.store') }}" method="POST">
                        @csrf

                        <input type="hidden" name="class_id" value="{{ $class->class_id }}">
                        <input type="hidden" name="attendance_date" value="{{ $date }}">

                        <div class="mb-3">
                            <label class="form-label">Student Attendance</label>
                            <div class="border rounded p-3" style="max-height: 600px; overflow-y: auto;">
                                @if (empty($students))
                                    <p class="text-muted">No students found for this class.</p>
                                @else
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:12%">NIS</th>
                                                <th style="width:38%">Name</th>
                                                <th style="width:30%">Absence</th>
                                                <th style="width:20%">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($students as $s)
                                                @php
                                                    // Each $s should contain student_class_id, student_id, first_name, last_name
                                                    // and if existing attendance detail was found, attendance_detail_id, status, notes
                                                    $id = $s->student_class_id;
                                                    $nis = $s->student_id;
                                                    $name = $s->first_name . ' ' . $s->last_name;
                                                    $status = $s->status ?? 'Present';
                                                    $notes = $s->notes ?? '';
                                                @endphp
                                                <tr>
                                                    <td><span class="badge bg-info">{{ $nis }}</span></td>
                                                    <td>{{ $name }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group" aria-label="absence-{{ $id }}">
                                                            <input type="radio" class="btn-check" name="attendances[{{ $id }}][status]" id="status_present_{{ $id }}" value="Present" autocomplete="off" {{ $status === 'Present' ? 'checked' : '' }}>
                                                            <label class="btn btn-sm btn-outline-success" for="status_present_{{ $id }}">Present</label>

                                                            <input type="radio" class="btn-check" name="attendances[{{ $id }}][status]" id="status_sick_{{ $id }}" value="Sick" autocomplete="off" {{ $status === 'Sick' ? 'checked' : '' }}>
                                                            <label class="btn btn-sm btn-outline-warning" for="status_sick_{{ $id }}">Sick</label>

                                                            <input type="radio" class="btn-check" name="attendances[{{ $id }}][status]" id="status_permit_{{ $id }}" value="Permit" autocomplete="off" {{ $status === 'Permit' ? 'checked' : '' }}>
                                                            <label class="btn btn-sm btn-outline-primary" for="status_permit_{{ $id }}">Permit</label>

                                                            <input type="radio" class="btn-check" name="attendances[{{ $id }}][status]" id="status_none_{{ $id }}" value="No Information" autocomplete="off" {{ $status === 'No Information' ? 'checked' : '' }}>
                                                            <label class="btn btn-sm btn-outline-secondary" for="status_none_{{ $id }}">No Information</label>
                                                        </div>
                                                        <input type="hidden" name="attendances[{{ $id }}][student_class_id]" value="{{ $id }}">
                                                    </td>
                                                    <td>
                                                        <input type="text" id="notes-{{ $id }}" name="attendances[{{ $id }}][notes]" class="form-control form-control-sm" placeholder="Notes (optional)" value="{{ $notes }}" {{ $status === 'Present' ? 'readonly' : '' }}>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="{{ route('employee.attendances.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle notes readonly on change (client-side enhancement)
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('[id^="notes-"]');
        rows.forEach(input => {
            const id = input.id.replace('notes-','');
            ['present','sick','permit','none'].forEach(kind => {
                const el = document.getElementById('status_' + kind + '_' + id);
                if (el) {
                    el.addEventListener('change', function() {
                        const notes = document.getElementById('notes-' + id);
                        if (this.value === 'Present') {
                            notes.readOnly = true;
                            notes.value = '';
                        } else {
                            notes.readOnly = false;
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
