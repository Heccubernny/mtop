@extends('user.layouts.master')

@section('title', 'Airtime Purchase Successful')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="card rounded-3 overflow-hidden border-0 shadow-sm">

                    {{-- HEADER --}}
                    <div class="bg-success py-4 text-center text-white">
                        <div class="mb-2">
                            <i class="las la-check-circle fs-1"></i>
                        </div>
                        <h4 class="mb-0">Airtime Purchase Successful</h4>
                        <small>Your transaction has been completed</small>
                    </div>

                    {{-- BODY --}}
                    <div class="card-body p-4">

                        <div class="mb-4 text-center">
                            <p class="text-muted mb-0">
                                Thank you for using our service. Below is a summary of your transaction.
                            </p>
                        </div>

                        {{-- RECEIPT SUMMARY --}}
                        <div class="table-responsive">
                            <table class="table-borderless table align-middle">
                                <tbody>
                                    <tr>
                                        <th class="text-muted w-50">Transaction Reference</th>
                                        <td class="fw-semibold">{{ $transaction->response_body['orderid'] }}</td>
                                    </tr>

                                    <tr>
                                        <th class="text-muted">Network</th>
                                        <td>{{ $transaction->network }}</td>
                                    </tr>

                                    <tr>
                                        <th class="text-muted">Phone Number</th>
                                        <td>{{ $transaction->mobile }}</td>
                                    </tr>

                                    <tr>
                                        <th class="text-muted">Amount Paid</th>
                                        <td class="fw-bold text-success">
                                            ₦{{ number_format($transaction->amount, 2) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <th class="text-muted">Status</th>
                                        <td>
                                            <span class="badge bg-success px-3 py-2">
                                                SUCCESSFUL
                                            </span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th class="text-muted">Date</th>
                                        <td>{{ $transaction->created_at->format('d M, Y h:i A') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- ACTION BUTTONS --}}
                        <div class="d-flex justify-content-center mt-4 flex-wrap gap-3">
                            <a class="btn btn-primary px-4" href="{{ route('user.airtime.receipt', $transaction->id) }}">
                                <i class="las la-file-pdf me-1"></i>
                                Download Receipt
                            </a>

                            <a class="btn btn-outline-secondary px-4" href="{{ route('user.dashboard') }}">
                                <i class="las la-home me-1"></i>
                                Back to Dashboard
                            </a>
                        </div>

                    </div>
                </div>

                {{-- FOOT NOTE --}}
                <div class="text-muted small mt-3 text-center">
                    Need help? Contact support if you have any issue with this transaction.
                </div>

            </div>
        </div>
    </div>
@endsection
