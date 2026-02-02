<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonitorAuthPerformance
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        if ($request->is('delivery-partner/login') && $request->isMethod('post')) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('Auth Request Performance', [
                'duration_ms' => $duration,
                'session_id' => $request->session()->getId(),
                'memory_usage' => memory_get_usage(true),
                'status_code' => $response->getStatusCode()
            ]);
        }
        
        return $response;
    }
}