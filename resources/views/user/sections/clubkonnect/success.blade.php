@extends('layouts.app')

@section('title', 'Airtime Purchase Successful')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="card shadow">
                    <div class="card-header bg-success text-center text-white">
                        <h4>Airtime Purchase Successful</h4>
                    </div>

                    <div class="card-body">
                        <p>Thank you. Your airtime purchase has been completed successfully.</p>

                        <div class="table-responsive">
                            <table class="table-bordered table">
                                <tr>
                                    <th>Reference</th>
                                    <td>{{ $transaction->reference }}</td>
                                </tr>
                                <tr>
                                    <th>Network</th>
                                    <td>{{ $transaction->network }}</td>
                                </tr>
                                <tr>
                                    <th>Phone Number</th>
                                    <td>{{ $transaction->phone }}</td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>₦{{ number_format($transaction->amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><span class="badge bg-success">SUCCESSFUL</span></td>
                                </tr>
                            </table>
                        </div>

                        <div class="mt-3 text-center">
                            <a class="btn btn-primary" href="{{ route('user.airtime.receipt', $transaction->id) }}">
                                Download Receipt (PDF)
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
