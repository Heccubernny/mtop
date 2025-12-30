@extends('user.layouts.master')

@push('css')
<style>
#verifyForm .form-control,
#verifyBtn {
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
    margin-top: 15px;
    border: 1px solid #e0e6ed;
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
        ['name' => __('Services'), 'url' => setRoute('user.cabletv.index')]
    ],
    'active' => __(strtoupper($service) . ' Subscription')
])
@endsection

@section('content')
<div class="body-wrapper">
    <div class="dashboard-area mt-10">
        <div class="dashboard-header-wrapper d-flex justify-content-between align-items-center">
            <h3 class="title">{{ __(strtoupper($service) . ' Subscription') }}</h3>
            {{-- <button class="btn btn--base btn-sm" data-bs-toggle="modal" data-bs-target="#limitModal">
                <i class="las la-info-circle me-1"></i> {{ __("View Limit Info") }}
            </button> --}}
        </div>
    </div>

    <div class="row mb-30-none">
        {{-- Subscription Form --}}
        <div class="col-xl-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __("Subscription Form") }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        @if(in_array($service, ['dstv', 'gotv', 'startimes']))
                        <form id="verifyForm" class="mb-4" onsubmit="return false;">
                            @csrf
                            <input type="hidden" name="exchange_rate">
                            <input type="hidden" name="biller_item_type">

                            {{-- <div class="text-center mb-3">
                                <code class="d-block">Exchange Rate: <span class="rate-show">--</span></code>
                            </div> --}}

                            <div class="row align-items-end g-2">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">{{ __("Smartcard Number") }}</label>
                                    <input type="text" id="smartcard_number" name="smartcard_number"
                                           class="form-control form--control" placeholder="{{ __('Enter Smartcard Number') }}" required>
                                    <small id="verifyMessage" class="form-text mt-1 d-block"></small>
                                </div>
                                <div class="col-md-4 d-flex">
                                    <button type="button" id="verifyBtn" class="btn btn--base w-100 align-self-end">
                                        {{ __("Verify") }}
                                    </button>
                                </div>
                            </div>
                        </form>
                        @endif

                        <form method="POST" action="{{ route('user.cabletv.buy', $service) }}" id="buyForm">
                            @csrf
                            <input type="hidden" name="smartcard_number" id="smartcardInput">

                            <div class="form-group mb-3">
                                <label>{{ __("Phone Number") }} <span class="text--base">*</span></label>
                                <input type="text" name="phone" id="phone" class="form--control" required
                                       placeholder="{{ __('Enter Phone Number') }}">
                            </div>

                            <div class="form-group mb-3">
                                <label>{{ __("Select Plan") }} <span class="text--base">*</span></label>
                                <select name="variation_code" id="variation_code" class="form--control" required>
                                    <option value="">{{ __('-- Select a Plan --') }}</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->variation_code }}" data-amount="{{ $plan->amount }}">
                                            {{ $plan->name }} 
                                            {{-- — ₦{{ number_format($plan->amount, 2) }} --}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>{{ __("Amount") }}</label>
                                <div class="input-group">
                                    <input type="text" name="amount" id="amount" class="form--control" readonly>
                                    <select class="form--control nice-select currency" name="currency">
                                        @foreach ($sender_wallets ?? [] as $data)
                                            <option value="{{ $data->code }}"
                                                data-rate="{{ $data->rate }}"
                                                data-type="{{ $data->type }}"
                                                data-currency-id="{{ $data->id }}"
                                                data-sender-country-name="{{ $data->name }}">
                                                {{ $data->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- <div class="note-area mb-3">
                                <code class="d-block fw-bold balance-show">--</code>
                            </div> --}}

                            <button type="submit" id="buyBtn" class="btn--base w-100 btn-loading" disabled>
                                {{ __("Buy Subscription") }} <i class="fas fa-tv ms-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Preview Section --}}
        <div class="col-xl-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __("Preview") }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="preview-list-wrapper">
                            <div class="preview-list-item"><i class="las la-tv me-2"></i> {{ __("Service") }} <span class="float-end">{{ strtoupper($service) }}</span></div>
                            <div class="preview-list-item"><i class="las la-list-ol me-2"></i> {{ __("Smartcard Number") }} <span class="float-end" id="smartcardPreview">--</span></div>
                            <div class="preview-list-item"><i class="las la-user-check me-2"></i> {{ __("Customer Name") }} <span class="float-end" id="cust_name">--</span></div>
                            <div class="preview-list-item"><i class="las la-id-card me-2"></i> {{ __("Customer Type") }} <span class="float-end" id="cust_type">--</span></div>
                            <div class="preview-list-item"><i class="las la-calendar me-2"></i> {{ __("Due Date") }} <span class="float-end" id="cust_due">--</span></div>
                            <div class="preview-list-item"><i class="las la-bell me-2"></i> {{ __("Status") }} <span class="float-end" id="cust_status">--</span></div>
                            <div class="preview-list-item"><i class="las la-phone me-2"></i> {{ __("Phone Number") }} <span class="float-end" id="cust_number">--</span></div>
                            <div class="preview-list-item"><i class="las la-coins me-2"></i> {{ __("Amount") }} <span class="float-end" id="preview_amount">--</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Limit Information Modal --}}   
 
        {{-- <div class="dash-payment-item-wrapper limit">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{__("Limit Information")}}</h5>
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
                                            <span>{{ __("Transaction Limit") }}</span>
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
                                                <span>{{ __("Daily Limit") }}</span>
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
                                                <span>{{ __("Remaining Daily Limit") }}</span>
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
                                                <span>{{ __("Monthly Limit") }}</span>
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
                                                <span>{{ __("Remaining Monthly Limit") }}</span>
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
                </div> --}}

        {{-- Pay Logs --}}
        <div class="col-12 mt-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>{{ __("Cable TV Payment Logs") }}</h5>
                </div>
                <div class="card-body">
                    @if($transactions->count())
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __("Date") }}</th>
                                        <th>{{ __("Smartcard") }}</th>
                                        <th>{{ __("Amount") }}</th>
                                        <th>{{ __("Status") }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $txn)
                                        <tr>
                                            <td>{{ showDateTime($txn->created_at) }}</td>
                                            <td>{{ $txn->smartcard_number }}</td>
                                            <td>₦{{ number_format($txn->amount, 2) }}</td>
                                            <td>{!! $txn->getStatusBadge() !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted">{{ __("No cable payment logs found.") }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- <div class="modal fade" id="limitModal" tabindex="-1" aria-labelledby="limitModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __("Limit Information") }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="limit-section">
          <div class="limit-item"><span>Daily Limit:</span> <span class="limit-daily">--</span></div>
          <div class="limit-item"><span>Monthly Limit:</span> <span class="limit-monthly">--</span></div>
          <hr>
          <div class="limit-item"><span>Remaining Daily:</span> <span class="daily-remaining">--</span></div>
          <div class="limit-item"><span>Remaining Monthly:</span> <span class="monthly-remaining">--</span></div>
        </div>
      </div>
    </div>
  </div>
</div> --}}
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ✅ Replace alert() calls with SweetAlert Toasts
function showToast(message, icon = 'success') {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title: message,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
    });
}
</script>

{{-- your JS functions from before remain here exactly as written --}}
<script>
    document.addEventListener("DOMContentLoaded", () => {
    const verifyBtn = document.getElementById("verifyBtn");
    const verifyMessage = document.getElementById("verifyMessage");
    const phoneInput = document.getElementById("phone");
    const buyBtn = document.getElementById("buyBtn");
    const smartcardInput = document.getElementById("smartcardInput");

    // ✅ Verify Button
    verifyBtn?.addEventListener("click", async (e) => {
        e.preventDefault();
        const smartcard = document.getElementById("smartcard_number").value.trim();
        if (!smartcard) return alert("Enter Smartcard Number");

        verifyBtn.disabled = true;
        verifyBtn.classList.add('loading');
        verifyBtn.innerHTML = `<i class='fas fa-spinner fa-spin me-1'></i> Verifying...`;

        try {
            const res = await fetch("{{ route('user.cabletv.verify', $service) }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ smartcard_number: smartcard }),
            });

            const data = await res.json();
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = "Verify";

            if (data?.success) {
                const info = data.data || {};
                verifyMessage.textContent = "✅ Smartcard verified successfully!";
                verifyMessage.className = "text-success";

                document.getElementById("smartcardPreview").textContent = smartcard;
                document.getElementById("cust_name").textContent = info.customer_name || "--";
                document.getElementById("cust_type").textContent = info.customer_type || "--";
                document.getElementById("cust_due").textContent = info.due_date || "--";
                document.getElementById("cust_status").textContent = info.status || "--";
                document.getElementById("cust_number").textContent = info.customer_number || "--";
                phoneInput.value = info.customer_number || "";
                smartcardInput.value = smartcard;
                buyBtn.disabled = false;
            } else {
                verifyMessage.textContent = data.message || "Verification failed.";
                verifyMessage.className = "text-danger";
                buyBtn.disabled = true;
            }

        } catch (error) {
            console.error("Error:", error);
            verifyMessage.textContent = "Network error. Try again.";
            verifyMessage.className = "text-danger";
            verifyBtn.disabled = false;
            verifyBtn.classList.remove('loading');
            verifyBtn.innerHTML = "Verify";
        }
    });

    // ✅ Update amount + preview
    const planSelect = document.getElementById("variation_code");
    planSelect?.addEventListener("change", function () {
        const amount = parseFloat(this.selectedOptions[0]?.dataset.amount || 0);
        document.getElementById("amount").value = amount;
        document.getElementById("preview_amount").textContent = `₦${amount.toFixed(2)}`;
        
    });

    // ✅ Live phone preview
    phoneInput?.addEventListener("input", function() {
        document.getElementById("cust_number").textContent = this.value || "--";
    });

    $("select[name=currency]").change(function(){
        senderBalance();
        getFees();
        getExchangeRate();
        getLimit();
        getDailyMonthlyLimit();
        get_remaining_limits();

    });

    
function acceptVar() {
        var senderCurrSelectedVal   = $("select[name=currency] :selected");
        var senderCurrencyCode      = $("select[name=currency] :selected").val();
        var senderCurrencyRate      = $("select[name=currency] :selected").data('rate');
        var senderCurrencyType      = $("select[name=currency] :selected").data('type');

        var defaultCurrencyCode     = defualCurrency;
        var defaultCurrencyRate     = defualCurrencyRate;

        var currencyMinAmount       = "{{getAmount($billPayCharge->min_limit)}}";
        var currencyMaxAmount       = "{{getAmount($billPayCharge->max_limit)}}";

        var currencyFixedCharge     = "{{getAmount($billPayCharge->fixed_charge)}}";
        var currencyPercentCharge   = "{{getAmount($billPayCharge->percent_charge)}}";

        var currencyDailyLimit      = "{{getAmount($billPayCharge->daily_limit)}}";
        var currencyMonthlyLimit      = "{{getAmount($billPayCharge->monthly_limit)}}";

        var billType                = $("select[name=bill_type] :selected");
        var billName                = $("select[name=bill_type] :selected").data("name");
        var billMonth               = $("select[name=bill_month] :selected").val();
        var billNumber              = $("input[name=bill_number]").val();

        if(senderCurrencyType == "CRYPTO"){
            var senderPrecison = "{{ get_precision_from_admin()['crypto_precision_value'] }}";
        }else{
            var senderPrecison = "{{  get_precision_from_admin()['fiat_precision_value'] }}";
        }


        return {
            sCurrSelectedVal:senderCurrSelectedVal,
            SCurrencyCode:senderCurrencyCode,
            SCurrencyRate:senderCurrencyRate,
            sPrecison:senderPrecison,

            defaultCurrencyCode:defaultCurrencyCode,
            defaultCurrencyRate:defaultCurrencyRate,

            currencyMinAmount:currencyMinAmount,
            currencyMaxAmount:currencyMaxAmount,

            currencyFixedCharge:currencyFixedCharge,
            currencyPercentCharge:currencyPercentCharge,

            currencyDailyLimit:currencyDailyLimit,
            currencyMonthlyLimit:currencyMonthlyLimit,

            billName:billName,
            billNumber:billNumber,
            billMonth:billMonth,
            billType:billType,


        };
    }
});
function senderBalance(){
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
                $('.balance-show').html("{{ __('Available Balance') }}: " + parseFloat(response.data).toFixed(acceptVar().sPrecison) + " " + senderCurrency);
            }
        });
    }

    function getFees() {
        var currencyCode = acceptVar().SCurrencyCode;
        var exchangeRate =  parseFloat($("input[name=exchange_rate]").val());
        if(acceptVar().billType.val() === "null"){
            return false;
        }
        var percent = acceptVar().currencyPercentCharge;
        var charges = feesCalculation();
        if (charges == false) {
                return false;
        }
        $(".fees-show").html(parseFloat(charges.fixed).toFixed(acceptVar().sPrecison) + " " + currencyCode + " + " + parseFloat(charges.percent).toFixed(acceptVar().sPrecison) + "% = " + parseFloat(charges.total).toFixed(acceptVar().sPrecison) + " " + currencyCode);


    }

    function feesCalculation() {
        var exchangeRate =  parseFloat($("input[name=exchange_rate]").val());
        var currencyCode = acceptVar().SCurrencyCode;
        var currencyRate = acceptVar().SCurrencyRate;
        var sender_amount = $("input[name=amount]").val();
        sender_amount == "" ? (sender_amount = 0) : (sender_amount = sender_amount);

        var fixed_charge = acceptVar().currencyFixedCharge;
        var percent_charge = acceptVar().currencyPercentCharge;
        if ($.isNumeric(percent_charge) && $.isNumeric(fixed_charge) && $.isNumeric(sender_amount)) {
            // Process Calculation
            var fixed_charge_calc = parseFloat(fixed_charge*currencyRate);
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
        type:'get',
            url:"{{ route('global.receiver.wallet.currency') }}",
            data:{code:receiver_currency_code},
            success:function(data){
                var receiverCurrencyCode = data.code;
                var receiverCurrencyRate = data.rate;
                var exchangeRate = (receiverCurrencyRate/walletCurrencyRate);

                $("input[name=exchange_rate]").val(exchangeRate);
                $('.rate-show').html("1 " +walletCurrencyCode+ " = " + parseFloat(exchangeRate).toFixed(acceptVar().sPrecison) + " " + receiverCurrencyCode);
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

    function enterLimit(){
        var exchangeRate =  parseFloat($("input[name=exchange_rate]").val());

        if (acceptVar().billType.data('item-type') === "AUTOMATIC") {
            var item = acceptVar().billType.data('item');
            var min_limit =( parseFloat(item.minLocalTransactionAmount) / parseFloat(exchangeRate));
            var max_limit = (parseFloat(item.maxLocalTransactionAmount)/ parseFloat(exchangeRate));

        } else {
            var min_limit = parseFloat(acceptVar().currencyMinAmount/exchangeRate);
            var max_limit = parseFloat(acceptVar().currencyMaxAmount/exchangeRate);
        }


        var sender_amount = parseFloat($("input[name=amount]").val());
       // Corrected code:
        if (sender_amount === "NaN" || sender_amount === "") {
            sender_amount = 0;
        }
        if( sender_amount < min_limit ){
            return{
                message: '{{ __("Please follow the mimimum limit") }}',
                type   : 'min'
            }
        }else if(sender_amount > max_limit){
            return{
                message: '{{ __("Please follow the maximum limit") }}',
                type   : 'max'
            }
        }else{
            $('.billPayBtn').attr('disabled',false);
            return{
                message: 'success',
                type   : 'success'
            }
        }

    }

    function getDailyMonthlyLimit(){
        var sender_currency = acceptVar().SCurrencyCode;
        var sender_currency_rate = acceptVar().SCurrencyRate;
        var daily_limit = acceptVar().currencyDailyLimit;
        var monthly_limit = acceptVar().currencyMonthlyLimit

        if($.isNumeric(daily_limit) && $.isNumeric(monthly_limit)) {
            if(daily_limit > 0 ){
                var daily_limit_calc = parseFloat(daily_limit * sender_currency_rate).toFixed(acceptVar().sPrecison);
                $('.limit-daily').html( daily_limit_calc + " " + sender_currency);
            }else{
                $('.limit-daily').html("");
            }

            if(monthly_limit > 0 ){
                var montly_limit_clac = parseFloat(monthly_limit * sender_currency_rate).toFixed(acceptVar().sPrecison);
                $('.limit-monthly').html( montly_limit_clac + " " + sender_currency);

            }else{
                $('.limit-monthly').html("");
            }

        }else {
            $('.limit-daily').html("--");
            $('.limit-monthly').html("--");
            return {
                dailyLimit:0,
                monthlyLimit:0,
            };
        }

    }

    function get_remaining_limits(){
        var csrfToken           = $('meta[name="csrf-token"]').attr('content');
        var user_field          = "user_id";
        var user_id             = "{{ userGuard()['user']->id }}";
        var transaction_type    = "{{ payment_gateway_const()::BILLPAY }}";
        var currency_id         = acceptVar().sCurrSelectedVal.data('currency-id');
        var sender_amount       = $("input[name=amount]").val();

        (sender_amount == "" || isNaN(sender_amount)) ? sender_amount = 0 : sender_amount = sender_amount;

        var charge_id           = "{{ $billPayCharge->id }}";
        var attribute           = "{{ payment_gateway_const()::SEND }}"

        $.ajax({
            type: 'POST',
            url: "{{ route('global.get.total.transactions') }}",
            data: {
                _token:             csrfToken,
                user_field:         user_field,
                user_id:            user_id,
                transaction_type:   transaction_type,
                currency_id:        currency_id,
                sender_amount:      sender_amount,
                charge_id:          charge_id,
                attribute:          attribute,
            },
            success: function(response) {
                var sender_currency = acceptVar().SCurrencyCode;

                var status  = response.status;
                var message = response.message;
                var amount_data = response.data;

                if(status == false){
                    $('.daily-remaining').html(amount_data.remainingDailyTxnSelected + " " + sender_currency);
                    $('.monthly-remaining').html(amount_data.remainingMonthlyTxnSelected + " " + sender_currency);
                    throwMessage('error',[message]);
                    return false;
                }else{
                    $('.daily-remaining').html(amount_data.remainingDailyTxnSelected + " " + sender_currency);
                    $('.monthly-remaining').html(amount_data.remainingMonthlyTxnSelected + " " + sender_currency);
                }
            },
        });
    }

</script>
@endpush
