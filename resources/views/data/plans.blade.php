@extends('user.layouts.master')

@push('css')
    <style>
        #buyForm .form-control {
            height: 48px;
            line-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 18px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .limit-section {
            background: #f9fbfd;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #e0e6ed;
            margin-top: 15px;
        }

        .limit-section .limit-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
    </style>
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [
            ['name' => __('Dashboard'), 'url' => setRoute('user.dashboard')],
            ['name' => __('Data Services'), 'url' => setRoute('user.data.index')],
        ],
        'active' => __(strtoupper($service) . ' Subscription'),
    ])
@endsection

@section('content')
    <div class="body-wrapper">
        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ __(strtoupper($service)) . ' Subscription' }}</h3>
            </div>
        </div>

        <div class="row mb-30-none">
            <!-- Subscription Form -->
            <div class="col-xl-6 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Buy Data Plan') }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <form id="buyForm" method="POST" action="{{ route('user.data.buy', $service) }}">
                                @csrf
                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold">{{ __('Phone Number') }}</label>
                                    <input class="form-control" id="phone_number" name="phone_number" type="text"
                                        placeholder="{{ __('Enter phone number') }}" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label fw-semibold">{{ __('Select Plan') }}</label>
                                    <select class="form-select form--control" id="variation_code" name="variation_code"
                                        required>
                                        <option value="">{{ __('-- Select a Plan --') }}</option>
                                        @foreach ($plans as $plan)
                                            <option data-amount="{{ $plan->amount }}" data-name="{{ $plan->name }}"
                                                value="{{ $plan->variation_code }}">
                                                {{ $plan->name }}
                                                {{-- — ₦{{ number_format($plan->amount, 2) }} --}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('Amount') }}</label>
                                    <div class="input-group">
                                        <input class="form-control" id="amount" name="amount" type="text" readonly>
                                        <select class="form--control nice-select currency" name="currency" required>
                                            @foreach ($sender_wallets ?? [] as $data)
                                                <option data-rate="{{ $data->rate }}" data-type="{{ $data->type }}"
                                                    data-currency-id="{{ $data->id }}"
                                                    data-sender-country-name="{{ $data->name }}"
                                                    value="{{ $data->code }}">
                                                    {{ $data->code }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <button class="btn--base w-100 btn-loading" id="buyBtn" type="submit" disabled>
                                    {{ __('Buy Data') }} <i class="fas fa-signal ms-1"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Preview Section -->
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
                                    <i class="las la-signal"></i> {{ __('Service') }}
                                    <span class="float-end">{{ strtoupper($service) }}</span>
                                </div>
                                <div class="preview-list-item">
                                    <i class="las la-phone"></i> {{ __('Phone Number') }}
                                    <span class="float-end" id="previewPhone">--</span>
                                </div>
                                <div class="preview-list-item">
                                    <i class="las la-list-ol"></i> {{ __('Plan') }}
                                    <span class="float-end" id="previewPlan">--</span>
                                </div>
                                <div class="preview-list-item">
                                    <i class="las la-coins"></i> {{ __('Amount') }}
                                    <span class="float-end" id="previewAmount">--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-list-area mt-20">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __('Data Purchase Log') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn mb-2">
                        <a class="btn--base"
                            href="{{ setRoute('user.transactions.index', 'data') }}">{{ __('View More') }}</a>
                    </div>
                </div>
            </div>
            <div class="dashboard-list-wrapper">
                @include('user.components.transaction-log', compact('transactions'))
            </div>
        </div>

        <!-- Transaction Logs -->
        {{-- <div class="card custom--card mt-4">
            <div class="card-header bg--base d-flex justify-content-between align-items-center text-white">
                <h5 class="mb-0">{{ __('Data Purchase Logs') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive table-striped">
                    <table class="mb-0 table">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Plan') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $log)
                                <tr>
                                    <td>{{ showDateTime($log->created_at) }}</td>
                                    <td>{{ $log->phone }}</td>
                                    <td>{{ $log->plan_name }}</td>
                                    <td>{{ showAmount($log->amount) }}</td>
                                    <td>
                                        <span class="badge {{ $log->status == 'success' ? 'bg-success' : 'bg-danger' }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="5">{{ __('No data purchase found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div> --}}
    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            const buyUrl = "{{ route('user.data.buy', $service) }}";

            $('#phone_number, #variation_code').on('input change', function() {
                const phone = $('#phone_number').val().trim();
                const plan = $('#variation_code').val();
                $('#buyBtn').prop('disabled', !(phone && plan));
            });

            $('#phone_number').on('input', function() {
                $('#previewPhone').text($(this).val() || '--');
            });

            $('#variation_code').on('change', function() {
                const selected = $(this).find('option:selected');
                const name = selected.data('name');
                const amount = selected.data('amount');
                $('#amount').val(amount);
                $('#previewPlan').text(name);
                $('#previewAmount').text('₦' + amount);
            });

            // Submit form
            // $('#buyForm').on('submit', function(e) {
            //     e.preventDefault();
            //     const formData = $(this).serialize();

            //     Swal.fire({
            //         title: "{{ __('Confirm Purchase') }}",
            //         text: "{{ __('Proceed to buy this data plan?') }}",
            //         icon: "question",
            //         showCancelButton: true,
            //         confirmButtonText: "{{ __('Yes, Buy Now') }}"
            //     }).then(result => {
            //         console.log("result bro: ", result);
            //         if (result.isConfirmed) {
            //             $.post(buyUrl, formData)
            //                 .done(res => {
            //                     Swal.fire("{{ __('Success!') }}", res.message || "{{ __('Data purchased successfully!') }}", "success")
            //                         .then(() => location.reload());
            //                 })
            //                 .fail(() => Swal.fire("{{ __('Error!') }}", "{{ __('Purchase failed.') }}", "error"));
            //         }
            //     });
            // });
        });
    </script>
@endpush
