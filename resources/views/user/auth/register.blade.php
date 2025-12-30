@extends('user.layouts.user_auth')

@php
    $lang = selectedLang();
    $auth_slug = Illuminate\Support\Str::slug(App\Constants\SiteSectionConst::AUTH_SECTION);
    $auth_text = App\Models\Admin\SiteSections::getData($auth_slug)->first();
    $type = Illuminate\Support\Str::slug(App\Constants\GlobalConst::USEFUL_LINKS);
    $policies = App\Models\Admin\SetupPage::orderBy('id')
        ->where('type', $type)
        ->where('slug', "terms-and-conditions")
        ->where('status', 1)
        ->first();
@endphp

@section('content')
<section class="account">
    <div class="account-area">
        <div class="account-wrapper">

            {{-- Logo --}}
            <div class="account-logo text-center mb-4">
                <a class="site-logo" href="{{ setRoute('index') }}">
                    <img src="{{ get_logo($basic_settings) }}"  
                        data-white_img="{{ get_logo($basic_settings,'white') }}"
                        data-dark_img="{{ get_logo($basic_settings,'dark') }}"
                        alt="site-logo">
                </a>
            </div>

            {{-- Page Title --}}
            <h5 class="title text-center mb-2">{{ __("Register for an Account Today") }}</h5>
            <p class="text-center text-muted mb-4">{{ __(@$auth_text->value->language->$lang->register_text) }}</p>

            <form class="account-form" action="{{ route('user.send.code') }}" method="POST">
                @csrf
                <div class="row ml-b-20">

                    {{-- Hardcoded registration type --}}
                    <input type="hidden" name="register_type" value="{{ \App\Constants\GlobalConst::EMAIL }}">

                    {{-- Email Input --}}
                    <div class="col-xl-12 col-lg-12 form-group">
                        <label>{{ __("Email Address") }} <span class="text--base">*</span></label>
                        <input type="email" name="credentials" class="form--control checkUser email"
                               placeholder="{{ __('Enter Email Address') }}" required
                               value="{{ old('credentials') }}">
                    </div>

                    {{-- Identity Verification (BVN/NIN) --}}
                    <div class="col-xl-12 col-lg-12 form-group">
                        <label>{{ __("Identity Type") }} <span class="text--base">*</span></label>
                        <select name="identity_type" id="identity_type" class="form--control nice-select" required>
                            <option value="" disabled selected>{{ __('Select Type') }}</option>
                            <option value="bvn" {{ old('identity_type') == 'bvn' ? 'selected' : '' }}>BVN</option>
                            <option value="nin" {{ old('identity_type') == 'nin' ? 'selected' : '' }}>NIN</option>
                        </select>
                    </div>

                    {{-- BVN Fields --}}
                    <div id="bvnFields" style="display:none;">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 form-group">
                                <label for="bvn_input">{{ __("BVN") }} <span class="text--base">*</span></label>
                                <input id="bvn_input" type="text" name="bvn" class="form--control" placeholder="{{ __('Enter 11-digit BVN') }}" maxlength="11" pattern="\d{11}" inputmode="numeric" aria-describedby="bvnHelp">
                                <small id="bvnHelp" class="text-muted">{{ __('Only digits, 11 characters') }}</small>
                            </div>

                            <div class="col-12 col-md-6 form-group">
                                <label for="identity_name_input">{{ __("Full Name") }}</label>
                                <input id="identity_name_input" type="text" name="name" class="form--control" placeholder="{{ __('Enter Full Name') }}" aria-describedby="fullNameHelp">
                                <small id="fullNameHelp" class="text-muted">{{ __('Surname, First Name, Middle Name') }}</small>
                            </div>

                            <div class="col-12 col-md-6 form-group">
                                <label for="identity_dob_input">{{ __("Date of Birth") }}</label>
                                <input id="identity_dob_input" type="date" name="dateOfBirth" class="form--control">
                            </div>

                            <div class="col-12 col-md-6 form-group">
                                <label for="identity_phone_input">{{ __("Mobile No") }}</label>
                                <input id="identity_phone_input" type="tel" name="mobileNo" class="form--control" placeholder="e.g. 08012345678" inputmode="tel">
                            </div>

                            <div class="col-12 form-group d-flex justify-content-end">
                                <button type="button" class="btn--base" id="verify_bvn_btn">{{ __('Verify') }}</button>
                            </div>
                        </div>
                    </div>

                    {{-- NIN Fields --}}
                    <div id="ninFields" style="display:none;">
                        <div class="col-xl-12 col-lg-12 form-group">
                            <label>{{ __("NIN") }} <span class="text--base">*</span></label>
                            <div class="d-flex gap-2">
                                <input type="text" id="nin_input" name="nin" class="form--control" placeholder="{{ __('Enter NIN') }}">
                                <button type="button" class="btn--base" id="verify_nin_btn">Verify</button>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden auto-filled fields --}}
                    <input type="hidden" name="identity_name" id="identity_name">
                    <input type="hidden" name="identity_dob" id="identity_dob">
                    <input type="hidden" name="identity_phone" id="identity_phone">

                    {{-- Terms --}}
                    @if($basic_settings->agree_policy)
                        <div class="col-lg-12 form-group">
                            <div class="custom-check-group">
                                <input type="checkbox" id="agree" name="agree" required>
                                <label for="agree">
                                    {{ __("I have agreed with") }}
                                    <a href="{{ $policies ? setRoute('useful.link',$policies->slug) : 'javascript:void(0)' }}"
                                       target="_blank">{{ __("Terms Of Use & Privacy Policy") }}</a>
                                </label>
                            </div>
                        </div>
                    @endif
@if(session('response'))
    <div class="mt-4 alert alert-{{ session('response.success') ? 'success' : 'danger' }}">
        <strong>{{ session('response.success') ? '✅ Success' : '❌ Failed' }}</strong><br>
        <p>{{ session('response.message') }}</p>
        @if(isset(session('response')['data']))
            <pre class="mt-2">{{ json_encode(session('response')['data'], JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>
@endif

                    {{-- Recaptcha + Submit --}}
                    <div class="col-lg-12 form-group text-center">
                        <x-security.google-recaptcha-field />
                        <button type="submit" class="btn--base w-100 btn-loading registerBtn" id="continue_btn" disabled>
                            {{ __("Continue") }}
                        </button>

                    </div>

                    {{-- Login link --}}
                    <div class="col-lg-12 text-center">
                        <div class="account-item">
                            <label>
                                {{ __("Already have an account?") }}
                                <a href="{{ setRoute('user.login') }}" class="account-control-btn">
                                    {{ __("Login Now") }}
                                </a>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

{{-- Bubbles --}}
<ul class="bg-bubbles">
    <li></li><li></li><li></li><li></li><li></li>
    <li></li><li></li><li></li><li></li><li></li>
</ul>
@endsection


@push('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

     
<script>
$(document).ready(function () {

    function setContinueEnabled(isEnabled) {
        $("#continue_btn").prop("disabled", !isEnabled);
    }

    function showToast(message, type = 'success') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
        });
        Toast.fire({
            icon: type,
            title: message
        });
    }

    const toggleIdentityFields = (value) => {
        $("#bvnFields").hide();
        $("#ninFields").hide();

        if (value === "bvn") $("#bvnFields").slideDown();
        if (value === "nin") $("#ninFields").slideDown();
    };

    // Trigger on load if old value exists
    toggleIdentityFields($("#identity_type").val());

    // Handle .nice-select change properly
    $(document).on("change", "#identity_type", function () {
        toggleIdentityFields($(this).val());
        setContinueEnabled(false);
    });

    // =======================================================
    // BVN VERIFICATION
    // =======================================================
    $("#verify_bvn_btn").on("click", async function () {
        const bvn = $("#bvn_input").val();
        const name = $("#identity_name_input").val();
        const dob = $("#identity_dob_input").val();
        const phone = $("#identity_phone_input").val();

        if (!bvn) {
            showToast("Please enter your BVN.", "warning");
            return;
        }

        if (!/^\d{11}$/.test(bvn)) {
            showToast("Please enter a valid 11-digit BVN number.", "error");
            return;
        }

        // Show verifying state
        $("#verify_bvn_btn").prop("disabled", true).text("Verifying...");

        try {
            const res = await fetch("{{ route('user.verify.identity') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    type: "bvn",
                    bvn: bvn,
                    name: name,
                    dateOfBirth: dob,
                    mobileNo: phone
                })
            });

            const data = await res.json();
            console.log("BVN verification response:", data);

            if (data.success) {
                showToast("✅ BVN Verified Successfully", "success");

                // Disable BVN fields after success
                $("#bvn_input, #identity_name_input, #identity_dob_input, #identity_phone_input")
                    .prop("disabled", true);
                $("#verify_bvn_btn").prop("disabled", true).text("Verified ✓");

                // Enable continue button
                setContinueEnabled(true);
            } else {
                const msg = data.message || "BVN verification failed.";
                showToast(msg, "error");
                $("#verify_bvn_btn").prop("disabled", false).text("Verify");
            }
        } catch (error) {
            console.error("BVN verification error:", error);
            showToast("An error occurred during BVN verification.", "error");
            $("#verify_bvn_btn").prop("disabled", false).text("Verify");
        }
    });

    // =======================================================
    // NIN VERIFICATION
    // =======================================================
    $("#verify_nin_btn").on("click", async function () {
        const nin = $("#nin_input").val();
        if (!nin) return showToast("Please enter your NIN.", "warning");

        $(this).prop("disabled", true).text("Verifying...");

        try {
            const res = await fetch("{{ route('user.verify.identity') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ type: "nin", nin })
            });

            const data = await res.json();
            console.log("NIN verification response:", data);

            if (data.success) {
                showToast("✅ NIN Verified: " + data.data.fullName, "success");

                $("#identity_name").val(data.data.fullName);
                $("#identity_dob").val(data.data.dateOfBirth);
                $("#identity_phone").val(data.data.phoneNumber);

                // Disable fields
                $("#nin_input").prop("disabled", true);
                $("#verify_nin_btn").prop("disabled", true).text("Verified ✓");

                // Enable continue
                setContinueEnabled(true);
            } else {
                showToast(data.message || "Verification failed.", "error");
                $("#verify_nin_btn").prop("disabled", false).text("Verify");
            }
        } catch (error) {
            console.error("NIN verification error:", error);
            showToast("An unexpected error occurred while verifying your NIN.", "error");
            $("#verify_nin_btn").prop("disabled", false).text("Verify");
        }
    });
});

</script>
@endpush
