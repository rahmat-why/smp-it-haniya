@extends('layouts.app')

@section('title', 'Edit Schedule')
@section('page-title', 'Schedule Management')

@section('content')
<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-calendar-alt text-primary"></i> Edit Schedule
            </h5>
        </div>

        <!-- BODY -->
        <div class="card-body px-4">

            <!-- VALIDATION ERROR -->
            <div id="form-errors"></div>

            <!-- FORM WRAPPER -->
            <div class="bg-white border rounded p-4 shadow-sm">

                <form action="{{ route('employee.schedules.update', ['id' => $schedule->schedule_id]) }}" 
                      method="POST" 
                      id="schedule-form">

                    @csrf
                    @method('PATCH')

                    <div class="row mb-4">
                        <!-- CLASS -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                Class (Academic) <span class="text-danger">*</span>
                            </label>
                            <select name="academic_class_id" class="form-select" required
                                    oninvalid="this.classList.add('is-invalid')"
                                    oninput="this.classList.remove('is-invalid')">
                                <option value="">-- Select --</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->academic_class_id }}" 
                                        {{ $schedule->academic_class_id == $c->academic_class_id ? 'selected' : '' }}>
                                        {{ $c->class_id }} - {{ $c->academic_class_id }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block">
                                Required. Select the academic class for the schedule.
                            </small>
                        </div>

                        <!-- DAY -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                Day <span class="text-danger">*</span>
                            </label>
                            <select name="day" class="form-select" required
                                    oninvalid="this.classList.add('is-invalid')"
                                    oninput="this.classList.remove('is-invalid')">
                                <option value="">-- Select Day --</option>
                                @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
                                    <option value="{{ $d }}" {{ $schedule->day == $d ? 'selected' : '' }}>
                                        {{ $d }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block">
                                Required. Select the day for the schedule.
                            </small>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3">Schedule Details</h6>

                    <!-- TABLE -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="details-table">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th width="70">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 0; @endphp
                                @foreach($details as $d)
                                    <tr>
                                        <td>
                                            <select name="details[{{ $i }}][subject_id]" class="form-select form-select-sm" required
                                                    oninvalid="this.classList.add('is-invalid')"
                                                    oninput="this.classList.remove('is-invalid')">
                                                <option value="">-- Subject --</option>
                                                @foreach($subjects as $s)
                                                    <option value="{{ $s->subject_id }}" 
                                                        {{ $d->subject_id == $s->subject_id ? 'selected' : '' }}>
                                                        {{ $s->subject_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted d-block">Required. Select a subject.</small>
                                        </td>
                                        <td>
                                            <select name="details[{{ $i }}][teacher_id]" class="form-select form-select-sm" required
                                                    oninvalid="this.classList.add('is-invalid')"
                                                    oninput="this.classList.remove('is-invalid')">
                                                <option value="">-- Teacher --</option>
                                                @foreach($teachers as $t)
                                                    <option value="{{ $t->teacher_id }}" 
                                                        {{ $d->teacher_id == $t->teacher_id ? 'selected' : '' }}>
                                                        {{ $t->first_name }} {{ $t->last_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted d-block">Required. Select a teacher.</small>
                                        </td>
                                        <td>
                                            <input type="time" name="details[{{ $i }}][start_time]" 
                                                   class="form-control form-control-sm" 
                                                   value="{{ \Carbon\Carbon::parse($d->start_time)->format('H:i') }}" required
                                                   oninvalid="this.classList.add('is-invalid')"
                                                   oninput="this.classList.remove('is-invalid')">
                                            <small class="text-muted d-block">Required. Enter start time.</small>
                                        </td>
                                        <td>
                                            <input type="time" name="details[{{ $i }}][end_time]" 
                                                   class="form-control form-control-sm" 
                                                   value="{{ \Carbon\Carbon::parse($d->end_time)->format('H:i') }}" required
                                                   oninvalid="this.classList.add('is-invalid')"
                                                   oninput="this.classList.remove('is-invalid')">
                                            <small class="text-muted d-block">Required. Enter end time.</small>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger remove-detail">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @php $i++; @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="button" id="add-detail" class="btn btn-sm btn-outline-primary shadow-sm">
                        <i class="fas fa-plus"></i> Add Detail
                    </button>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" id="save-schedule-btn" class="btn btn-primary shadow-sm">
                            <i class="fas fa-save"></i> Save Schedule
                        </button>
                        <a href="{{ route('employee.schedules.index') }}" class="btn btn-secondary shadow-sm">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<!-- TEMPLATE -->
<template id="detail-row-template">
    <tr>
        <td>
            <select name="details[__INDEX__][subject_id]" class="form-select form-select-sm" required
                    oninvalid="this.classList.add('is-invalid')"
                    oninput="this.classList.remove('is-invalid')">
                <option value="">-- Subject --</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->subject_id }}">{{ $s->subject_name }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block">Required. Select a subject.</small>
        </td>
        <td>
            <select name="details[__INDEX__][teacher_id]" class="form-select form-select-sm" required
                    oninvalid="this.classList.add('is-invalid')"
                    oninput="this.classList.remove('is-invalid')">
                <option value="">-- Teacher --</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->teacher_id }}">{{ $t->first_name }} {{ $t->last_name }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block">Required. Select a teacher.</small>
        </td>
        <td><input type="time" name="details[__INDEX__][start_time]" class="form-control form-control-sm" required
                   oninvalid="this.classList.add('is-invalid')"
                   oninput="this.classList.remove('is-invalid')"><small class="text-muted d-block">Required. Enter start time.</small></td>
        <td><input type="time" name="details[__INDEX__][end_time]" class="form-control form-control-sm" required
                   oninvalid="this.classList.add('is-invalid')"
                   oninput="this.classList.remove('is-invalid')"><small class="text-muted d-block">Required. Enter end time.</small></td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger remove-detail">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

@push('scripts')
<script>
$(function() {
    var $tbody = $('#details-table tbody');
    var tpl = document.getElementById('detail-row-template');
    var idx = $tbody.find('tr').length || 0;

    function addRow() {
        var cloneEl = tpl.content.firstElementChild.cloneNode(true);
        $(cloneEl).find('[name]').each(function() {
            $(this).attr('name', $(this).attr('name').replace(/__INDEX__/g, idx));
        });
        $tbody.append(cloneEl);
        idx++;
    }

    $('#add-detail').on('click', function(e) {
        e.preventDefault();
        addRow();
    });

    $('#details-table').on('click', '.remove-detail', function() {
        $(this).closest('tr').remove();
    });

    if ($tbody.find('tr').length === 0) addRow();

    $('#schedule-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#save-schedule-btn');
        $btn.prop('disabled', true).text('Saving...');
        $('#form-errors').empty();

        var fd = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            method: $(this).attr('method') || 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType:'json'
        }).done(function(resp){
            if (resp.redirect) {
                window.location = resp.redirect;
                return;
            }
            $('#form-errors').html('<div class="alert alert-success shadow-sm"><i class="fas fa-check-circle"></i> Saved.</div>');
            $btn.prop('disabled', false).text('Save Schedule');
        }).fail(function(xhr){
            var content = '';
            if (xhr.status === 422 && xhr.responseJSON.errors) {
                content = '<div class="alert alert-danger shadow-sm"><ul class="mb-0">';
                $.each(xhr.responseJSON.errors, function(k,v){
                    content += '<li>' + (v.join ? v.join(', ') : v) + '</li>';
                });
                content += '</ul></div>';
            } else {
                content = '<div class="alert alert-danger shadow-sm">Server error. Please try again.</div>';
            }
            $('#form-errors').html(content);
            $btn.prop('disabled', false).text('Save Schedule');
        });
    });
});
</script>
@endpush

@endsection
