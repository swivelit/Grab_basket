<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            // Redirect based on the guard used
            $guard = $this->getGuard($request);
            
            return match($guard) {
                'delivery_partner' => route('delivery-partner.login'),
                'warehouse' => route('warehouse.login'),
                'hotel_owner' => route('hotel-owner.login'),
                default => route('login'),
            };
        }

        return null;
    }

    /**
     * Determine which guard is being used in the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    private function getGuard(Request $request): string
    {
        // Check the route name to determine the guard
        $routeName = $request->route()?->getName() ?? '';
        
        if (str_starts_with($routeName, 'delivery-partner.')) {
            return 'delivery_partner';
        } elseif (str_starts_with($routeName, 'warehouse.')) {
            return 'warehouse';
        } elseif (str_starts_with($routeName, 'hotel-owner.')) {
            return 'hotel_owner';
        }

        return 'web';
    }

    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);
        return $next($request);
    }
}
