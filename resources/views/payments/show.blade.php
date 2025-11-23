@extends('layouts.app')

@section('content')
<div class="container">

    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Payment Details</h5>
        </div>

        <div class="card-body">

            {{-- Payment Summary --}}
            <h5>{{ $payment->item_name }}</h5>

            <p id="totalPayment"><strong>Total Payment:</strong> 
                Rp {{ number_format($payment->total_payment, 2, ',', '.') }}
            </p>

            <p id="paidAmount"><strong>Paid:</strong> 
                Rp {{ number_format($payment->instalments->sum('total_payment'), 2, ',', '.') }}
            </p>

            <p id="remainingPayment"><strong>Remaining:</strong> 
                Rp {{ number_format($payment->remaining_payment, 2, ',', '.') }}
            </p>

            <p id="statusPayment"><strong>Status:</strong> {{ $payment->status }}</p>

            <hr>

            @if(strtolower($payment->payment_method) === 'instalment')

                <h5>Installment History</h5>

                <form id="instalmentForm" method="POST" action="{{ route('employee.payments.store-instalment', $payment->payment_id) }}">
                    @csrf

                    <table class="table table-bordered" id="instalmentTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Note</th>
                            </tr>
                        </thead>

                        <tbody id="instalmentBody">

                            {{-- Existing instalments --}}
                            @foreach ($payment->instalments as $i => $inst)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($inst->payment_date)->format('d M Y') }}</td>
                                    <td>Rp {{ number_format($inst->total_payment, 2, ',', '.') }}</td>
                                    <td>{{ $inst->notes }}</td>
                                </tr>
                            @endforeach

                            {{-- New instalment row --}}
                            <tr id="newRow" class="bg-light">
                                <td id="nextNo">{{ $payment->instalments->count() + 1 }}</td>

                                <td>
                                    <input type="date" name="payment_date" class="form-control" required>
                                </td>

                                <td>
                                    <input type="number" step="0.01" name="total_payment" class="form-control" required>
                                </td>

                                <td>
                                    <input type="text" name="notes" class="form-control">
                                </td>
                                <input type="hidden" name="installment_number" value="{{ $payment->instalments->count() + 1 }}">
                            </tr>

                        </tbody>
                    </table>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success">
                            Save Instalment
                        </button>
                    </div>
                </form>

            @endif

        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
$('#instalmentForm').on('submit', function(e){
    e.preventDefault();

    let formData = new FormData(this);
    $.ajax({
        url: "{{ route('employee.payments.store-instalment', $payment->payment_id) }}",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(data){

            if (!data.success) return;

            // Format amount
            let amountFormatted = parseFloat(data.instalment.total_payment)
                .toLocaleString('id-ID', { minimumFractionDigits: 2 });

            // Add new row
            let row = `
                <tr>
                    <td>${data.instalment.instalment_number}</td>
                    <td>${new Date(data.instalment.payment_date).toLocaleDateString('id-ID')}</td>
                    <td>Rp ${amountFormatted}</td>
                    <td>${data.instalment.notes ?? ''}</td>
                </tr>
            `;

            $('#newRow').before(row);

            // Update summary
            $('#paidAmount').html("<strong>Paid:</strong> Rp " + 
                parseFloat(data.paid_total).toLocaleString('id-ID', { minimumFractionDigits: 2 })
            );

            $('#remainingPayment').html("<strong>Remaining:</strong> Rp " + 
                parseFloat(data.remaining).toLocaleString('id-ID', { minimumFractionDigits: 2 })
            );

            $('#statusPayment').html("<strong>Status:</strong> " + data.status);

            // Reset inputs
            $('input[name="payment_date"]').val('');
            $('input[name="total_payment"]').val('');
            $('input[name="notes"]').val('');

            // Update next instalment number
            $('#nextNo').text(data.instalment.instalment_number + 1);
        }
    });
});
</script>
@endsection