@extends('user.layouts.master')

@section('content')
<div class="container py-5 d-flex justify-content-center">
    <div class="card shadow-lg border-0 rounded-4 mx-auto" style="max-width: 720px;">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:72px;height:72px;background:linear-gradient(135deg,#2dd4bf,#06b6d4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="white" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 11.03a.75.75 0 0 0 1.06 0L12.03 7a.75.75 0 1 0-1.06-1.06L7.5 9.44 5.03 6.97A.75.75 0 1 0 3.97 8.03l2.99 2.99z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="mb-0 fw-bold text-success">Subscription Successful</h4>
                    <p class="mb-0 text-muted small">Your data purchase was processed successfully.</p>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-12 col-md-7">
                    <ul class="list-group list-group-flush shadow-sm rounded-3 overflow-hidden">
                        <li class="list-group-item d-flex justify-content-between align-items-start px-4 py-3">
                            <div>
                                <div class="small text-muted">Transaction ID</div>
                                <div class="fw-medium" id="tx-id">{{ $transaction['transactionId'] ?? 'N/A' }}</div>
                            </div>
                            <div class="text-end">
                                <button id="copyTx" class="btn btn-sm btn-outline-secondary">Copy</button>
                                <div id="copyMsg" class="small text-success mt-1 d-none">Copied</div>
                            </div>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                            <div class="small text-muted">Amount</div>
                            <div class="fw-semibold">₦{{ number_format($transaction['amount'] ?? 0, 2) }}</div>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                            <div class="small text-muted">Phone</div>
                            <div class="fw-medium">{{ $transaction['unique_element'] ?? 'N/A' }}</div>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                            <div class="small text-muted">Status</div>
                            <div>
                                <span class="badge bg-{{ ($transaction['status'] ?? 'pending') === 'success' ? 'success' : 'warning' }}">
                                    {{ ucfirst($transaction['status'] ?? 'pending') }}
                                </span>
                            </div>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                            <div class="small text-muted">Service</div>
                            <div class="fw-medium">{{ strtoupper($service ?? '') }}</div>
                        </li>
                    </ul>
                </div>

                <div class="col-12 col-md-5 d-flex flex-column justify-content-between">
                    <div class="p-4 bg-light rounded-3 h-100">
                        <h6 class="mb-2">Receipt</h6>
                        <p class="small text-muted mb-3">Save your receipt for future reference or share it with support if needed.</p>
                        <div class="d-grid gap-2">
                            <a href="{{ route('user.data.index') }}" class="btn btn-primary btn-block">Buy Another Data</a>
                            {{-- <a href="{{ setRoute('index') }}" class="btn btn-outline-secondary btn-block">Return Home</a> --}}
                        </div>
                    </div>
                    <div class="text-center mt-3 small text-muted">
                        If you need help, contact <a href="mailto:{{ $basic_settings->support_email ?? 'support@example.com' }}">support</a>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyBtn = document.getElementById('copyTx');
    const txIdEl = document.getElementById('tx-id');
    const copyMsg = document.getElementById('copyMsg');

    if (!copyBtn || !txIdEl) return;

    copyBtn.addEventListener('click', async function () {
        const text = txIdEl.textContent.trim();
        if (!text || text === 'N/A') return;

        try {
            await navigator.clipboard.writeText(text);
            copyMsg.classList.remove('d-none');
            setTimeout(() => copyMsg.classList.add('d-none'), 1500);
        } catch (e) {
            // fallback
            const input = document.createElement('textarea');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            copyMsg.classList.remove('d-none');
            setTimeout(() => copyMsg.classList.add('d-none'), 1500);
        }
    });
});
</script>
@endpush