@extends('layouts.app')

@section('title', 'Classes')
@section('page-title', 'Class Management')

@section('content')
<div class="container-fluid">

    <!-- MAIN CARD -->
    <div class="card shadow-lg border-0 rounded-3 overflow-hidden">

        <!-- HEADER -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-chalkboard text-primary"></i> Classes
            </h5>
            <a href="{{ route('employee.classes.create') }}" 
               class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-plus"></i> Add New Class
            </a>
        </div>

        <!-- BODY -->
        <div class="card-body px-4">

            <!-- SUCCESS & ERROR ALERT -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-times-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- DATATABLE WRAPPER -->
            <div class="bg-white border rounded p-3 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="classesTable">
                        <thead class="table-light text-center">
                            <tr>
                                
                                <th>Class Name</th>
                                <th>Class Level</th>
                                <th width="70" class="text-center">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function() {

    const csrfToken = '{{ csrf_token() }}';

    $('#classesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('employee.classes.data') }}",
        columns: [
      
            { data: 'class_name' },
            { data: 'class_level' },
            {
                data: 'class_id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(id) {
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light shadow-sm border dropdown-toggle" 
                                    type="button" data-bs-toggle="dropdown">
                                ⋮
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="/classes/edit/${id}">
                                        <i class="fas fa-edit text-primary"></i> Edit
                                    </a>
                                </li>
                                <li>
                                    <button class="dropdown-item text-danger btn-delete" data-id="${id}">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </li>
                            </ul>
                        </div>`;
                }
            }
        ],
        order: [[0,'desc']],
        autoWidth: false
    });

    // DELETE ACTION
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        if(!confirm('Delete this class?')) return;

        $.ajax({
            url: `/classes/${id}`,
            type: 'POST',
            data: {
                _token: csrfToken,
                _method: 'DELETE'
            },
            success: function() {
                $('#classesTable').DataTable().ajax.reload(null,false);
            },
            error: function(xhr){
                let message = "Failed to delete class";
                if(xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
                alert(message);
            }
        });
    });

});
</script>
@endpush
