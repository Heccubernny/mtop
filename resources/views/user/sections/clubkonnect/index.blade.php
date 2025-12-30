@extends('user.layouts.master')

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [['name' => __('Dashboard'), 'url' => setRoute('user.dashboard')]],
        'active' => __('List Services'),
    ])
@endsection

@section('content')
    <style>
        /* Wrapper */
        .service-wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* Fade animation */
        .fade-in {
            animation: fadeIn .6s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Card */
        .service-card {
            text-decoration: none;
            display: block;
            height: 100%;
        }

        .custom-card {
            border-radius: 22px;
            padding: 32px 22px;
            color: #fff;
            height: 100%;
            transition: all .3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .custom-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 30px rgba(0, 0, 0, .18);
        }

        /* Icon */
        .icon-circle {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .25);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
        }

        .icon-circle i {
            font-size: 34px;
            color: #fff;
        }

        /* Gradients */
        .airtime-card {
            background: linear-gradient(135deg, #4CAFED, #0073FF);
        }

        .data-card {
            background: linear-gradient(135deg, #5EDC9A, #00B86B);
        }

        .cable-card {
            background: linear-gradient(135deg, #FF9A3D, #FF6A00);
        }

        h4 {
            font-weight: 600;
            margin-bottom: 6px;
        }

        .text-muted {
            color: rgba(255, 255, 255, .85) !important;
        }

        /* Mobile tuning */
        @media (max-width: 576px) {
            .custom-card {
                padding: 26px 18px;
            }
        }
    </style>

    <div class="body-wrapper">
        <div class="dashboard-area mt-4">
            <div class="row g-3 justify-content-center service-wrapper">

                <!-- Airtime -->
                <div class="col-12 col-sm-6 col-md-4 fade-in">
                    <a class="service-card" href="{{ route('user.airtime.index') }}">
                        <div class="custom-card airtime-card">
                            <div class="icon-circle">
                                <i class="las la-phone-volume"></i>
                            </div>
                            <h4>Airtime</h4>
                            <p class="text-muted small">Buy instant airtime.</p>
                        </div>
                    </a>
                </div>

                <!-- Data -->
                <div class="col-12 col-sm-6 col-md-4 fade-in">
                    <a class="service-card" href="{{ route('user.data.index') }}">
                        <div class="custom-card data-card">
                            <div class="icon-circle">
                                <i class="las la-wifi"></i>
                            </div>
                            <h4>Data</h4>
                            <p class="text-muted small">Purchase mobile data instantly.</p>
                        </div>
                    </a>
                </div>

                <!-- Cable -->
                <div class="col-12 col-sm-6 col-md-4 fade-in">
                    <a class="service-card" href="{{ route('user.cable.index') }}">
                        <div class="custom-card cable-card">
                            <div class="icon-circle">
                                <i class="las la-tv"></i>
                            </div>
                            <h4>Cable TV</h4>
                            <p class="text-muted small">Subscribe GOTV, DSTV & Startimes.</p>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </div>
@endsection
