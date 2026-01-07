@extends('user.layouts.master')

@push('css')
    <style>
        .select2-results__option .country-flag {
            width: 22px;
            height: 14px;
            object-fit: cover;
            margin-right: 8px;
            vertical-align: middle;
        }

        .select2-selection__rendered .country-flag {
            width: 18px;
            height: 12px;
            object-fit: cover;
            margin-right: 6px;
            vertical-align: middle;
        }

        .country-option-text {
            display: inline-block;
            vertical-align: middle;
        }
    </style>
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('user.dashboard'),
            ],
        ],
        'active' => __(@$page_title),
    ])
@endsection

@section('content')
    <div class="body-wrapper">
        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ __(@$page_title) }}</h3>
            </div>
        </div>
        {{-- <form method="GET" action="{{ route('user.bill.pay.index') }}">
            <div class="row mb-3">

                <div class="col-md-4">
                    <label>Service Type</label>
                    <select class="form-control" name="type" required>
                        <option value="">Select Service</option>
                        <option value="ELECTRICITY">Electricity</option>
                        <option value="INTERNET">Internet</option>
                        <option value="WATER">Water</option>
                        <option value="TV">TV Subscription</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Country</label>
                    <select class="form-control" id="countrySelect" name="country" style="width:100%">
                        <option value="">{{ __('Loading countries...') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Biller Type</label>
                    <select class="form-control" name="serviceType">
                        <option value="">Any</option>
                        <option value="PREPAID">Prepaid</option>
                        <option value="POSTPAID">Postpaid</option>
                        <option value="DATA">Data</option>
                    </select>
                </div>

            </div>

            <button class="btn btn-primary">Get Billers</button>
        </form> --}}

        <div class="dash-payment-item active">
            <div class="dash-payment-title-area">
                <span class="dash-payment-badge">!</span>
                <h5 class="title">{{ __('Billers Search') }}</h5>
            </div>

            <div class="dash-payment-body">
                <form class="card-form" id="billersForm" method="GET" action="{{ route('user.bill.pay.index') }}">
                    <div class="row">

                        <!-- Service Type -->
                        <div class="col-xxl-4 col-xl-4 col-lg-4 form-group">
                            <label>{{ __('Service Type') }} <span class="text--base">*</span></label>
                            <select class="form--control" name="type" required>
                                <option value="">{{ __('Select Service') }}</option>
                                <option value="ELECTRICITY">{{ __('Electricity') }}</option>
                                <option value="INTERNET">{{ __('Internet') }}</option>
                                <option value="WATER">{{ __('Water') }}</option>
                                <option value="TV">{{ __('TV Subscription') }}</option>
                            </select>
                        </div>

                        <!-- Country -->
                        <div class="col-xxl-4 col-xl-4 col-lg-4 form-group">
                            <label>{{ __('Country') }} <span class="text--base">*</span></label>
                            <select class="form--control nice-select" id="countrySelect" name="country" style="width:100%">
                                {{-- <option value="">{{ __('Loading countries...') }}</option> --}}
                            </select>
                        </div>

                        <!-- Biller Type -->
                        <div class="col-xxl-4 col-xl-4 col-lg-4 form-group">
                            <label>{{ __('Biller Type') }}</label>
                            <select class="form--control" name="serviceType">
                                <option value="">{{ __('Any') }}</option>
                                <option value="PREPAID">{{ __('Prepaid') }}</option>
                                <option value="POSTPAID">{{ __('Postpaid') }}</option>
                                <option value="DATA">{{ __('Data') }}</option>
                            </select>
                        </div>

                        <div class="col-xl-12 col-lg-12 mt-2">
                            <button class="btn--base w-100 btn-loading" type="submit">
                                {{ __('Get Billers') }} <i class="las la-search ms-1"></i>
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <!-- Container to display billers dynamically -->
        {{-- <div class="mt-4" id="billersContainer"></div> --}}

        {{-- @if (isset($billType) && count($billType))
            <div class="row">
                @foreach ($billType as $biller)
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary biller-select" data-id="{{ $biller['id'] }}"
                            data-name="{{ $biller['name'] }}">
                            {{ $biller['name'] }}
                        </button>
                    </div>
                @endforeach
            </div>
        @endif --}}

        <div class="row mb-30-none">
            <div class="col-xl-6 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Bill Pay Form') }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <form class="card-form" action="{{ setRoute('user.bill.pay.confirm') }}" method="POST">
                                @csrf
                                <input name="exchange_rate" type="hidden">
                                <input name="biller_item_type" type="hidden">
                                {{-- {{ logger()->info('Biller item type ' . json_encode($billType)) }} --}}
                                <div class="row">
                                    <div class="col-xl-12 col-lg-12 form-group text-center">
                                        <div class="exchange-area">
                                            <code class="d-block text-center">
                                                <span>{{ __('Exchange Rate') }} <span class="rate-show">--</span></span>
                                            </code>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __('bill Type') }} <span class="text--base">*</span></label>
                                        <select class="form--control" name="bill_type">
                                            @forelse ($billType ??[] as $type)
                                                @php
                                                    $type = (object) $type;
                                                @endphp
                                                <option data-item-type ="{{ $type->item_type }}"
                                                    data-item="{{ json_encode($type) }}" data-name="{{ $type->name }}"
                                                    value="{{ $type->id }}">{{ $type->name }}
                                                    {{ $type->item_type === 'MANUAL' ? '(Manual)' : '' }}</option>
                                            @empty
                                                <option value="null" disabled selected>{{ __('No Items Available') }}
                                                </option>
                                            @endforelse

                                        </select>
                                    </div>
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __('Bill Month') }} <span class="text--base">*</span></label>
                                        <select class="form--control" name="bill_month">
                                            <option value="{{ 'January' . '-' . date('Y') }}">{{ 'January ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'February' . '-' . date('Y') }}">
                                                {{ 'February ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'March' . '-' . date('Y') }}">{{ 'March ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'April' . '-' . date('Y') }}">{{ 'April ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'May' . '-' . date('Y') }}">{{ 'May ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'June' . '-' . date('Y') }}">{{ 'June ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'July' . '-' . date('Y') }}">{{ 'July ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'August' . '-' . date('Y') }}">{{ 'August ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'September' . '-' . date('Y') }}">
                                                {{ 'September ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'October' . '-' . date('Y') }}">{{ 'October ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'November' . '-' . date('Y') }}">
                                                {{ 'November ' . date('Y') }}
                                            </option>
                                            <option value="{{ 'December' . '-' . date('Y') }}">
                                                {{ 'December ' . date('Y') }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __('Bill Number') }} <span class="text--base">*</span></label>
                                        <input class="form--control number-input btn-loading" name="bill_number"
                                            type="text" value="{{ old('bill_number') }}" required
                                            placeholder="{{ __('enter Bill Number') }}">

                                    </div>

                                    <div class="col-xxl-6 col-xl-6 col-lg-6 form-group">
                                        <label>{{ __('Amount') }}<span>*</span></label>
                                        <div class="input-group">
                                            <input class="form--control number-input" name="amount" type="text"
                                                value="{{ old('amount') }}" placeholder="{{ __('enter Amount') }}">
                                            <select class="form--control nice-select currency" name="currency">
                                                @foreach ($sender_wallets ?? [] as $data)
                                                    <option data-rate="{{ $data->rate }}"
                                                        data-type="{{ $data->type }}"
                                                        data-currency-id="{{ $data->id }}"
                                                        data-sender-country-name="{{ $data->name }}"
                                                        value="{{ $data->code }}">{{ $data->code }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                    </div>
                                    <div class="col-xl-12 col-lg-12 form-group">
                                        <div class="note-area">
                                            <code class="d-block fw-bold balance-show">-- </code>
                                        </div>
                                    </div>

                                    <div class="col-xl-12 col-lg-12">
                                        <button class="btn--base w-100 btn-loading billPayBtn"
                                            type="submit">{{ __('pay Bill') }} <i
                                                class="fas fa-coins ms-1"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
                                            <div class="preview-list-user-icon">
                                                <i class="las la-plug"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Bill Pay') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="bill-type">--</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-list-ol"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Bill Month') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="bill-month">--</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-list-ol"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Bill Number') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="bill-number">--</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-hand-holding-usd"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Amount') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="request-amount">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-hand-holding-usd"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Conversion Amount') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="text--info conversion-amount">--</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-hand-holding-usd"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Total Charge') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="fees-show">--</span>
                                    </div>
                                </div>

                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-hand-holding-usd"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Total Payable') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="text--base last payable-total">--</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                {{-- limit section  --}}
                <div class="dash-payment-item-wrapper limit">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Limit Information') }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <div class="preview-list-wrapper">
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-wallet"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Transaction Limit') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="limit-show">--</span>
                                    </div>
                                </div>
                                @if ($billPayCharge->daily_limit > 0)
                                    <div class="preview-list-item">
                                        <div class="preview-list-left">
                                            <div class="preview-list-user-wrapper">
                                                <div class="preview-list-user-icon">
                                                    <i class="las la-wallet"></i>
                                                </div>
                                                <div class="preview-list-user-content">
                                                    <span>{{ __('Daily Limit') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="preview-list-right">
                                            <span class="limit-daily">--</span>
                                        </div>
                                    </div>
                                    <div class="preview-list-item">
                                        <div class="preview-list-left">
                                            <div class="preview-list-user-wrapper">
                                                <div class="preview-list-user-icon">
                                                    <i class="las la-wallet"></i>
                                                </div>
                                                <div class="preview-list-user-content">
                                                    <span>{{ __('Remaining Daily Limit') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="preview-list-right">
                                            <span class="daily-remaining">--</span>
                                        </div>
                                    </div>
                                @endif
                                @if ($billPayCharge->monthly_limit > 0)
                                    <div class="preview-list-item">
                                        <div class="preview-list-left">
                                            <div class="preview-list-user-wrapper">
                                                <div class="preview-list-user-icon">
                                                    <i class="las la-wallet"></i>
                                                </div>
                                                <div class="preview-list-user-content">
                                                    <span>{{ __('Monthly Limit') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="preview-list-right">
                                            <span class="limit-monthly">--</span>
                                        </div>
                                    </div>
                                    <div class="preview-list-item">
                                        <div class="preview-list-left">
                                            <div class="preview-list-user-wrapper">
                                                <div class="preview-list-user-icon">
                                                    <i class="las la-wallet"></i>
                                                </div>
                                                <div class="preview-list-user-content">
                                                    <span>{{ __('Remaining Monthly Limit') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="preview-list-right">
                                            <span class="monthly-remaining">--</span>
                                        </div>
                                    </div>
                                @endif

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-list-area mt-20">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __('Bill Pay Log') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn mb-2">
                        <a class="btn--base"
                            href="{{ setRoute('user.transactions.index', 'bill-pay') }}">{{ __('View More') }}</a>
                    </div>
                </div>
            </div>
            <div class="dashboard-list-wrapper">
                @include('user.components.transaction-log', compact('transactions'))
            </div>
        </div>
    </div>
@endsection

@push('script')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        var defualCurrency = "{{ get_default_currency_code() }}";
        var defualCurrencyRate = "{{ get_default_currency_rate() }}";

        $(document).ready(function() {
            senderBalance();
            getLimit();
            getDailyMonthlyLimit();
            get_remaining_limits();
            getExchangeRate();
            getFees();
            activeItems();

        });
        $("input[name=amount]").keyup(function() {
            getFees();
            getLimit();
            getDailyMonthlyLimit();
            get_remaining_limits();
            activeItems();
        });
        $("input[name=amount]").focusout(function() {
            var limit_validate = enterLimit();
            if (limit_validate.type == 'min') {
                throwMessage('error', [limit_validate.message]);
                $('.billPayBtn').attr('disabled', true)
            } else if (limit_validate.type == 'max') {
                throwMessage('error', [limit_validate.message]);
                $('.billPayBtn').attr('disabled', true)
            }
        });
        $("input[name=bill_number]").keyup(function() {
            getFees();
            activeItems();
        });
        $("select[name=bill_type]").change(function() {
            getFees();
            getLimit();
            getDailyMonthlyLimit();
            get_remaining_limits();
            getExchangeRate();
            activeItems();
        });
        $("select[name=bill_month]").change(function() {
            getFees();
            getExchangeRate();
            activeItems();
        });

        $("select[name=currency]").change(function() {
            senderBalance();
            getFees();
            getExchangeRate();
            getLimit();
            getDailyMonthlyLimit();
            get_remaining_limits();

        });

        // important aspect of the code
        function acceptVar() {
            var senderCurrSelectedVal = $("select[name=currency] :selected");
            var senderCurrencyCode = $("select[name=currency] :selected").val();
            var senderCurrencyRate = $("select[name=currency] :selected").data('rate');
            var senderCurrencyType = $("select[name=currency] :selected").data('type');

            var defaultCurrencyCode = defualCurrency;
            var defaultCurrencyRate = defualCurrencyRate;

            var currencyMinAmount = "{{ getAmount($billPayCharge->min_limit) }}";
            var currencyMaxAmount = "{{ getAmount($billPayCharge->max_limit) }}";

            var currencyFixedCharge = "{{ getAmount($billPayCharge->fixed_charge) }}";
            var currencyPercentCharge = "{{ getAmount($billPayCharge->percent_charge) }}";

            var currencyDailyLimit = "{{ getAmount($billPayCharge->daily_limit) }}";
            var currencyMonthlyLimit = "{{ getAmount($billPayCharge->monthly_limit) }}";

            var billType = $("select[name=bill_type] :selected");
            var billName = $("select[name=bill_type] :selected").data("name");
            var billMonth = $("select[name=bill_month] :selected").val();
            var billNumber = $("input[name=bill_number]").val();

            if (senderCurrencyType == "CRYPTO") {
                var senderPrecison = "{{ get_precision_from_admin()['crypto_precision_value'] }}";
            } else {
                var senderPrecison = "{{ get_precision_from_admin()['fiat_precision_value'] }}";
            }


            return {
                sCurrSelectedVal: senderCurrSelectedVal,
                SCurrencyCode: senderCurrencyCode,
                SCurrencyRate: senderCurrencyRate,
                sPrecison: senderPrecison,

                defaultCurrencyCode: defaultCurrencyCode,
                defaultCurrencyRate: defaultCurrencyRate,

                currencyMinAmount: currencyMinAmount,
                currencyMaxAmount: currencyMaxAmount,

                currencyFixedCharge: currencyFixedCharge,
                currencyPercentCharge: currencyPercentCharge,

                currencyDailyLimit: currencyDailyLimit,
                currencyMonthlyLimit: currencyMonthlyLimit,

                billName: billName,
                billNumber: billNumber,
                billMonth: billMonth,
                billType: billType,


            };
        }

        function getLimit() {
            var exchangeRate = parseFloat($("input[name=exchange_rate]").val());
            var walletCurrencyCode = acceptVar().SCurrencyCode;
            var sender_currency = acceptVar().SCurrencyCode;
            var sender_currency_rate = acceptVar().SCurrencyRate;
            if (acceptVar().billType.val() === "null") {
                return false;
            }
            if (acceptVar().billType.data('item-type') === "AUTOMATIC") {
                var item = acceptVar().billType.data('item');
                console.log(item, exchangeRate)
                var min_limit = parseFloat(item.minLocalTransactionAmount) / parseFloat(exchangeRate);
                var max_limit = parseFloat(item.maxLocalTransactionAmount) / parseFloat(exchangeRate);
                // var min_limit = acceptVar().currencyMinAmount;
                // var max_limit = acceptVar().currencyMaxAmount;

                // min_limit_calc = parseFloat(min_limit * sender_currency_rate).toFixed(accept().sPrecison);
                // max_limit_calc = parseFloat(max_limit * sender_currency_rate).toFixed(accept().sPrecison);
                // $('.limit-show').html(min_limit_calc + " " + sender_currency + " d-d " + max_limit_calc + " " + sender_currency);
                $('.limit-show').html(min_limit.toFixed(acceptVar().sPrecison) + " " + walletCurrencyCode + " - " +
                    max_limit.toFixed(acceptVar().sPrecison) + " " + walletCurrencyCode);

            } else {
                var currencyCode = acceptVar().SCurrencyCode;
                var currencyRate = acceptVar().SCurrencyRate;

                var min_limit = acceptVar().currencyMinAmount;
                var max_limit = acceptVar().currencyMaxAmount;
                if ($.isNumeric(min_limit) || $.isNumeric(max_limit)) {
                    var min_limit_calc = parseFloat(min_limit / exchangeRate);
                    var max_limit_calc = parseFloat(max_limit / exchangeRate);
                    $('.limit-show').html(min_limit_calc.toFixed(acceptVar().sPrecison) + " " + currencyCode + " - " +
                        max_limit_calc.toFixed(acceptVar().sPrecison) + " " + currencyCode);
                    // var min_limit = acceptVar().currencyMinAmount;
                    // var max_limit = acceptVar().currencyMaxAmount;

                    // var min_limit_calc = parseFloat(min_limit * sender_currency_rate).toFixed(acceptVar().sPrecison);
                    // var max_limit_calc = parseFloat(max_limit * sender_currency_rate).toFixed(acceptVar().sPrecison);
                    // $('.limit-show').html(min_limit_calc + " " + sender_currency + " f-f " + max_limit_calc + " " +
                    //     sender_currency);

                    return {
                        minLimit: min_limit_calc,
                        maxLimit: max_limit_calc,
                    };
                } else {
                    $('.limit-show').html("--");
                    return {
                        minLimit: 0,
                        maxLimit: 0,
                    };
                }

            }
        }

        function getDailyMonthlyLimit() {
            var sender_currency = acceptVar().SCurrencyCode;
            var sender_currency_rate = acceptVar().SCurrencyRate;
            var daily_limit = acceptVar().currencyDailyLimit;
            var monthly_limit = acceptVar().currencyMonthlyLimit

            if ($.isNumeric(daily_limit) && $.isNumeric(monthly_limit)) {
                if (daily_limit > 0) {
                    var daily_limit_calc = parseFloat(daily_limit * sender_currency_rate).toFixed(acceptVar().sPrecison);
                    $('.limit-daily').html(daily_limit_calc + " " + sender_currency);
                } else {
                    $('.limit-daily').html("");
                }

                if (monthly_limit > 0) {
                    var montly_limit_calc = parseFloat(monthly_limit * sender_currency_rate).toFixed(acceptVar().sPrecison);
                    $('.limit-monthly').html(montly_limit_calc + " " + sender_currency);

                } else {
                    $('.limit-monthly').html("");
                }

            } else {
                $('.limit-daily').html("--");
                $('.limit-monthly').html("--");
                return {
                    dailyLimit: 0,
                    monthlyLimit: 0,
                };
            }

        }

        function getExchangeRate() {
            var walletCurrencyCode = acceptVar().SCurrencyCode;
            var walletCurrencyRate = acceptVar().SCurrencyRate;
            var sender_amount = $("input[name=amount]").val();

            sender_amount == "" ? (sender_amount = 0) : (sender_amount = sender_amount);

            if (acceptVar().billType.data('item-type') === "AUTOMATIC") {
                var item = acceptVar().billType.data('item');
                var receiver_currency_code = item.localTransactionCurrencyCode;
            } else {
                var receiver_currency_code = acceptVar().defaultCurrencyCode;
            }
            $.ajax({
                type: 'get',
                url: "{{ route('global.receiver.wallet.currency') }}",
                data: {
                    code: receiver_currency_code
                },
                success: function(data) {
                    var receiverCurrencyCode = data.code;
                    var receiverCurrencyRate = data.rate;
                    var exchangeRate = (receiverCurrencyRate / walletCurrencyRate);

                    $("input[name=exchange_rate]").val(exchangeRate);
                    $('.rate-show').html("1 " + walletCurrencyCode + " = " + parseFloat(exchangeRate).toFixed(
                        acceptVar().sPrecison) + " " + receiverCurrencyCode);
                    feesCalculation();
                    getFees();
                    getLimit();
                    getDailyMonthlyLimit();
                    get_remaining_limits();
                    enterLimit();
                    activeItems();
                }
            });
        }

        function feesCalculation() {
            var exchangeRate = parseFloat($("input[name=exchange_rate]").val());
            var currencyCode = acceptVar().SCurrencyCode;
            var currencyRate = acceptVar().SCurrencyRate;
            var sender_amount = $("input[name=amount]").val();
            sender_amount == "" ? (sender_amount = 0) : (sender_amount = sender_amount);

            var fixed_charge = acceptVar().currencyFixedCharge;
            var percent_charge = acceptVar().currencyPercentCharge;
            if ($.isNumeric(percent_charge) && $.isNumeric(fixed_charge) && $.isNumeric(sender_amount)) {
                // Process Calculation
                var fixed_charge_calc = parseFloat(fixed_charge * currencyRate);
                var percent_charge_calc = (parseFloat(sender_amount) / 100) * parseFloat(percent_charge);
                var total_charge = parseFloat(fixed_charge_calc) + parseFloat(percent_charge_calc);
                total_charge = parseFloat(total_charge);
                // return total_charge;
                return {
                    total: parseFloat(total_charge),
                    fixed: parseFloat(fixed_charge_calc),
                    percent: parseFloat(percent_charge),
                };
            } else {
                return false;
            }
        }

        function getFees() {
            var currencyCode = acceptVar().SCurrencyCode;
            var exchangeRate = parseFloat($("input[name=exchange_rate]").val());
            if (acceptVar().billType.val() === "null") {
                return false;
            }
            var percent = acceptVar().currencyPercentCharge;
            var charges = feesCalculation();
            if (charges == false) {
                return false;
            }
            $(".fees-show").html(parseFloat(charges.fixed).toFixed(acceptVar().sPrecison) + " " + currencyCode + " + " +
                parseFloat(charges.percent).toFixed(acceptVar().sPrecison) + "% = " + parseFloat(charges.total).toFixed(
                    acceptVar().sPrecison) + " " + currencyCode);


        }

        function activeItems() {
            var billType = acceptVar().billType.val();
            if (billType === undefined || billType === '' || billType === null) {
                return false;
            } else {
                return getPreview();
            }
        }

        function getPreview() {
            if (acceptVar().billType.val() === "null") {
                return false;
            }
            var senderAmount = $("input[name=amount]").val();
            var wallet_currency = acceptVar().SCurrencyCode;
            var billName = acceptVar().billName;
            var billNumber = acceptVar().billNumber;
            var exchangeRate = parseFloat($("input[name=exchange_rate]").val());
            senderAmount == "" ? senderAmount = 0 : senderAmount = senderAmount;
            var conversion_amount = parseFloat(senderAmount).toFixed(acceptVar().sPrecison) * parseFloat(exchangeRate)
                .toFixed(acceptVar().sPrecison);
            //fillup hidden fields
            $("input[name=biller_item_type]").val(acceptVar().billType.data('item-type'));
            //fillup hidden fields

            if (acceptVar().billType.data('item-type') === "AUTOMATIC") {
                var item = acceptVar().billType.data('item');
                var rCurrencyCode = item.localTransactionCurrencyCode;
            } else {
                var rCurrencyCode = acceptVar().defaultCurrencyCode;
            }
            var charges = feesCalculation();
            var final_charge = charges.total;

            // Sending Amount
            $('.request-amount').text(parseFloat(senderAmount).toFixed(acceptVar().sPrecison) + " " + wallet_currency);
            $('.conversion-amount').text(parseFloat(conversion_amount).toFixed(acceptVar().sPrecison) + " " +
                rCurrencyCode);
            //bill type
            $('.bill-type').text(billName);
            $('.bill-month').text(acceptVar().billMonth);
            // Fees
            //bill number
            if (billNumber == '' || billNumber == 0) {
                $('.bill-number').text("Ex: 1234567891");
            } else {
                $('.bill-number').text(billNumber);
            }
            // Fees
            var total_charge = 0;
            if (senderAmount == 0) {
                total_charge = 0;
            } else {
                total_charge = final_charge;
            }

            $('.fees').text(parseFloat(total_charge).toFixed(acceptVar().sPrecison) + " " + wallet_currency);
            // Pay In Total
            var totalPay = parseFloat(senderAmount) + parseFloat(total_charge)
            var pay_in_total = 0;
            if (senderAmount == 0) {
                pay_in_total = 0;
            } else {
                pay_in_total = parseFloat(totalPay);
            }
            $('.payable-total').text(parseFloat(pay_in_total).toFixed(acceptVar().sPrecison) + " " + wallet_currency);

        }

        function enterLimit() {
            var exchangeRate = parseFloat($("input[name=exchange_rate]").val());

            if (acceptVar().billType.data('item-type') === "AUTOMATIC") {
                var item = acceptVar().billType.data('item');
                var min_limit = (parseFloat(item.minLocalTransactionAmount) / parseFloat(exchangeRate));
                var max_limit = (parseFloat(item.maxLocalTransactionAmount) / parseFloat(exchangeRate));

            } else {
                var min_limit = parseFloat(acceptVar().currencyMinAmount / exchangeRate);
                var max_limit = parseFloat(acceptVar().currencyMaxAmount / exchangeRate);
            }


            var sender_amount = parseFloat($("input[name=amount]").val());
            console.log([sender_amount, min_limit, max_limit])
            // Corrected code:
            if (sender_amount === "NaN" || sender_amount === "") {
                sender_amount = 0;
            }
            if (sender_amount < min_limit) {
                return {
                    message: '{{ __('Please follow the mimimum limit') }}',
                    type: 'min'
                }
            } else if (sender_amount > max_limit) {
                return {
                    message: '{{ __('Please follow the maximum limit') }}',
                    type: 'max'
                }
            } else {
                $('.billPayBtn').attr('disabled', false);
                return {
                    message: 'success',
                    type: 'success'
                }
            }

        }

        function senderBalance() {
            var senderCurrency = acceptVar().SCurrencyCode;
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                type: 'POST',
                url: "{{ route('user.wallets.balance') }}",
                data: {
                    target: senderCurrency,
                    _token: csrfToken
                },
                success: function(response) {
                    $('.balance-show').html("{{ __('Available Balance') }}: " + parseFloat(response.data)
                        .toFixed(acceptVar().sPrecison) + " " + senderCurrency);
                }
            });
        }

        function get_remaining_limits() {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            var user_field = "user_id";
            var user_id = "{{ userGuard()['user']->id }}";
            var transaction_type = "{{ payment_gateway_const()::BILLPAY }}";
            var currency_id = acceptVar().sCurrSelectedVal.data('currency-id');
            var sender_amount = $("input[name=amount]").val();

            (sender_amount == "" || isNaN(sender_amount)) ? sender_amount = 0: sender_amount = sender_amount;

            var charge_id = "{{ $billPayCharge->id }}";
            var attribute = "{{ payment_gateway_const()::SEND }}"

            $.ajax({
                type: 'POST',
                url: "{{ route('global.get.total.transactions') }}",
                data: {
                    _token: csrfToken,
                    user_field: user_field,
                    user_id: user_id,
                    transaction_type: transaction_type,
                    currency_id: currency_id,
                    sender_amount: sender_amount,
                    charge_id: charge_id,
                    attribute: attribute,
                },
                success: function(response) {
                    var sender_currency = acceptVar().SCurrencyCode;

                    var status = response.status;
                    var message = response.message;
                    var amount_data = response.data;

                    if (status == false) {
                        $('.daily-remaining').html(amount_data.remainingDailyTxnSelected + " " +
                            sender_currency);
                        $('.monthly-remaining').html(amount_data.remainingMonthlyTxnSelected + " " +
                            sender_currency);
                        throwMessage('error', [message]);
                        return false;
                    } else {
                        $('.daily-remaining').html(amount_data.remainingDailyTxnSelected + " " +
                            sender_currency);
                        $('.monthly-remaining').html(amount_data.remainingMonthlyTxnSelected + " " +
                            sender_currency);
                    }
                },
            });
        }
    </script>

    {{-- <script>
        $(document).ready(function() {

            $('#billersForm').on('submit', function(e) {
                e.preventDefault(); // prevent normal form submit
                var $form = $(this);
                var url = $form.attr('action');
                var data = $form.serialize();

                console.log("Mydata ", data);

                $.ajax({
                    url: url,
                    method: 'GET',
                    data: data,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    beforeSend: function() {
                        $('#billersContainer').html(
                            '<div class="text-center py-4">Loading billers...</div>');
                    },
                    success: function(response) {
                        // Inject the billers HTML returned by controller
                        // Controller should detect AJAX and return partial view if necessary
                        console.log(response);
                        $('#billersContainer').html(
                            '<div class="alert alert-success">Fetched successfully.</div>');
                    },
                    error: function(xhr) {
                        console.log(xhr);
                        $('#billersContainer').html(
                            '<div class="alert alert-danger">Failed to load billers. Try again.</div>'
                        );
                    }
                });
            });

        });
    </script> --}}

    <script>
        $(function() {
            const $country = $('#countrySelect');

            // Initialize Select2 first with a placeholder
            $country.select2({
                placeholder: 'Select Country',
                templateResult: formatCountry, // show flag + name + currency in dropdown
                templateSelection: formatSelection, // show flag + name in selection
                escapeMarkup: function(m) {
                    return m;
                } // allow HTML
            });

            // Fetch minimized fields to reduce payload
            $.ajax({
                url: "https://restcountries.com/v3.1/all?fields=name,cca2,flags,currencies",
                method: "GET",
                success: function(data) {
                    // sort alphabetically
                    data.sort((a, b) => a.name.common.localeCompare(b.name.common));
                    $country.empty(); // ✅ remove "Loading countries..."

                    $country.append('<option></option>'); // ✅ real placeholder

                    const items = data
                        .filter(c => c.cca2) // ensure iso exists
                        .map(country => {
                            // currency symbol extraction (take first currency entry)
                            let currencySymbol = '';
                            let currencyCode = '';
                            if (country.currencies) {
                                const first = Object.entries(country.currencies)[0];
                                if (first) {
                                    currencyCode = first[0];
                                    currencySymbol = first[1]?.symbol ?? '';
                                }
                            }
                            const flagImg = country.flags?.png || country.flags?.svg || '';

                            return {
                                id: country.cca2,
                                text: country.name.common,
                                flag: flagImg,
                                currencySymbol: currencySymbol,
                                currencyCode: currencyCode
                            };
                        });

                    // load data into select2
                    // $country.empty().append('<option></option>'); // placeholder option
                    // $country.empty(); // remove "Loading countries..."
                    // $country.append(new Option('', '', false, false)); // real placeholder
                    items.forEach(i => {
                        const option = new Option(i.text, i.id, false, false);
                        // attach extra data for Select2 to use
                        $(option).data('flag', i.flag);
                        $(option).data('currencySymbol', i.currencySymbol);
                        $(option).data('currencyCode', i.currencyCode);
                        $country.append(option);
                    });
                    $country.select2({
                        placeholder: "Select Country",
                        templateResult: formatCountry,
                        templateSelection: formatSelection,
                        escapeMarkup: m => m
                    });

                    // trigger select2 to re-evaluate options
                    // $country.trigger('change');
                    // $country.val(null).trigger('change'); // reset select2 display

                },
                error: function() {
                    $country.empty().append('<option value="">Unable to load countries</option>');
                    $country.trigger('change');
                }
            });

            // Format function for dropdown results
            function formatCountry(option) {
                if (!option.id) {
                    return option.text;
                }

                let flag = $(option.element).data('flag') || option.flag || '';
                let symbol = $(option.element).data('currencySymbol') || option.currencySymbol || '';

                const img = flag ? `<img src="${flag}" class="country-flag" alt="${option.text} flag" />` : '';
                const symbolText = symbol ? ` (${symbol})` : '';
                return `<span>${img}<span class="country-option-text">${option.text}${symbolText}</span></span>`;
            }

            function formatSelection(option) {
                if (!option.id) {
                    return option.text;
                }
                let flag = $(option.element).data('flag') || option.flag || '';
                const img = flag ? `<img src="${flag}" class="country-flag" alt="${option.text} flag" />` : '';
                return `${img}<span class="country-option-text">${option.text}</span>`;
            }
        });
    </script>
@endpush
