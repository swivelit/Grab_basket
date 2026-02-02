<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$guards
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // If already authenticated, redirect to appropriate dashboard
                return match($guard) {
                    'delivery_partner' => redirect()->route('delivery-partner.dashboard'),
                    'warehouse' => redirect()->route('warehouse.dashboard'),
                    'hotel_owner' => redirect()->route('hotel-owner.dashboard'),
                    default => redirect('/'),
                };
            }
        }

        return $next($request);
    }
}
