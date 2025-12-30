@extends('admin.layouts.master')

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [['name' => __('Dashboard'), 'url' => setRoute('admin.dashboard')]],
        'active' => __($page_title),
    ])
@endsection

@section('content')
    {{-- ======================== RELOADLY SETTINGS ======================== --}}
    <div class="custom-card mb-4">
        <div class="card-header">
            <h6 class="title">Reloadly API Configuration</h6>
        </div>

        <div class="card-body">
            <form class="card-form" action="{{ setRoute('admin.bill.pay.method.automatic.api.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-10-none">
                    <div class="col-xl-4 col-md-4 form-group">
                        <label>Client ID *</label>
                        <div class="input-group append">
                            <span class="input-group-text"><i class="las la-key"></i></span>
                            <input class="form--control" name="client_id" type="text"
                                value="{{ @$reloadlyApi->credentials->client_id }}">
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-4 form-group">
                        <label>Secret Key *</label>
                        <div class="input-group append">
                            <span class="input-group-text"><i class="las la-key"></i></span>
                            <input class="form--control" name="secret_key" type="text"
                                value="{{ @$reloadlyApi->credentials->secret_key }}">
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-4 form-group">
                        <label>Production URL *</label>
                        <div class="input-group append">
                            <span class="input-group-text"><i class="las la-link"></i></span>
                            <input class="form--control" name="production_base_url" type="text"
                                value="{{ @$reloadlyApi->credentials->production_base_url }}">
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-4 form-group">
                        <label>Sandbox URL *</label>
                        <div class="input-group append">
                            <span class="input-group-text"><i class="las la-link"></i></span>
                            <input class="form--control" name="sandbox_base_url" type="text"
                                value="{{ @$reloadlyApi->credentials->sandbox_base_url }}">
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-4 form-group">
                        @include('admin.components.form.switcher', [
                            'label' => __('Api ENV') . '*',
                            'name' => 'env',
                            'value' => @$reloadlyApi->env,
                            'options' => [
                                __('Production') => global_const()::ENV_PRODUCTION,
                                __('Sandbox') => global_const()::ENV_SANDBOX,
                            ],
                        ])
                    </div>
                    <div class="col-xl-4 col-md-4 form-group">
                        @include('admin.components.form.switcher', [
                            'label' => __('Status'),
                            'value' => @$reloadlyApi->status,
                            'name' => 'status',
                            'options' => [
                                __('Active') => 1,
                                __('Inactive') => 0,
                            ],
                        ])
                    </div>

                    <div class="col-xl-12 form-group">
                        @include('admin.components.button.form-btn', [
                            'class' => 'w-100 btn-loading',
                            'text' => __('Update Reloadly Settings'),
                            'permission' => 'admin.bill.pay.method.automatic.api.update',
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ======================== CLUBKONNECT SETTINGS ======================== --}}
    <div class="custom-card mb-4">
        <div class="card-header">
            <h6 class="title">ClubKonnect API Configuration</h6>
        </div>

        <div class="card-body">
            <form class="card-form" action="{{ setRoute('admin.bill.pay.method.automatic.ck.api.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-10-none">

                    {{-- API CONFIG --}}
                    <div class="col-xl-4 col-md-4 form-group">
                        <label>API URL *</label>
                        <input class="form--control" name="api_url" type="text"
                            value="{{ @$clubkonnectApi->credentials->api_url }}">
                    </div>

                    <div class="col-xl-4 col-md-4 form-group">
                        <label>API Key *</label>
                        <input class="form--control" name="api_key" type="text"
                            value="{{ @$clubkonnectApi->credentials->api_key }}">
                    </div>

                    <div class="col-xl-4 col-md-4 form-group">
                        <label>User ID *</label>
                        <input class="form--control" name="user_id" type="text"
                            value="{{ @$clubkonnectApi->credentials->user_id }}">
                    </div>

                    <div class="col-xl-4 col-md-4 form-group">
                        <label>Callback URL *</label>
                        <input class="form--control" name="callback_url" type="text"
                            value="{{ @$clubkonnectApi->credentials->callback_url }}">
                    </div>

                    {{-- ENV --}}
                    <div class="col-xl-4 col-md-4 form-group">
                        @include('admin.components.form.switcher', [
                            'label' => __('Api ENV'),
                            'name' => 'env',
                            'value' => @$clubkonnectApi->env,
                            'options' => [
                                __('Production') => global_const()::ENV_PRODUCTION,
                                __('Sandbox') => global_const()::ENV_SANDBOX,
                            ],
                        ])
                    </div>

                    {{-- STATUS --}}
                    <div class="col-xl-4 col-md-4 form-group">
                        @include('admin.components.form.switcher', [
                            'label' => __('Status'),
                            'name' => 'status',
                            'value' => @$clubkonnectApi->status,
                            'options' => [
                                __('Active') => 1,
                                __('Inactive') => 0,
                            ],
                        ])
                    </div>

                    {{-- ================= SERVICE CHARGES ================= --}}
                    <div class="col-xl-12">
                        <hr>
                        <h6 class="mb-3">Service Charges <small class="text-muted">(NGN)</small></h6>
                    </div>

                    {{-- ================= DATA CHARGES ================= --}}
                    <div class="col-xl-12">
                        <h6 class="mb-2">Data Charges</h6>
                    </div>

                    <div class="col-xl-6 col-md-6 form-group">
                        <label>Fixed Charge (₦)</label>
                        <input class="form--control" name="charges[data][fixed]" type="number"
                            value="{{ @$clubkonnectApi->credentials->charges->data->fixed ?? 0 }}" step="0.01">
                    </div>

                    <div class="col-xl-6 col-md-6 form-group">
                        <label>Percentage Charge (%)</label>
                        <input class="form--control" name="charges[data][percentage]" type="number"
                            value="{{ @$clubkonnectApi->credentials->charges->data->percentage ?? 0 }}" step="0.01">
                    </div>

                    {{-- ================= AIRTIME CHARGES ================= --}}
                    <div class="col-xl-12 mt-3">
                        <h6 class="mb-2">Airtime Charges</h6>
                    </div>

                    <div class="col-xl-6 col-md-6 form-group">
                        <label>Fixed Charge (₦)</label>
                        <input class="form--control" name="charges[airtime][fixed]" type="number"
                            value="{{ @$clubkonnectApi->credentials->charges->airtime->fixed ?? 0 }}" step="0.01">
                    </div>

                    <div class="col-xl-6 col-md-6 form-group">
                        <label>Percentage Charge (%)</label>
                        <input class="form--control" name="charges[airtime][percentage]" type="number"
                            value="{{ @$clubkonnectApi->credentials->charges->airtime->percentage ?? 0 }}"
                            step="0.01">
                    </div>

                    {{-- ================= CABLE TV CHARGES ================= --}}
                    <div class="col-xl-12 mt-3">
                        <h6 class="mb-2">Cable TV Charges</h6>
                    </div>

                    <div class="col-xl-6 col-md-6 form-group">
                        <label>Fixed Charge (₦)</label>
                        <input class="form--control" name="charges[cabletv][fixed]" type="number"
                            value="{{ @$clubkonnectApi->credentials->charges->cabletv->fixed ?? 0 }}" step="0.01">
                    </div>

                    <div class="col-xl-6 col-md-6 form-group">
                        <label>Percentage Charge (%)</label>
                        <input class="form--control" name="charges[cabletv][percentage]" type="number"
                            value="{{ @$clubkonnectApi->credentials->charges->cabletv->percentage ?? 0 }}"
                            step="0.01">
                    </div>

                    {{-- ================= DATA CATEGORY TOGGLES PER NETWORK ================= --}}
                    <div class="col-xl-12">
                        <hr>
                        <h6 class="mb-3">Data Category Availability Per Network</h6>
                    </div>

                    @php
                        $networks = ['mtn' => 'MTN', 'glo' => 'Glo', 'airtel' => 'Airtel', '9mobile' => '9Mobile'];
                        $categories = ['sme' => 'SME Data', 'direct' => 'Direct Data', 'awoof' => 'Awoof Data'];
                    @endphp

                    @foreach ($networks as $networkKey => $networkLabel)
                        <div class="col-xl-12 mb-2">
                            <h6>{{ $networkLabel }}</h6>
                        </div>

                        @foreach ($categories as $catKey => $catLabel)
                            <div class="col-xl-4 col-md-4 form-group">
                                @php
                                    $status =
                                        @$clubkonnectApi->credentials->networks->{$networkKey}->data_categories
                                            ->{$catKey}->status ?? 0;
                                @endphp
                                @include('admin.components.form.switcher', [
                                    'label' => $catLabel,
                                    'name' => "networks[$networkKey][data_categories][$catKey][status]",
                                    'value' => $status,
                                    'options' => [
                                        __('Enabled') => 1,
                                        __('Disabled') => 0,
                                    ],
                                ])
                            </div>
                        @endforeach
                    @endforeach

                    {{-- SUBMIT --}}
                    <div class="col-xl-12 form-group mt-3">
                        @include('admin.components.button.form-btn', [
                            'class' => 'w-100 btn-loading',
                            'text' => __('Update ClubKonnect Settings'),
                            'permission' => 'admin.bill.pay.method.automatic.ck.api.update',
                        ])
                    </div>

                </div>
            </form>
        </div>
    </div>
@endsection
