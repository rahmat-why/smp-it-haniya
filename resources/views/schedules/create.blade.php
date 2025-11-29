@extends('layouts.app')

@section('title', 'Create Schedule')
@section('page-title', 'Create Schedule')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-lg-12">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Create Schedule</h5>
                </div>

                <div class="card-body">

                    <div id="form-errors"></div>

                    <form action="{{ route('employee.schedules.store') }}" method="POST" id="schedule-form">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Class (Academic) <span class="text-danger">*</span>
                                </label>
                                <select name="academic_class_id" class="form-select" required>
                                    <option value="">-- Select --</option>
                                    @foreach($classes as $c)
                                        <option value="{{ $c->academic_class_id }}">
                                            {{ $c->class_id }} - {{ $c->academic_class_id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @php
                            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
                        @endphp

                        @foreach($days as $day)
                        <div class="card border mb-4">
                            <div class="card-header bg-light fw-bold">
                                {{ $day }}
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle day-table"
                                           data-day="{{ $day }}">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 28%">Subject</th>
                                                <th style="width: 28%">Teacher</th>
                                                <th style="width: 16%">Start Time</th>
                                                <th style="width: 16%">End Time</th>
                                                <th style="width: 10%"></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <button type="button"
                                        class="btn btn-outline-primary btn-sm add-detail-btn"
                                        data-day="{{ $day }}">
                                    + Add Detail for {{ $day }}
                                </button>
                            </div>
                        </div>
                        @endforeach

                        <div class="mt-4">
                            <button type="submit" id="save-schedule-btn" class="btn btn-primary px-4">
                                Save Schedule
                            </button>
                            <a href="{{ route('employee.schedules.index') }}" class="btn btn-secondary px-4">
                                Cancel
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

{{-- TEMPLATE ROW --}}
<template id="detail-row-template">
    <tr>
        <td>
            <select class="form-select form-select-sm subject-select" required>
                <option value="">-- Subject --</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->subject_id }}">{{ $s->subject_name }}</option>
                @endforeach
            </select>
        </td>

        <td>
            <select class="form-select form-select-sm teacher-select" required>
                <option value="">-- Teacher --</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->teacher_id }}">
                        {{ $t->first_name }} {{ $t->last_name }}
                    </option>
                @endforeach
            </select>
        </td>

        <td><input type="time" class="form-control form-control-sm start-time" required></td>

        <td><input type="time" class="form-control form-control-sm end-time" required></td>

        <td>
            <button type="button" class="btn btn-danger btn-sm remove-detail">Delete</button>
        </td>
    </tr>
</template>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function(){

    function addRow(day) {
        let table = $(`table[data-day="${day}"] tbody`);
        let index = table.children().length;

        const clone = $('#detail-row-template')[0].content.cloneNode(true);

        $(clone).find('select.subject-select').attr('name', `details[${day}][${index}][subject_id]`);
        $(clone).find('select.teacher-select').attr('name', `details[${day}][${index}][teacher_id]`);
        $(clone).find('input.start-time').attr('name', `details[${day}][${index}][start_time]`);
        $(clone).find('input.end-time').attr('name', `details[${day}][${index}][end_time]`);

        table.append(clone);
    }

    $('.add-detail-btn').click(function () {
        addRow($(this).data('day'));
    });

    $('body').on('click', '.remove-detail', function(){
        $(this).closest('tr').remove();
    });

});
</script>

@endsection
