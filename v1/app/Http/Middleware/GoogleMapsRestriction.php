<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GoogleMapsRestriction
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $referer = $request->headers->get('referer');
        
        if (!app('google.maps')->validateDomain($referer)) {
            return response()->json(['error' => 'Unauthorized domain'], 403);
        }

        return $next($request);
    }
}