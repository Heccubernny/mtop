<div class="sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-menu-inner-wrapper">
            <div class="sidebar-logo">
                <a class="sidebar-main-logo" href="{{ setRoute('index') }}">
                    <img data-white_img="{{ get_logo($basic_settings, 'dark') }}"
                        data-dark_img="{{ get_logo($basic_settings) }}" src="{{ get_logo($basic_settings) }}"
                        alt="logo">
                </a>
                <button class="sidebar-menu-bar">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            </div>
            <div class="sidebar-menu-wrapper">
                <ul class="sidebar-menu">
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.dashboard') }}">
                            <i class="menu-icon fas fa-th-large"></i>
                            <span class="menu-title">{{ __('Dashboard') }}</span>
                        </a>
                    </li>
                    @if (module_access('receive-money', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.receive.money.index') }}">
                                <i class="menu-icon fas fa-receipt"></i>
                                <span class="menu-title">{{ __('Receive Money') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (isset($referral_info) && $referral_info->status == true)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.refer.level.index') }}">
                                <i class="menu-icon fas fa-level-up-alt"></i>
                                <span class="menu-title">{{ __('Referral Status') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (module_access('money-exchange', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.money.exchange.index') }}">
                                <i class="menu-icon fas fa-exchange-alt"></i>
                                <span class="menu-title">{{ __('Money Exchange') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (module_access('send-money', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.send.money.index') }}">
                                <i class="menu-icon fas fa-paper-plane"></i>
                                <span class="menu-title">{{ __('Send Money') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (module_access('pay-link', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ route('user.payment-link.index') }}">
                                <i class="menu-icon fas fa-link"></i>
                                <span class="menu-title">{{ __('Payment Link') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (module_access('request-money', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.request.money.index') }}">
                                <i class="menu-icon fas fa-hand-holding-usd"></i>
                                <span class="menu-title">{{ __('request Money') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (module_access('remittance-money', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.remittance.index') }}">
                                <i class="menu-icon fas fa-coins"></i>
                                <span class="menu-title">{{ __('Remittance') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (module_access('add-money', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.add.money.index') }}">
                                <i class="menu-icon fas fa-plus-circle"></i>
                                <span class="menu-title">{{ __('Add Money') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (module_access('withdraw-money', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.money.out.index') }}">
                                <i class="menu-icon fas fa-arrow-alt-circle-right"></i>
                                <span class="menu-title">{{ __('withdraw') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (module_access('make-payment', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.make.payment.index') }}">
                                <i class="menu-icon fas fa-arrow-alt-circle-left"></i>
                                <span class="menu-title">{{ __('Make Payment') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (module_access('money-out', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.agent.money.out.index') }}">
                                <i class="menu-icon fas fa-arrow-alt-circle-left"></i>
                                <span class="menu-title">{{ __('Money Out') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (module_access('virtual-card', $module)->status)
                        @if (virtual_card_system('flutterwave'))
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.virtual.card.index') }}">
                                    <i class="menu-icon fas fa-credit-card"></i>
                                    <span class="menu-title">{{ __('Virtual Card') }}</span>
                                </a>
                            </li>
                        @elseif(virtual_card_system('sudo'))
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.sudo.virtual.card.index') }}">
                                    <i class="menu-icon fas fa-credit-card"></i>
                                    <span class="menu-title">{{ __('Virtual Card') }}</span>
                                </a>
                            </li>
                        @elseif(virtual_card_system('stripe'))
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.stripe.virtual.card.index') }}">
                                    <i class="menu-icon fas fa-credit-card"></i>
                                    <span class="menu-title">{{ __('Virtual Card') }}</span>
                                </a>
                            </li>
                        @elseif(virtual_card_system('strowallet'))
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.strowallet.virtual.card.index') }}">
                                    <i class="menu-icon fas fa-credit-card"></i>
                                    <span class="menu-title">{{ __('Virtual Card') }}</span>
                                </a>
                            </li>
                        @endif
                    @endif
                    @if (module_access('gift-cards', $module)->status)
                        <li class="sidebar-menu-item">
                            <a href="{{ setRoute('user.gift.card.index') }}">
                                <i class="menu-icon fas fa-gift"></i>
                                <span class="menu-title">{{ __('Gift Card') }}</span>
                            </a>
                        </li>
                    @endif
                    @php
                        $provider = active_billpay_provider();
                    @endphp

                    {{-- @if (module_access('bill-pay', $module)->status)
                        @php
                            $provider = active_billpay_provider();
                        @endphp
                        @if ($provider == 'reloadly')
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.bill.pay.index') }}">
                                    <i class="menu-icon fas fa-shopping-bag"></i>
                                    <span class="menu-title">{{ __('Bill Pay') }}</span>
                                </a>
                            </li>
                        @elseif ($provider == 'clubkonnect')
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.ck.home') }}">
                                    <i class="menu-icon fas fa-shopping-bag"></i>
                                    <span class="menu-title">{{ __('Bill Pay') }}</span>
                                </a>
                            </li>
                        @endif
                    @endif --}}

                    {{-- @if (module_access('cable-tv', $module)->status)
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute(route_name: 'user.cabletv.index') }}">
                            <i class="menu-icon fas fa-shopping-bag"></i>
                            <span class="menu-title">{{ __("Cable TV") }}</span>
                        </a>
                    </li>
                    @endif
                     @if (module_access('data', $module)->status)
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute(route_name: 'user.data.index') }}">
                            <i class="menu-icon fas fa-shopping-bag"></i>
                            <span class="menu-title">{{ __("Data Subscription") }}</span>
                        </a>
                    </li>
                    @endif --}}
                    @if ($provider === 'clubkonnect')
                        @if (module_access('cable-tv', $module)->status)
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute(route_name: 'user.cable.index') }}">
                                    <i class="menu-icon fas fa-shopping-bag"></i>
                                    <span class="menu-title">{{ __('Cable TV') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (module_access('data', $module)->status)
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute(route_name: 'user.data.index') }}">
                                    <i class="menu-icon fas fa-shopping-bag"></i>
                                    <span class="menu-title">{{ __('Data Subscription') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (module_access('airtime', $module)->status)
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute(route_name: 'user.airtime.index') }}">
                                    <i class="menu-icon fas fa-shopping-bag"></i>
                                    <span class="menu-title">{{ __('Airtime') }}</span>
                                </a>
                            </li>
                        @endif
                    @endif
                    @if ($provider === 'reloadly')
                        @if (module_access('bill-pay', $module)->status)
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.bill.pay.index') }}">
                                    <i class="menu-icon fas fa-shopping-bag"></i>
                                    <span class="menu-title">{{ __('Bill Pay') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (module_access('mobile-top-up', $module)->status)
                            <li class="sidebar-menu-item">
                                <a href="{{ setRoute('user.mobile.topup.index') }}">
                                    <i class="menu-icon fas fa-mobile"></i>
                                    <span class="menu-title">{{ __('Mobile Topup') }}</span>
                                </a>
                            </li>
                        @endif
                    @endif
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.transactions.index') }}">
                            <i class="menu-icon fas fa-arrows-alt-h"></i>
                            <span class="menu-title">{{ __('Transactions') }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.receipient.index') }}">
                            <i class="menu-icon fas fa-user-check"></i>
                            <span class="menu-title">{{ __('Saved Recipients') }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.security.google.2fa') }}">
                            <i class="menu-icon fas fa-qrcode"></i>
                            <span class="menu-title">{{ __('2FA Security') }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a class="logout-btn" href="javascript:void(0)">
                            <i class="menu-icon fas fa-sign-out-alt"></i>
                            <span class="menu-title">{{ __('Logout') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="sidebar-doc-box bg_img" data-background="{{ asset('frontend/') }}/images/element/support.jpg">
            <div class="sidebar-doc-icon">
                <i class="las la-question-circle"></i>
            </div>
            <div class="sidebar-doc-content">
                <h4 class="title">{{ __('help Center') }}?</h4>
                <p>{{ __('How can we help you?') }}</p>
                <div class="sidebar-doc-btn">
                    <a class="btn--base w-100"
                        href="{{ setRoute('user.support.ticket.index') }}">{{ __('Get Support') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@push('script')
    <script>
        $(".logout-btn").click(function() {
            var actionRoute = "{{ setRoute('user.logout') }}";
            var target = 1;
            var sureText = '{{ __('Are you sure to') }}';
            var message = `${sureText} <strong>{{ __('Logout') }}</strong>?`;
            var logout = `{{ __('Logout') }}`;
            openAlertModal(actionRoute, target, message, logout, "POST");
        });
    </script>
@endpush
