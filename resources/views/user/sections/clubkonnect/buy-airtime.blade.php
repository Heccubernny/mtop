@extends('user.layouts.master')

@push('css')
    <style>
        .custom-alert {
            position: relative;
            padding: 15px 45px 15px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideDown 0.4s ease-out;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            font-weight: 500;
            color: #fff;
        }

        .alert-success {
            background: #28a745;
        }

        .alert-danger {
            background: #dc3545;
        }

        .close-alert {
            position: absolute;
            top: 8px;
            right: 12px;
            background: none;
            border: none;
            color: #fff;
            font-size: 22px;
            cursor: pointer;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-out {
            animation: fadeOut 0.5s forwards;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateY(-6px);
            }
        }
    </style>
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [
            ['name' => __('Dashboard'), 'url' => setRoute('user.dashboard')],
            ['name' => __('List Services'), 'url' => setRoute('user.ck.home')],
        ],
        'active' => __('Buy Airtime'),
    ])
@endsection

@section('content')
    <div class="body-wrapper">

        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ __('Buy Airtime') }}</h3>
            </div>
        </div>

        {{-- SUCCESS / ERROR ALERTS --}}
        {{-- @if (session('success'))
            <div class="custom-alert alert-success" id="alertBox">
                <span>{{ session('success') }}</span>
                <button class="close-alert">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="custom-alert alert-danger" id="alertBox">
                <span>{{ session('error') }}</span>
                <button class="close-alert">&times;</button>
            </div>
        @endif --}}

        <div class="row mb-30-none">
            <div class="col-xl-6 mb-30">

                {{-- MAIN FORM CARD --}}
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">

                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Airtime Purchase Form') }}</h5>
                        </div>

                        <div class="dash-payment-body">
                            <form class="card-form" action="{{ route('user.airtime.buy') }}" method="POST">
                                @csrf
                                @if (session('success'))
                                    <div class="custom-alert alert-success" id="alertBox">
                                        <span>{{ session('success') }}</span>
                                        <button class="close-alert">&times;</button>
                                    </div>
                                @endif

                                @if (session('error'))
                                    <div class="custom-alert alert-danger" id="alertBox">
                                        <span>{{ session('error') }}</span>
                                        <button class="close-alert">&times;</button>
                                    </div>
                                @endif

                                <input id="countryCode" name="country_code" type="hidden" value="NG">
                                <input id="phoneCode" name="phone_code" type="hidden" value="+234">
                                <input id="operator" name="operator" type="hidden">
                                <input id="operatorId" name="operator_id" type="hidden">
                                <input id="exchangeRate" name="exchange_rate" type="hidden">

                                <div class="row">

                                    {{-- NETWORK --}}
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __('Mobile Network') }} <span class="text--base">*</span></label>
                                        <select class="form--control" id="networkSelect" name="network_id" required>
                                            <option value="">-- {{ __('Select Network') }} --</option>
                                            @foreach ($networks as $network)
                                                <option data-code="{{ $network->code }}" value="{{ $network->id }}">
                                                    {{ strtoupper($network->slug) }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <input id="networkCode" name="network" type="hidden">
                                    </div>

                                    {{-- PHONE NUMBER --}}
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __('Mobile Number') }} <span class="text--base">*</span></label>
                                        <input class="form--control" name="mobile" placeholder="08012345678" required>
                                    </div>

                                    {{-- AMOUNT --}}
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __('Amount (₦)') }} <span class="text--base">*</span></label>
                                        <input class="form--control" name="amount" type="number" min="50"
                                            max="200000" placeholder="50 - 200000" required>
                                    </div>

                                    <div class="col-xl-12 col-lg-12 mt-2">
                                        <button class="btn--base w-100" type="submit">
                                            {{ __('Buy Airtime') }}
                                            <i class="las la-phone-volume ms-1"></i>
                                        </button>
                                    </div>

                                </div>
                            </form>
                        </div>

                    </div>
                </div>

            </div>

            {{-- RIGHT SIDE PREVIEW BOX --}}
            <div class="col-xl-6 mb-30">

                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">

                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Preview') }}</h5>
                        </div>

                        <div class="dash-payment-body">
                            <div class="preview-list-wrapper">
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-network-wired"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Network Provider') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-provider">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-sim-card"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Network') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-network">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-phone"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Mobile Number') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-mobile">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-wallet"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Amount') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-amount">--</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>

        {{-- TRANSACTION LOG --}}
        {{-- <div class="dashboard-list-area mt-20">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __('Airtime Purchase Log') }}</h4>

                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn mb-2">
                        <a class="btn--base" href="{{ setRoute('user.transactions.index', 'airtime') }}">
                            {{ __('View More') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="dashboard-list-wrapper">
                @include('user.components.transaction-log', compact('transactions'))
            </div>
        </div> --}}

        {{-- TRANSACTIONS --}} <h4 class="mt-4">Your Airtime Transactions</h4>
        <div class="table-responsive">
            <table class="table-bordered mt-2 table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Network</th>
                        <th>Mobile</th>
                        <th>Amount (₦)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td>{{ $tx->request_id }}</td>
                            <td>{{ strtoupper($tx->network) }}</td>
                            <td>{{ $tx->mobile }}</td>
                            <td>₦{{ number_format($tx->amount, 2) }}</td>
                            <td>{{ ucfirst($tx->status) }}</td>
                            <td>{{ $tx->created_at->format('d M, Y h:i A') }}</td>
                    </tr> @empty <tr>
                            <td class="text-center" colspan="6">No airtime transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const FIXED_CHARGE = Number("{{ $fixedCharge }}") || 0;
        const PERCENTAGE_CHARGE = Number("{{ $percentageCharge }}") || 0;

        const submitBtn = document.querySelector("button[type='submit']");
        submitBtn.disabled = true;

        const defaultCountryCode = "+234";

        // Calculate payable
        function calculatePayable(amount) {
            if (!amount || isNaN(amount)) return 0;
            return amount + FIXED_CHARGE + (amount * (PERCENTAGE_CHARGE / 100));
        }

        // Popup (status feedback)
        // function showPopup(message, type = "success") {
        //     const box = document.createElement("div");
        //     box.className = `custom-alert ${type === "success" ? "alert-success" : "alert-danger"}`;
        //     box.innerHTML = `<span>${message}</span><button class="close-alert">&times;</button>`;
        //     document.body.prepend(box);

        //     box.querySelector(".close-alert").onclick = () => box.remove();
        //     setTimeout(() => box.classList.add("fade-out"), 2500);
        //     setTimeout(() => box.remove(), 3000);
        // }

        function showPopup(message, type = 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
            });
            Toast.fire({
                icon: type,
                title: message
            });
        }

        // Reset preview/operator state
        function resetOperator() {
            document.querySelector("#operator").value = "";
            document.querySelector("#operatorId").value = "";
            document.querySelector("#exchangeRate").value = "";
            submitBtn.disabled = true;

            const preview = document.querySelector(".preview-provider");
            if (preview) preview.textContent = "--";
        }

        // Auto-detect provider
        async function checkOperator() {
            const field = document.querySelector("input[name='mobile']");
            let phone = field.value.trim().replace(/\D/g, '');

            if (phone.length !== 11) return resetOperator();

            const url = '{{ route('user.mobile.topup.automatic.check.operator') }}';
            const token = '{{ csrf_token() }}';

            const data = {
                _token: token,
                mobile_code: defaultCountryCode,
                phone: phone,
                iso: "NG"
            };

            try {
                const req = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(data)
                });

                const res = await req.json();


                if (!req.ok || !res.status || !res.data || !res.data.name) {
                    resetOperator();
                    showPopup("Invalid or unsupported network number", "error");
                    return;
                }

                const providerName = (res.data.name || "UNKNOWN").toUpperCase();
                const providerId = res.data.id || "";
                const exchangeRate = res.data.rate || "";

                // Assign values
                document.querySelector("#operator").value = providerName;
                document.querySelector("#operatorId").value = providerId;
                document.querySelector("#exchangeRate").value = exchangeRate;

                // Update preview display
                const preview = document.querySelector(".preview-provider");
                if (preview) preview.textContent = providerName;

                submitBtn.disabled = false;
                showPopup(`Network detected: ${providerName}`, "success");

            } catch (error) {
                console.error("Lookup error:", error);
                resetOperator();
                showPopup("Unable to detect network, try again", "error");
            }
        }

        // Monitor mobile number field
        document.querySelector("input[name='mobile']")
            .addEventListener("input", () => {
                const phone = document.querySelector("input[name='mobile']").value;
                phone.length === 11 ? checkOperator() : resetOperator();
            });

        // Live preview for number & amount
        document.addEventListener("input", function() {
            const number = document.querySelector("input[name='mobile']").value || "--";
            const amount = parseFloat(document.querySelector("input[name='amount']").value);
            const payable = calculatePayable(amount);

            const prevMobile = document.querySelector(".preview-mobile");
            const prevAmount = document.querySelector(".preview-amount");

            if (prevMobile) prevMobile.textContent = number;
            if (prevAmount) prevAmount.textContent = payable ? `₦${payable.toFixed(2)}` : "--";
        });

        // Manual override if select exists
        const networkSelect = document.querySelector("#networkSelect");
        if (networkSelect) {
            networkSelect.addEventListener("change", function() {
                const selectedText = this.selectedOptions[0].textContent.trim();
                const code = this.selectedOptions[0].dataset.code || "";

                document.querySelector("#networkCode").value = code;

                const previewNet = document.querySelector(".preview-network");
                if (previewNet) previewNet.textContent = selectedText || "--";
            });
        }
    </script>
@endpush
