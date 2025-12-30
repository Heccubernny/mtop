@extends('user.layouts.user_auth')

@php
    $lang = selectedLang();
    $auth_slug = Illuminate\Support\Str::slug(App\Constants\SiteSectionConst::AUTH_SECTION);
    $auth_text = App\Models\Admin\SiteSections::getData($auth_slug)->first();
@endphp

@push('css')
<style>
    .input-group-text span {
        max-width: 20ch !important;
    }

    /* Fix password eye icon alignment */
    .password-wrapper {
        position: relative;
    }
    .password-wrapper input {
        padding-right: 2.8rem; /* space for the eye icon */
    }
    .password-toggle {
        position: absolute;
        top: 50%;
        right: 0.9rem;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #6c757d;
        cursor: pointer;
    }
    .password-toggle:hover {
        color: #0d6efd;
    }
</style>
@endpush

@section('content')
<section class="account">
    <div class="account-area">
        <div class="account-wrapper">
            <!-- Logo -->
            <div class="account-logo text-center">
                <a href="{{ setRoute('index') }}" class="site-logo">
                    <img src="{{ get_logo($basic_settings) }}" 
                         data-white_img="{{ get_logo($basic_settings,'white') }}"
                         data-dark_img="{{ get_logo($basic_settings,'dark') }}"
                         alt="site-logo">
                </a>
            </div>

            <!-- Title -->
            <h5 class="title">{{ __("Log in and Stay Connected") }}</h5>
            <p>{{ __(@$auth_text->value->language->$lang->login_text) }}</p>

            <!-- Login Form -->
            <form class="account-form" action="{{ setRoute('user.login.submit') }}" method="POST">
                @csrf
                <input type="hidden" name="login_type" value="{{ global_const()::EMAIL }}">

                <div class="row ml-b-20">

                    <!-- Email Field -->
                    <div class="col-xl-12 col-lg-12 form-group">
                        <label>{{ __("Email Address") }} <span class="text--base">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text copytext">{{ __("Email") }}</span>
                            </div>
                            <input 
                                type="email" 
                                name="credentials" 
                                class="form--control" 
                                placeholder="{{ __('Enter Email Address') }}" 
                                value="{{ old('credentials') }}" 
                                required>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="col-lg-12 form-group">
                        <label>{{ __("Password") }} <span class="text--base">*</span></label>
                        <div class="password-wrapper">
                            <input 
                                type="password" 
                                required 
                                class="form-control form--control" 
                                name="password" 
                                placeholder="{{ __('Enter Password') }}">
                            <button type="button" class="password-toggle">
                                <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Forgot Password -->
                    <div class="col-lg-12 form-group">
                        <div class="forgot-item text-right">
                            <a href="{{ setRoute('user.password.forgot') }}">{{ __("Forgot Password?") }}</a>
                        </div>
                    </div>

                    <!-- Recaptcha + Submit -->
                    <div class="col-lg-12 form-group text-center">
                        <x-security.google-recaptcha-field />
                        <button type="submit" class="btn--base w-100 btn-loading">
                            {{ __("Login Now") }} <i class="las la-arrow-right"></i>
                        </button>
                    </div>

                    <!-- Register Option -->
                    @if($basic_settings->user_registration)
                    <div class="or-area">
                        <span class="or-line"></span>
                        <span class="or-title">{{ __("Or") }}</span>
                        <span class="or-line"></span>
                    </div>
                    <div class="col-lg-12 text-center">
                        <div class="account-item">
                            <label>{{ __("Don't Have An Account?") }}
                                <a href="{{ setRoute('user.register') }}" class="account-control-btn">{{ __("Register Now") }}</a>
                            </label>
                        </div>
                    </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Background bubbles -->
<ul class="bg-bubbles">
    <li></li><li></li><li></li><li></li><li></li>
    <li></li><li></li><li></li><li></li><li></li>
</ul>
@endsection

@push('script')
<script>
    // Password visibility toggle
    $(document).on("click", ".password-toggle", function (e) {
        e.preventDefault();
        const input = $(this).siblings('input');
        const icon = $(this).find('i');
        if (input.attr("type") === "password") {
            input.attr("type", "text");
            icon.removeClass("fa-eye-slash").addClass("fa-eye");
        } else {
            input.attr("type", "password");
            icon.removeClass("fa-eye").addClass("fa-eye-slash");
        }
    });
</script>
@endpush
