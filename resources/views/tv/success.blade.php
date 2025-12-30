@extends('user.layouts.master')

@push('css')

@endpush


@section('content')
<div class="container py-5 text-center">
    <div class="card shadow-lg">
        <div class="card-body">
            <h3 class="text-success mb-3">🎉 Subscription Successful!</h3>
            <p><strong>Service:</strong> {{ strtoupper($service) }}</p>
            <p><strong>Transaction ID:</strong> {{ $transaction['transactionId'] ?? 'N/A' }}</p>
            <p><strong>Amount:</strong> ₦{{ number_format($transaction['amount'] ?? 0, 2) }}</p>

            <a href="{{ route('user.cabletv.plans', $service) }}" class="btn btn-outline-primary mt-3">Back to Plans</a>
        </div>
    </div>
</div>
@endsection
