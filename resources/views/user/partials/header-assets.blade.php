<!-- favicon -->
<link rel="shortcut icon" href="{{ get_fav($basic_settings) }}" type="image/x-icon">
<!-- fontawesome css link -->
<link rel="stylesheet" href="{{ asset('frontend/') }}/css/fontawesome-all.css">
<!-- line-awesome-icon css -->
<link rel="stylesheet" href="{{ asset('frontend/') }}/css/line-awesome.css">
<!-- bootstrap css link -->
<link rel="stylesheet" href="{{ asset('frontend/') }}/css/bootstrap.css">
<!-- swipper css link -->
<link rel="stylesheet" href="{{ asset('frontend/') }}/css/swiper.css">

<!-- animate css link -->
<link rel="stylesheet" href="{{ asset('frontend/') }}/css/animate.css">

<link rel="stylesheet" href="{{ asset('backend/css/select2.css') }}">
<link rel="stylesheet" href="{{ asset('backend/library/popup/magnific-popup.css') }}">
<!-- nice-select css link -->
<link rel="stylesheet" href="{{ asset('frontend/') }}/css/nice-select.css">
<link rel="stylesheet" href="{{ asset('frontend/') }}/css/virtual-card.css">
<!-- Fileholder CSS CDN -->
<link rel="stylesheet" href="https://appdevs.cloud/cdn/fileholder/v1.0/css/fileholder-style.css" type="text/css">
<!-- main style css link -->
<link rel="stylesheet" href="{{ asset('frontend/') }}/css/style.css">
@php
    $color = @$basic_settings->base_color ?? '#000000';

@endphp

<style>
    :root {
        --primary-color: {{$color}};
    }

.custom-toast-popup {
        background: var(--base-color, #1e90ff);
        color: #fff !important;
        border-radius: 6px;
    }

    .custom-toast-title {
        font-weight: 500;
        font-family: 'Poppins', sans-serif;
    }

</style>
