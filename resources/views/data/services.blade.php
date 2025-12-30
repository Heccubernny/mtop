@extends('user.layouts.master')

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

        {{-- Page Header --}}
        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ __('Data Services') }}</h3>
            </div>
        </div>

        {{-- Data Service Selection --}}
        <div class="row mb-30-none justify-content-center">
            <div class="col-xl-6 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">

                        {{-- Title --}}
                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Select Data Provider') }}</h5>
                        </div>

                        {{-- Body --}}
                        <div class="dash-payment-body">
                            <form class="card-form" id="dataServiceForm">
                                @csrf
                                <div class="row">

                                    {{-- Provider Dropdown --}}
                                    <div class="col-xl-12 form-group">
                                        <label class="fw-bold">
                                            {{ __('Select Data Provider') }}
                                            <span class="text--base">*</span>
                                        </label>

                                        <select class="form--control" id="serviceSelect" required>
                                            <option value="" disabled selected>{{ __('Choose a provider') }}</option>
                                            @foreach ($services as $key => $service)
                                                <option value="{{ $key }}">{{ $service['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Continue Button --}}
                                    <div class="col-xl-12">
                                        <button class="btn--base w-100 btn-loading mobileTopupBtn" type="submit">
                                            {{ __('Continue') }}
                                            <i class="fas fa-arrow-alt-circle-right"></i>
                                        </button>
                                    </div>

                                </div>
                            </form>
                        </div>

                    </div>
                </div>

                <div class="d-none mt-4 text-center" id="servicePreview">
                    <img class="img-fluid mb-2" id="previewIcon" src="" alt="" style="height:60px;">
                    <h6 class="fw-semibold" id="previewName"></h6>
                    <p class="text-muted small mb-0" id="previewDesc"></p>
                </div>
            </div>
        </div>

        <div class="dashboard-list-area mt-20">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __('Mobile Topup Log') }}</h4>
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
    </div>
@endsection

@push('css')
    <style>
        .form-select:focus,
        .form--control:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }

        #servicePreview img {
            transition: transform 0.3s ease;
        }

        #servicePreview img:hover {
            transform: scale(1.1);
        }
    </style>
@endpush

@push('script')
    <script>
        $(document).ready(function() {

            const services = @json($services);

            // Update preview on selection
            $('#serviceSelect').on('change', function() {
                const key = $(this).val();
                const service = services[key];

                if (service) {
                    $("#servicePreview").removeClass("d-none");
                    $("#previewIcon").attr("src", service.icon);
                    $("#previewName").text(service.name);
                    $("#previewDesc").text(service.description);
                }
            });

            // Continue button action
            $('#dataServiceForm').on('submit', function(e) {
                e.preventDefault();
                const selectedKey = $('#serviceSelect').val();

                if (!selectedKey) {
                    notify('error', "{{ __('Please select a data provider first.') }}");
                    return;
                }

                window.location.href = `{{ url('user/data') }}/${selectedKey}`;
            });
        });
    </script>
@endpush
