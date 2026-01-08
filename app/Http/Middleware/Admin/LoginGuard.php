<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        // $guards = config('auth.guards');

        // foreach ($guards as $guard => $values) {
        //     if (Auth::guard($guard)->check() == true) {
        //         if ($request->routeIs('admin.*')) {
        //             return redirect(route('admin.dashboard'));
        //         } elseif ($request->routeIs('user.*')) {
        //             return redirect(route('user.dashboard'));
        //         }
        //     }
        // }

        if ($request->routeIs('admin.*') && Auth::guard('admin')->check()) {
            return redirect(route('admin.dashboard'));
        }

        // if ($request->routeIs('agent.*') && Auth::guard('agent')->check()) {
        //     return redirect(route('agent.dashboard'));
        // }

        // if ($request->routeIs('merchant.*') && Auth::guard('merchant')->check()) {
        //     return redirect(route('merchant.dashboard'));
        // }

        if ($request->routeIs('user.*') && Auth::guard('web')->check()) {
            return redirect(route('user.dashboard'));
        }
        return $next($request);
    }
}
