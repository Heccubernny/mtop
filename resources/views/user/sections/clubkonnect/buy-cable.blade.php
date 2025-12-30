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
    </style>
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [
            ['name' => __('Dashboard'), 'url' => setRoute('user.dashboard')],
            ['name' => __('List Services'), 'url' => setRoute('user.ck.home')],
        ],
        'active' => __('Cable Subscription'),
    ])
@endsection

@section('content')
    <div class="body-wrapper">

        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ __('Cable TV Subscription') }}</h3>
            </div>
        </div>

        {{-- SUCCESS / ERROR ALERTS --}}
        @if (session('success'))
            <div class="custom-alert alert-success">
                <span>{{ session('success') }}</span>
                <button class="close-alert">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="custom-alert alert-danger">
                <span>{{ session('error') }}</span>
                <button class="close-alert">&times;</button>
            </div>
        @endif

        <div class="row mb-30-none">

            {{-- LEFT SIDE FORM --}}
            <div class="col-xl-6 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">

                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Cable TV Subscription Form') }}</h5>
                        </div>

                        <div class="dash-payment-body">
                            <form class="card-form" id="cableForm" method="POST"
                                action="{{ route('user.cable.subscribe') }}">
                                @csrf

                                <div class="row">

                                    {{-- CABLE PROVIDER --}}
                                    <div class="col-xl-6 form-group">
                                        <label>{{ __('Cable Provider') }} <span class="text--base">*</span></label>
                                        <select class="form--control" id="cableSelect" name="cable" required>
                                            <option value="">-- Select Provider --</option>
                                            @foreach ($providers as $provider)
                                                <option data-code="{{ $provider->code }}" value="{{ $provider->code }}">
                                                    {{ strtoupper($provider->slug) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- PACKAGE --}}
                                    <div class="col-xl-6 form-group">
                                        <label>{{ __('Package') }} <span class="text--base">*</span></label>
                                        <select class="form--control" id="packageSelect" name="package" required disabled>
                                            <option value="">Select Provider First</option>
                                        </select>
                                    </div>

                                    {{-- SMARTCARD --}}
                                    <div class="col-xl-12 form-group">
                                        <label>{{ __('Smartcard / IUC Number') }} <span class="text--base">*</span></label>
                                        <div class="input-group">
                                            <input class="form--control" id="smartcardInput" name="smartcard" required>
                                            <button class="btn--base" id="verifyBtn" type="button">Verify</button>
                                        </div>
                                    </div>

                                    {{-- CUSTOMER NAME --}}
                                    <div class="col-xl-12 form-group" id="customerNameWrapper" style="display:none;">
                                        <label>{{ __('Customer Name') }}</label>
                                        <input class="form--control" id="customerName" name="customer_name" readonly>
                                    </div>

                                    {{-- PHONE --}}
                                    <div class="col-xl-12 form-group">
                                        <label>{{ __('Phone Number') }} <span class="text--base">*</span></label>
                                        <input class="form--control" name="phone" placeholder="08012345678" required>
                                    </div>

                                    {{-- SUBMIT --}}
                                    <div class="col-xl-12 mt-2">
                                        <button class="btn--base w-100" id="submitBtn" type="submit" disabled>
                                            {{ __('Subscribe') }}
                                            <i class="las la-tv ms-1"></i>
                                        </button>
                                    </div>

                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            {{-- RIGHT SIDE PREVIEW --}}
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
                                            <div class="preview-list-user-icon"><i class="las la-broadcast-tower"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Provider') }}</span>
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
                                            <div class="preview-list-user-icon"><i class="las la-list"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Package') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-package">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-id-card"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Smartcard / IUC') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-smartcard">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-phone"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Phone') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-phone">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-money-bill"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Plan Price') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-price">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-wallet"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Total Payable') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <strong class="preview-payable">--</strong>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </div>

        {{-- TRANSACTION TABLE --}}
        <h4 class="mt-4">{{ __('Cable Subscription History') }}</h4>
        <div class="table-responsive">
            <table class="table-bordered mt-2 table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Provider') }}</th>
                        <th>{{ __('Package') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Request ID') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr>
                            <td>{{ $tx->created_at->format('d M, Y h:i A') }}</td>
                            <td>{{ strtoupper($tx->network) }}</td>
                            <td>{{ $tx->plan }}</td>
                            <td>₦{{ number_format($tx->amount, 2) }}</td>
                            <td>{{ ucfirst($tx->status) }}</td>
                            <td>{{ $tx->request_id }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-muted text-center" colspan="6">
                                {{ __('No cable subscription history found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection

@push('script')
    <script>
        const FIXED_CHARGE = parseFloat("{{ $fixedCharge }}");
        const PERCENTAGE_CHARGE = parseFloat("{{ $percentageCharge }}");

        function calculatePayable(amount) {
            if (!amount || isNaN(amount)) return 0;
            return amount + FIXED_CHARGE + (amount * (PERCENTAGE_CHARGE / 100));
        }

        /* =========================
           PROVIDER CHANGE → LOAD PACKAGES
        ========================== */
        document.getElementById('cableSelect').addEventListener('change', function() {
            const provider = this.value;
            const packageSelect = document.getElementById('packageSelect');

            // Reset preview
            document.querySelector(".preview-package").textContent = "--";
            document.querySelector(".preview-price").textContent = "--";
            document.querySelector(".preview-payable").textContent = "--";

            if (!provider) return;

            packageSelect.innerHTML = '<option value="">Loading packages...</option>';
            packageSelect.disabled = true;

            fetch(`/user/cable/cable-plans/provider/${provider}`)
                .then(res => res.json())
                .then(packages => {
                    packageSelect.innerHTML = '<option value="">-- Select Package --</option>';
                    packageSelect.disabled = false;

                    packages.forEach(pkg => {
                        packageSelect.insertAdjacentHTML(
                            'beforeend',
                            `<option value="${pkg.package_code}"
                                data-price="${pkg.price}">
                            ${pkg.description}
                        </option>`
                        );
                    });
                })
                .catch(() => {
                    packageSelect.innerHTML = '<option>Error loading packages</option>';
                });
        });

        /* =========================
           PACKAGE CHANGE → UPDATE PREVIEW
        ========================== */
        document.getElementById('packageSelect').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];

            if (!selected || !selected.dataset.price) {
                document.querySelector(".preview-package").textContent = "--";
                document.querySelector(".preview-price").textContent = "--";
                document.querySelector(".preview-payable").textContent = "--";
                return;
            }

            const price = parseFloat(selected.dataset.price);
            const payable = calculatePayable(price);

            document.querySelector(".preview-package").textContent = selected.textContent;
            document.querySelector(".preview-price").textContent = "₦" + price.toFixed(2);
            document.querySelector(".preview-payable").textContent = "₦" + payable.toFixed(2);
        });

        /* =========================
           LIVE PREVIEW (OTHER FIELDS)
        ========================== */
        document.getElementById('cableSelect').addEventListener('change', function() {
            document.querySelector(".preview-provider").textContent =
                this.options[this.selectedIndex]?.text || "--";
        });

        document.getElementById('smartcardInput').addEventListener('input', function() {
            document.querySelector(".preview-smartcard").textContent = this.value || "--";
        });

        document.querySelector("input[name='phone']").addEventListener('input', function() {
            document.querySelector(".preview-phone").textContent = this.value || "--";
        });

        /* =========================
           VERIFY SMARTCARD
        ========================== */
        document.getElementById('verifyBtn').addEventListener('click', function() {
            const smartcard = document.getElementById('smartcardInput').value;
            const cableSelect = document.getElementById('cableSelect');
            const providerCode = cableSelect.selectedOptions[0]?.dataset.code;

            if (!smartcard) return alert("Enter Smartcard / IUC Number");
            if (!providerCode) return alert("Select Cable Provider");

            const btn = this;
            const submitBtn = document.getElementById('submitBtn');

            btn.innerText = "Verifying...";
            submitBtn.disabled = true;

            fetch(`/user/cable/verify?smartcard=${smartcard}&cable=${providerCode}`)
                .then(res => res.json())
                .then(data => {
                    btn.innerText = "Verify";

                    if (data.status === "00") {
                        document.getElementById('customerNameWrapper').style.display = 'block';
                        document.getElementById('customerName').value = data.customer_name;
                        submitBtn.disabled = false;
                    } else {
                        alert("Verification failed");
                    }
                })
                .catch(() => {
                    alert("Network error");
                    btn.innerText = "Verify";
                });
        });
    </script>
@endpush
