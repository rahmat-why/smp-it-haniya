@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Edit Schedule</h5>
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

                    <div id="form-errors"></div>

                    <form action="{{ route('employee.schedules.update', ['id' => $schedule->schedule_id]) }}" method="POST" id="schedule-form">
                        @csrf
                        @method('PATCH')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Class (Academic)</label>
                                <select name="academic_class_id" class="form-select" required>
                                    <option value="">-- Select --</option>
                                    @foreach($classes as $c)
                                        <option value="{{ $c->academic_class_id }}" {{ $schedule->academic_class_id == $c->academic_class_id ? 'selected' : '' }}>{{ $c->class_id }} - {{ $c->academic_class_id }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Day</label>
                                <select name="day" class="form-select" required>
                                    @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
                                        <option value="{{ $d }}" {{ $schedule->day == $d ? 'selected' : '' }}>{{ $d }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <h6>Schedule Details</h6>
                        <div class="table-responsive">
                            <table class="table table-sm" id="details-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $i = 0; @endphp
                                    @foreach($details as $d)
                                        <tr>
                                            <td>
                                                <select name="details[{{ $i }}][subject_id]" class="form-select form-select-sm" required>
                                                    <option value="">-- Subject --</option>
                                                    @foreach($subjects as $s)
                                                        <option value="{{ $s->subject_id }}" {{ $d->subject_id == $s->subject_id ? 'selected' : '' }}>{{ $s->subject_name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="details[{{ $i }}][teacher_id]" class="form-select form-select-sm" required>
                                                    <option value="">-- Teacher --</option>
                                                    @foreach($teachers as $t)
                                                        <option value="{{ $t->teacher_id }}" {{ $d->teacher_id == $t->teacher_id ? 'selected' : '' }}>{{ $t->first_name }} {{ $t->last_name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="time" name="details[{{ $i }}][start_time]" class="form-control form-control-sm" value="{{ \Carbon\Carbon::parse($d->start_time)->format('H:i') }}" required></td>
                                            <td><input type="time" name="details[{{ $i }}][end_time]" class="form-control form-control-sm" value="{{ \Carbon\Carbon::parse($d->end_time)->format('H:i') }}" required></td>
                                            <td><button type="button" class="btn btn-sm btn-danger remove-detail">Remove</button></td>
                                        </tr>
                                        @php $i++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" id="add-detail" class="btn btn-sm btn-outline-primary">Add Detail</button>

                        <div class="mt-3">
                            <button type="submit" id="save-schedule-btn" class="btn btn-primary">Save Schedule</button>
                            <a href="{{ route('employee.schedules.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="detail-row-template">
    <tr>
        <td>
            <select name="details[__INDEX__][subject_id]" class="form-select form-select-sm" required>
                <option value="">-- Subject --</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->subject_id }}">{{ $s->subject_name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="details[__INDEX__][teacher_id]" class="form-select form-select-sm" required>
                <option value="">-- Teacher --</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->teacher_id }}">{{ $t->first_name }} {{ $t->last_name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="time" name="details[__INDEX__][start_time]" class="form-control form-control-sm" required></td>
        <td><input type="time" name="details[__INDEX__][end_time]" class="form-control form-control-sm" required></td>
        <td><button type="button" class="btn btn-sm btn-danger remove-detail">Remove</button></td>
    </tr>
</template>

@section('scripts')
<script>
$(function() {
    console.log('schedules.edit script loaded, jQuery version:', typeof jQuery !== 'undefined' ? jQuery.fn.jquery : 'no-jquery');

    var $tbody = $('#details-table tbody');
    var tpl = document.getElementById('detail-row-template');
    // set starting index to existing rows count
    var idx = $tbody.find('tr').length || 0;

    function addRow() {
        console.log('addRow called, idx=', idx);
        var cloneEl = tpl.content.firstElementChild.cloneNode(true);
        $(cloneEl).find('[name]').each(function() {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/__INDEX__/g, idx));
            }
        });

        $tbody.append(cloneEl);
        idx++;
    }

    $('#add-detail').on('click', function(e) {
        e.preventDefault();
        console.log('add-detail clicked');
        addRow();
    });

    // delegate remove handler
    $('#details-table').on('click', '.remove-detail', function(e) {
        e.preventDefault();
        console.log('remove-detail clicked');
        $(this).closest('tr').remove();
    });

    // only add an initial row if there are no existing rows
    if ($tbody.find('tr').length === 0) {
        addRow();
    }

    // AJAX submit handler (reuse same logic as create)
    $('#schedule-form').on('submit', function(e) {
        e.preventDefault();
        console.log('schedule-form submit (ajax)');

        var $btn = $('#save-schedule-btn');
        $btn.prop('disabled', true).text('Saving...');

        // clear previous errors
        $('#form-errors').empty();

        var form = document.getElementById('schedule-form');
        var formData = new FormData(form);

        $.ajax({
            url: $(form).attr('action'),
            method: $(form).attr('method') || 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(resp) {
            console.log('ajax success', resp);
            if (resp.redirect) {
                window.location = resp.redirect;
                return;
            }
            // show success message
            $('#form-errors').html('<div class="alert alert-success">' + (resp.message || 'Saved') + '</div>');
            $btn.prop('disabled', false).text('Save Schedule');
        }).fail(function(xhr) {
            console.log('ajax fail', xhr);
            var content = '';
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                content = '<div class="alert alert-danger"><ul class="mb-0">';
                $.each(xhr.responseJSON.errors, function(k, v) {
                    content += '<li>' + v.join ? v.join(', ') : v + '</li>';
                });
                content += '</ul></div>';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                content = '<div class="alert alert-danger">' + xhr.responseJSON.message + '</div>';
            } else {
                content = '<div class="alert alert-danger">Server error. Please try again.</div>';
            }
            $('#form-errors').html(content);
            $btn.prop('disabled', false).text('Save Schedule');
        });
    });
});
</script>
@endsection

@endsection
