@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payment Management</h5>
                    <a href="{{ route('employee.payments.create') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Create Payment
                    </a>
                </div>
                <div class="card-body">
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Payment Type</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Remaining</th>
                                    <th>Status</th>
                                    <th>Installments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $payment)
                                    <tr>
                                        <td>
                                            <strong>{{ $payment->first_name }} {{ $payment->last_name }}</strong>
                                            <br><small class="text-muted">{{ $payment->class_name }}</small>
                                        </td>
                                        <td>{{ $payment->payment_type }}</td>
                                        <td><strong>Rp {{ number_format($payment->total_payment, 2, ',', '.') }}</strong></td>
                                        <td>Rp {{ number_format($payment->paid_amount ?? 0, 2, ',', '.') }}</td>
                                        <td>
                                            <span class="badge bg-warning">
                                                Rp {{ number_format($payment->remaining_payment, 2, ',', '.') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($payment->status === 'Paid')
                                                <span class="badge bg-success">Paid</span>
                                            @elseif ($payment->status === 'Partial')
                                                <span class="badge bg-info">Partial</span>
                                            @else
                                                <span class="badge bg-danger">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $payment->installment_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('employee.payments.show', $payment->payment_id) }}" 
                                                   class="btn btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('employee.payments.edit', $payment->payment_id) }}" 
                                                   class="btn btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('employee.payments.destroy', $payment->payment_id) }}" 
                                                      method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="Delete"
                                                            onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"></i> No payments found
                                        </td>
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
