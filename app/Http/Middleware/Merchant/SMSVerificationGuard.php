<?php

namespace App\Http\Middleware\Merchant;

use App\Models\Admin\BasicSettings;
use Closure;
use Illuminate\Http\Request;

class SMSVerificationGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $basic_settings = BasicSettings::first();
        if ($basic_settings->merchant_sms_verification == true) {
            if ($user->sms_verified == false && $user->full_mobile != null) {
                return merchantSmsVerificationTemplate($user);
            }
        }

        return $next($request);
    }
}
