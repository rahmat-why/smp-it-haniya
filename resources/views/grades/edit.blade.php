@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Edit Grades</h5>
                </div>

                <div class="card-body">

                    <form id="grade-form" action="{{ route('employee.grades.update', $grade->grade_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Class</label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">-- Select Class --</option>
                                @foreach ($classes as $c)
                                <option value="{{ $c->class_id }}" 
                                    {{ $c->class_id == $grade->class_id ? 'selected' : '' }}>
                                    {{ $c->class_name }} (Level {{ $c->class_level }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select class="form-select" name="subject_id" required>
                                <option value="">-- Select Subject --</option>
                                @foreach ($subjects as $s)
                                <option value="{{ $s->subject_id }}" 
                                    {{ $s->subject_id == $grade->subject_id ? 'selected' : '' }}>
                                    {{ $s->subject_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Teacher</label>
                            <select class="form-select" name="teacher_id" required>
                                <option value="">-- Select Teacher --</option>
                                @foreach ($teachers as $t)
                                <option value="{{ $t->teacher_id }}" 
                                    {{ $t->teacher_id == $grade->teacher_id ? 'selected' : '' }}>
                                    {{ $t->first_name }} {{ $t->last_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Grade Type</label>
                            <select class="form-select" name="grade_type" required>
                                <option value="">-- Select Type --</option>
                                @foreach ($gradeTypes as $gt)
                                <option value="{{ $gt->item_code }}"
                                    {{ $gt->item_code == $grade->grade_type ? 'selected' : '' }}>
                                    {{ $gt->item_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Student values --}}
                        <div class="mb-3">
                            <label class="form-label">Student Grades</label>
                            <div class="border rounded p-3" style="max-height:400px; overflow-y:auto;">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>NIS</th>
                                            <th>Name</th>
                                            <th>Grade</th>
                                            <th>Attitude</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($details as $d)
                                        <tr>
                                            <td><span class="badge bg-info">{{ $d->student_id }}</span></td>
                                            <td>{{ $d->first_name }} {{ $d->last_name }}</td>

                                            <td>
                                                <input type="number" step="0.01"
                                                    name="grades[{{ $d->student_class_id }}][grade_value]"
                                                    class="form-control form-control-sm"
                                                    value="{{ $d->grade_value }}">
                                            </td>

                                            <td>
                                                <input type="text"
                                                    name="grades[{{ $d->student_class_id }}][grade_attitude]"
                                                    class="form-control form-control-sm"
                                                    value="{{ $d->grade_attitude }}">
                                            </td>

                                            <td>
                                                <input type="text"
                                                    name="grades[{{ $d->student_class_id }}][notes]"
                                                    class="form-control form-control-sm"
                                                    value="{{ $d->notes }}">
                                            </td>

                                            <input type="hidden"
                                                name="grades[{{ $d->student_class_id }}][student_class_id]"
                                                value="{{ $d->student_class_id }}">
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="save-btn">
                                <i class="fas fa-save"></i> Update Grades
                            </button>
                            <a href="{{ route('employee.grades.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>

                    </form>

                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).on('submit', '#grade-form', function(e){
    e.preventDefault();
    const $form = $(this);
    const $btn = $('#save-btn');
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        dataType: 'json'
    }).done(function(res){
        window.location.href = res.redirect;
    }).fail(function(xhr){
        alert(xhr.responseJSON?.message || 'Update failed');
    }).always(function(){
        $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update Grades');
    });
});
</script>
@endpush
@endsection