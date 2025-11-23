@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Grades</h5>
            <a href="{{ route('employee.grades.create') }}" class="btn btn-primary btn-sm">Add Grades</a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Teacher</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($grades as $g)
                        <tr>
                            <td>{{ $g->class_name }}</td>
                            <td>{{ $g->subject_name }}</td>
                            <td>{{ $g->grade_type }}</td>
                            <td>{{ $g->teacher_name }}</td>

                            <td>
                                <a 
                                    href="{{ route('employee.grades.edit', $g->grade_id) }}" 
                                    class="btn btn-sm btn-primary"
                                >
                                    Edit
                                </a>

                                <form 
                                    action="{{ route('employee.grades.destroy', $g->grade_id) }}" 
                                    method="POST" 
                                    class="d-inline"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button 
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete grade?')"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach

                        @if (count($grades) == 0)
                        <tr>
                            <td colspan="7" class="text-center text-muted">No grades found.</td>
                        </tr>
                        @endif
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>
@endsection