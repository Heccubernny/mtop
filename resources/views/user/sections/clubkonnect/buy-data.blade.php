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

        ``` .alert-success {
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
    ```
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [
            ['name' => __('Dashboard'), 'url' => setRoute('user.dashboard')],
            ['name' => __('List Services'), 'url' => setRoute('user.ck.home')],
        ],
        'active' => __('Buy Data'),
    ])
@endsection

@section('content')
    <div class="body-wrapper">

        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ __('Buy Data') }}</h3>
            </div>
        </div>

        {{-- SUCCESS / ERROR ALERTS --}}
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

        <div class="row mb-30-none">

            {{-- FORM --}}
            <div class="col-xl-6 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">

                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Data Purchase Form') }}</h5>
                        </div>

                        <div class="dash-payment-body">
                            <form class="card-form" method="POST" action="{{ route('user.data.buy') }}">
                                @csrf
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

                                    {{-- DATA PLAN --}}
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __('Data Plan') }}<span class="text--base">*</span></label>
                                        <select class="form--control" id="planSelect" name="plan_id" disabled required>
                                            <option value="">Select a network first</option>
                                        </select>
                                    </div>

                                    {{-- MOBILE NUMBER --}}
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __('Mobile Number') }} <span class="text--base">*</span></label>
                                        <input class="form--control" name="mobile" placeholder="08012345678" required>
                                    </div>

                                    <div class="col-xl-12 col-lg-12 mt-2">
                                        <button class="btn--base w-100" type="submit">
                                            {{ __('Buy Data') }} <i class="las la-database ms-1"></i>
                                        </button>
                                    </div>

                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            {{-- PREVIEW --}}
            <div class="col-xl-6 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">

                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Preview') }}</h5>
                        </div>

                        <div class="dash-payment-body">
                            <div class="preview-list-wrapper">
                                {{-- <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-network-wired"></i></div>
                                            <div class="preview-list-user-content">
                                                <span>Network Provider</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-provider">--</span>
                                    </div>
                                </div> --}}

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-sim-card"></i></div>
                                            <div class="preview-list-user-content"><span>{{ __('Network') }}</span></div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-network">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-database"></i></div>
                                            <div class="preview-list-user-content"><span>{{ __('Data Plan') }}</span></div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="preview-plan">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon"><i class="las la-phone"></i></div>
                                            <div class="preview-list-user-content"><span>{{ __('Mobile Number') }}</span>
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
                                            <div class="preview-list-user-content"><span>{{ __('Amount') }}</span></div>
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

        {{-- TRANSACTION HISTORY --}}
        <h4 class="mt-4">{{ __('Your Data Transactions') }}</h4>
        <div class="table-responsive">
            <table class="table-bordered mt-2 table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Network</th>
                        <th>Plan</th>
                        <th>Mobile</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Request ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $t)
                        <tr>
                            <td>{{ $t->created_at->format('d M, Y h:i A') }}</td>
                            <td>{{ strtoupper($t->network) }}</td>
                            <td>{{ $t->plan }}</td>
                            <td>{{ $t->mobile }}</td>
                            <td>₦{{ number_format($t->amount, 2) }}</td>
                            <td>{{ ucfirst($t->status) }}</td>
                            <td>{{ $t->request_id }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center" colspan="7">No transactions found.</td>
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
        const FIXED_CHARGE = parseFloat("{{ $fixedCharge }}");
        const PERCENTAGE_CHARGE = parseFloat("{{ $percentageCharge }}");
        const submitBtn = document.querySelector("button[type='submit']");
        submitBtn.disabled = true;
        const defaultCountryCode = "+234";
        const token = '{{ csrf_token() }}';

        function calculatePayable(amount) {
            if (!amount || isNaN(amount)) return 0;
            return amount + FIXED_CHARGE + (amount * (PERCENTAGE_CHARGE / 100));
        }

        function resetOperator() {
            document.getElementById('networkCode').value = "";
            submitBtn.disabled = true;
            document.querySelector(".preview-network").textContent = "--";
            const preview = document.querySelector(".preview-network");
            if (preview) preview.textContent = "--";
        }

        function showPopup(message, type = "success") {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500
            });
            Toast.fire({
                icon: type,
                title: message
            });
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
                const operatorRaw = res.data.name;



                if (!req.ok || !res.status || !res.data || !operatorRaw) {
                    resetOperator();
                    showPopup("Invalid or unsupported network number", "error");
                    return;
                }
                let operatorSlug = operatorRaw.toLowerCase().trim();

                operatorSlug = operatorSlug.replace(" ", "").replace("-", "").replace("nigeria", "").trim();

                const providerName = operatorRaw.toUpperCase();
                const providerId = res.data.id || "";
                const exchangeRate = res.data.rate || "";

                let matched = false;
                const networkSelect = document.getElementById("networkSelect");

                // Assign values
                document.querySelector("#operator").value = providerName;
                document.querySelector("#operatorId").value = providerId;
                document.querySelector("#exchangeRate").value = exchangeRate;

                // Update preview display
                const preview = document.querySelector(".preview-network");
                if (preview) preview.textContent = providerName;

                Array.from(networkSelect.options).forEach(option => {
                    const dbSlug = option.textContent.toLowerCase().trim();

                    if (dbSlug === operatorSlug) {
                        option.selected = true;
                        matched = true;

                        // update hidden field
                        document.getElementById("networkCode").value = option.dataset.code;

                        // update preview (if you have one)
                        // document.querySelector(".preview-provider").textContent = operatorRaw;
                        document.querySelector(".preview-network").textContent = operatorRaw;

                        break;
                    }
                });

                // no match case
                if (!matched) {
                    showPopup(`Network detected: ${operatorRaw}, but cannot auto-match. Select manually.`, "warning");
                }

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

        // Network → Update Plan Dropdown & Preview
        document.getElementById('networkSelect').addEventListener('change', function() {
            const networkId = this.value;
            const networkText = this.options[this.selectedIndex].text;
            document.querySelector(".preview-network").textContent = networkText || "--";
            document.getElementById('networkCode').value = this.selectedOptions[0]?.dataset.code || "";

            const planSelect = document.getElementById('planSelect');
            planSelect.innerHTML = `<option>Loading...</option>`;
            planSelect.disabled = true;

            if (!networkId) return;

            fetch(`/user/data/data-plans/network/${networkId}`)
                .then(res => res.json())
                .then(plans => {
                    planSelect.innerHTML = "";
                    planSelect.disabled = false;
                    if (plans.length === 0) {
                        planSelect.innerHTML = `<option>No plans available</option>`;
                        return;
                    }
                    plans.forEach(plan => {
                        planSelect.innerHTML +=
                            `<option value="${plan.id}" data-price="${plan.price}">${plan.description}</option>`;
                    });
                })
                .catch(() => planSelect.innerHTML = `<option>Error loading plans</option>`);

        });
        // Plan & Mobile → Update Preview
        document.addEventListener("input", function() {
            const planOption = document.getElementById('planSelect').selectedOptions[0];
            document.querySelector(".preview-plan").textContent = planOption ? planOption.textContent :
                "--";
            const mobile = document.querySelector("input[name='mobile']").value;
            document.querySelector(".preview-mobile").textContent = mobile || "--";
            if (planOption) {
                const baseAmount = parseFloat(planOption.dataset.price);
                const payable = calculatePayable(baseAmount);

                document.querySelector(".preview-amount").textContent =
                    "₦" + payable.toFixed(2);
            } else {
                document.querySelector(".preview-amount").textContent = "--";
            }
        });

        // Close alerts
        document.querySelectorAll('.close-alert').forEach(btn => {
            btn.addEventListener('click', () => btn.parentElement.classList.add('fade-out'));
        });
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alertBox');

            alerts.forEach(alert => {
                // Auto close after 5 seconds
                setTimeout(() => {
                    alert.remove();
                }, 5000);

                // Close on button click
                const closeBtn = alert.querySelector('.close-alert');
                closeBtn.addEventListener('click', () => {
                    alert.remove();
                });
            });
        });
    </script>
@endpush
