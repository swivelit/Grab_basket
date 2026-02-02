<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class OptimizeLoginSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip heavy session operations for authenticated users
        if (Auth::check() && $request->route() && $request->route()->getName() === 'login') {
            $user = Auth::user();
            $role = $user->role ?? 'buyer';
            
            // Direct redirect for already authenticated users
            $redirectRoute = $role === 'seller' ? 'seller.dashboard' : 'home';
            return redirect()->route($redirectRoute);
        }

        // Cache user session data for faster subsequent requests
        if (Auth::check()) {
            $userId = Auth::id();
            $cacheKey = "user_session_{$userId}";
            
            if (!Cache::has($cacheKey)) {
                $user = Auth::user();
                Cache::put($cacheKey, [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ], now()->addMinutes(30));
            }
        }

        return $next($request);
    }
}