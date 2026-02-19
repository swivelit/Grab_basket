<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckDeliveryPartnerStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $partner = Auth::guard('delivery_partner')->user();

        if (!$partner) {
            return redirect()->route('delivery-partner.login')
                ->with('error', 'Please login to continue.');
        }

        // Check if partner is suspended
        if ($partner->status === 'suspended') {
            Auth::guard('delivery_partner')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('delivery-partner.login')
                ->withErrors(['login' => 'Your account has been suspended. Please contact support at ' . config('mail.support_email')]);
        }

        // Check if partner is rejected
        if ($partner->status === 'rejected') {
            Auth::guard('delivery_partner')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('delivery-partner.login')
                ->withErrors(['login' => 'Your application has been rejected. Please contact support for more information.']);
        }

        // Check if partner is inactive
        if ($partner->status === 'inactive') {
            Auth::guard('delivery_partner')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('delivery-partner.login')
                ->withErrors(['login' => 'Your account is inactive. Please contact support to reactivate.']);
        }

        // Allow pending and approved partners to continue
        return $next($request);
    }
}
