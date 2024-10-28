<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StoreReferralUrl
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if referral URL is not already set in session
        if (!$request->session()->has('referral_url')) {
            // Check HTTP referer and set session if conditions are met
            if (
                $request->server('HTTP_REFERER') &&
                !str_contains($request->server('HTTP_REFERER'), 'giftscenter') &&
                !str_contains($request->server('HTTP_REFERER'), 'gateway.mastercard') &&
                !str_contains($request->server('HTTP_REFERER'), 'montypay')
            ) {
                $request->session()->put('referral_url', $request->server('HTTP_REFERER'));
            }
        }

        return $next($request);
    }
}
