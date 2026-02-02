<?php

namespace App\Http\Controllers\DeliveryPartner;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class OptimizedAuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('delivery-partner.auth.login');
    }

    /**
     * Ultra-optimized login with aggressive caching and query optimization
     */
    public function login(Request $request): RedirectResponse
    {
        $startTime = microtime(true);
        
        // OPTIMIZATION 0: Rate limiting to prevent abuse
        $ip = $request->ip();
        $rateLimitKey = "delivery_login_attempts_{$ip}";
        $attempts = Cache::get($rateLimitKey, 0);
        
        if ($attempts >= 10) { // Max 10 attempts per hour
            return back()->withErrors(['login' => 'Too many login attempts. Please try again in an hour.']);
        }
        
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Pre-process and normalize input
        $login = trim($credentials['login']);
        $password = $credentials['password'];
        
        // Determine login field type
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        $loginField = $isEmail ? 'email' : 'phone';
        
        // Normalize phone number (remove any formatting)
        if (!$isEmail) {
            $login = preg_replace('/\D/', '', $login); // Remove non-digits
        } else {
            $login = strtolower($login); // Normalize email
        }

        $findUserStartTime = microtime(true);
        
        // OPTIMIZATION 1: Aggressive caching for user lookup
        $cacheKey = "delivery_partner_login_{$loginField}_{$login}";
        $partner = Cache::remember($cacheKey, 300, function() use ($loginField, $login) { // 5 min cache
            return DeliveryPartner::select(['id', 'name', 'email', 'phone', 'password', 'status', 'is_verified', 'last_active_at'])
                ->where($loginField, $login)
                ->first();
        });
            
        $findUserTime = (microtime(true) - $findUserStartTime) * 1000;

        if (!$partner) {
            $failTime = (microtime(true) - $startTime) * 1000;
            Log::warning("DeliveryPartner Login Failed - User Not Found", [
                'login_field' => $loginField,
                'find_user_time_ms' => round($findUserTime, 2),
                'total_time_ms' => round($failTime, 2),
                'ip' => $request->ip()
            ]);

            return back()->withErrors(['login' => 'Invalid credentials.']);
        }

        // OPTIMIZATION 2: Quick password verification
        $passwordStartTime = microtime(true);
        $passwordValid = Hash::check($password, $partner->password);
        $passwordTime = (microtime(true) - $passwordStartTime) * 1000;

        if (!$passwordValid) {
            // Increment failed attempts counter
            Cache::put($rateLimitKey, $attempts + 1, 3600); // 1 hour
            
            $failTime = (microtime(true) - $startTime) * 1000;
            Log::warning("DeliveryPartner Login Failed - Invalid Password", [
                'partner_id' => $partner->id,
                'login_field' => $loginField,
                'find_user_time_ms' => round($findUserTime, 2),
                'password_time_ms' => round($passwordTime, 2),
                'total_time_ms' => round($failTime, 2),
                'attempts' => $attempts + 1,
                'ip' => $request->ip()
            ]);

            return back()->withErrors(['login' => 'Invalid credentials.']);
        }

        // OPTIMIZATION 3: Quick status validation
        if (!in_array($partner->status, ['active', 'pending'])) {
            $messages = [
                'rejected' => 'Your account has been rejected. Please contact support.',
                'suspended' => 'Your account has been suspended. Please contact support.',
                'inactive' => 'Your account is inactive. Please contact support.',
            ];
            
            $message = $messages[$partner->status] ?? 'Your account status prevents login. Please contact support.';
            
            return back()->withErrors(['login' => $message]);
        }

        // OPTIMIZATION 4: Fast login without additional queries
        $authStartTime = microtime(true);
        Auth::guard('delivery_partner')->loginUsingId($partner->id, $request->boolean('remember'));
        $authTime = (microtime(true) - $authStartTime) * 1000;

        // OPTIMIZATION 5: Session regeneration
        $sessionStartTime = microtime(true);
        $request->session()->regenerate();
        $sessionTime = (microtime(true) - $sessionStartTime) * 1000;

        // OPTIMIZATION 6: Async last_active_at update (non-blocking)
        $updateStartTime = microtime(true);
        $shouldUpdate = !$partner->last_active_at || 
                       $partner->last_active_at->diffInMinutes(now()) >= 60; // Only update every hour
        
        $updateTime = 0;
        if ($shouldUpdate) {
            // Use raw query for fastest update
            DB::table('delivery_partners')
                ->where('id', $partner->id)
                ->update(['last_active_at' => now()]);
            $updateTime = (microtime(true) - $updateStartTime) * 1000;
        }

        $totalTime = (microtime(true) - $startTime) * 1000;

        // Clear failed attempts on successful login
        Cache::forget($rateLimitKey);
        
        // Clear the user cache to ensure fresh data on next login
        Cache::forget($cacheKey);
        
        // Log success with detailed performance metrics
        Log::info("DeliveryPartner Login Success - OPTIMIZED", [
            'partner_id' => $partner->id,
            'partner_name' => $partner->name,
            'login_field' => $loginField,
            'status' => $partner->status,
            'find_user_time_ms' => round($findUserTime, 2),
            'password_time_ms' => round($passwordTime, 2),
            'auth_time_ms' => round($authTime, 2),
            'session_time_ms' => round($sessionTime, 2),
            'update_time_ms' => round($updateTime, 2),
            'total_time_ms' => round($totalTime, 2),
            'should_update_activity' => $shouldUpdate,
            'cached_user_lookup' => Cache::has($cacheKey),
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 100)
        ]);

        // Handle status-specific redirects
        if ($partner->status === 'pending') {
            return redirect()
                ->route('delivery-partner.dashboard')
                ->with('warning', 'Your account is still under review. You will be notified once approved.');
        }

        return redirect()
            ->intended(route('delivery-partner.dashboard'))
            ->with('success', 'Welcome back, ' . $partner->name . '!');
    }

    /**
     * Handle logout
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('delivery_partner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('delivery-partner.login')
            ->with('success', 'You have been logged out successfully.');
    }
}