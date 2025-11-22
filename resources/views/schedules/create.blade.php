@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Create Schedule</h5>
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

                    {{-- AJAX error container (will be filled by JS on validation/server errors) --}}
                    <div id="form-errors"></div>

                    <form action="{{ route('employee.schedules.store') }}" method="POST" id="schedule-form">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Class (Academic)</label>
                                <select name="academic_class_id" class="form-select" required>
                                    <option value="">-- Select --</option>
                                    @foreach($classes as $c)
                                        <option value="{{ $c->academic_class_id }}">{{ $c->class_id }} - {{ $c->academic_class_id }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Day</label>
                                <select name="day" class="form-select" required>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
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
                                    <!-- template row -->
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    console.log('schedules.create script loaded, jQuery version:', typeof jQuery !== 'undefined' ? jQuery.fn.jquery : 'no-jquery');

    var idx = 0;
    var $tbody = $('#details-table tbody');
    var tpl = document.getElementById('detail-row-template');

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

    // initial row
    addRow();

    // AJAX submit handler
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