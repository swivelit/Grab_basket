<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class OptimizeDeliveryPartnerAuth
{
    /**
     * Handle an incoming request with database and session optimizations
     * specifically for delivery partner authentication routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply optimizations to delivery partner auth routes
        if (!$request->is('delivery-partner/login*') && !$request->is('delivery-partner/register*')) {
            return $next($request);
        }

        // 1. Optimize database connection for auth queries
        $this->optimizeDatabaseConnection();
        
        // 2. Preload commonly accessed config values
        $this->preloadConfiguration();
        
        // 3. Optimize session settings for auth
        $this->optimizeSessionForAuth();

        $response = $next($request);

        // 4. Add performance headers for monitoring
        $response->headers->set('X-Auth-Optimized', 'delivery-partner');
        $response->headers->set('X-DB-Connection-Pool', 'enabled');

        return $response;
    }

    /**
     * Optimize database connection settings for authentication queries.
     */
    private function optimizeDatabaseConnection(): void
    {
        // Set connection to use persistent connections
        config(['database.connections.mysql.options' => [
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_TIMEOUT => 5, // 5 second timeout instead of default 30
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'",
        ]]);

        // Reduce query cache size for faster auth queries
        DB::statement("SET SESSION query_cache_type = ON");
        DB::statement("SET SESSION query_cache_size = 1048576"); // 1MB for auth queries
    }

    /**
     * Preload configuration values to avoid file I/O during auth.
     */
    private function preloadConfiguration(): void
    {
        Cache::remember('delivery_partner_auth_config', 300, function () {
            return [
                'password_hash_rounds' => config('hashing.bcrypt.rounds', 10),
                'session_lifetime' => config('session.lifetime', 120),
                'rate_limit_attempts' => 5,
                'rate_limit_decay' => 1,
            ];
        });
    }

    /**
     * Optimize session configuration specifically for authentication.
     */
    private function optimizeSessionForAuth(): void
    {
        // NOTE: Do NOT switch session driver to 'array' here. The 'array' driver
        // stores session data only for the current request which prevents the
        // authentication session from persisting across requests (breaking login).

        // Reduce session data size while keeping the configured driver
        config(['session.encrypt' => false]); // Skip encryption for auth sessions
        config(['session.same_site' => 'lax']); // Optimize cookie handling
    }
}