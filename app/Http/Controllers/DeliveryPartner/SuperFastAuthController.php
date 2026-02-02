<?php

namespace App\Http\Controllers\DeliveryPartner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use App\Models\DeliveryPartner;

class SuperFastAuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('delivery-partner.auth.login');
    }

    /**
     * Super-fast login with minimal overhead
     */
    public function login(Request $request): RedirectResponse
    {
        $startTime = microtime(true);
        
        // Basic validation only
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim($request->input('login'));
        $password = $request->input('password');
        
        // Determine login field and normalize
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        $loginField = $isEmail ? 'email' : 'phone';
        
        if (!$isEmail) {
            $login = preg_replace('/\D/', '', $login); // Remove non-digits for phone
        } else {
            $login = strtolower($login); // Normalize email
        }

        // SUPER FAST: Direct database query with minimal fields
        $queryStartTime = microtime(true);
        $partner = DB::table('delivery_partners')
            ->select(['id', 'name', 'email', 'phone', 'password', 'status'])
            ->where($loginField, $login)
            ->first();
        $queryTime = (microtime(true) - $queryStartTime) * 1000;

        if (!$partner) {
            Log::warning("DeliveryPartner Login Failed - User Not Found", [
                'login_field' => $loginField,
                'query_time_ms' => round($queryTime, 2),
                'total_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'ip' => $request->ip()
            ]);
            return back()->withErrors(['login' => 'Invalid credentials.']);
        }

        // SUPER FAST: Password verification
        $passwordStartTime = microtime(true);
        if (!Hash::check($password, $partner->password)) {
            $passwordTime = (microtime(true) - $passwordStartTime) * 1000;
            Log::warning("DeliveryPartner Login Failed - Invalid Password", [
                'partner_id' => $partner->id,
                'query_time_ms' => round($queryTime, 2),
                'password_time_ms' => round($passwordTime, 2),
                'total_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'ip' => $request->ip()
            ]);
            return back()->withErrors(['login' => 'Invalid credentials.']);
        }
        $passwordTime = (microtime(true) - $passwordStartTime) * 1000;

        // SUPER FAST: Status check - accept repository's canonical statuses
        // (migration uses 'approved' rather than 'active')
        if (!in_array($partner->status, ['approved', 'pending'])) {
            $supportEmail = config('mail.support_email', 'support@grabbasket.com');
            $messages = [
                'rejected' => "Your application has been rejected. Please contact support at {$supportEmail} for more information.",
                'suspended' => "Your account has been suspended due to policy violations. Please contact support at {$supportEmail} to appeal this decision.",
                'inactive' => "Your account is inactive. Please contact support at {$supportEmail} to reactivate your account.",
            ];
            
            Log::warning("DeliveryPartner Login Blocked - Account Status Issue", [
                'partner_id' => $partner->id,
                'status' => $partner->status,
                'ip' => $request->ip()
            ]);
            
            return back()->withErrors(['login' => $messages[$partner->status] ?? 'Account status issue. Please contact support.']);
        }

        // SUPER FAST: Login using ID (fastest method)
        $authStartTime = microtime(true);
        
        // Debug session configuration
        Log::info('Session Configuration', [
            'driver' => config('session.driver'),
            'domain' => config('session.domain'),
            'secure' => config('session.secure'),
            'path' => config('session.path')
        ]);
        
        Auth::guard('delivery_partner')->loginUsingId($partner->id, $request->boolean('remember'));
            
        // Check if login was successful
        if (!Auth::guard('delivery_partner')->check()) {
            Log::error('Login failed - Session not persisted', [
                'partner_id' => $partner->id,
                'session_id' => $request->session()->getId(),
                'cookies' => $request->cookies->all()
            ]);
            
            // Try standard login as fallback
            if (Auth::guard('delivery_partner')->attempt([
                $loginField => $login,
                'password' => $password
            ], $request->boolean('remember'))) {
                Log::info('Fallback login successful');
            } else {
                Log::error('Both login methods failed');
                return back()->withErrors(['login' => 'Login failed - Please try again']);
            }
        }
        // double-check session persisted; if not, attempt the normal auth attempt flow as a fallback
        if (!Auth::guard('delivery_partner')->check()) {
            Log::error('DeliveryPartner loginUsingId did not persist session, attempting fallback Auth::attempt', [
                'partner_id' => $partner->id,
                'login_field' => $loginField,
                'login_value' => $login,
                'ip' => $request->ip()
            ]);

            // Try standard attempt (uses provider/model) as a fallback
            $credentials = [$loginField => $login, 'password' => $password];
            if (!Auth::guard('delivery_partner')->attempt($credentials, $request->boolean('remember'))) {
                // If fallback also fails, return error
                Log::warning('DeliveryPartner fallback Auth::attempt failed', ['partner_id' => $partner->id]);
                return back()->withErrors(['login' => 'Invalid credentials.']);
            }

            Log::info('DeliveryPartner fallback Auth::attempt succeeded', ['partner_id' => $partner->id]);
        }
        $authTime = (microtime(true) - $authStartTime) * 1000;

        // SUPER FAST: Session regeneration
        $sessionStartTime = microtime(true);
        $request->session()->regenerate();
        $sessionTime = (microtime(true) - $sessionStartTime) * 1000;

        $totalTime = (microtime(true) - $startTime) * 1000;

        // Log success
        Log::info("DeliveryPartner Login Success - SUPER FAST", [
            'partner_id' => $partner->id,
            'partner_name' => $partner->name,
            'login_field' => $loginField,
            'status' => $partner->status,
            'query_time_ms' => round($queryTime, 2),
            'password_time_ms' => round($passwordTime, 2),
            'auth_time_ms' => round($authTime, 2),
            'session_time_ms' => round($sessionTime, 2),
            'total_time_ms' => round($totalTime, 2),
            'ip' => $request->ip()
        ]);

        // VERIFY: Confirm session is actually persisted before redirect
        if (!Auth::guard('delivery_partner')->check()) {
            Log::error('CRITICAL: Session check failed after login', [
                'partner_id' => $partner->id,
                'session_id' => $request->session()->getId(),
                'session_data' => $request->session()->all()
            ]);
            return back()->withErrors(['login' => 'Login failed - session not persisted. Please try again.']);
        }

        // Handle status-specific redirects
        if ($partner->status === 'pending') {
            return redirect()
                ->route('delivery-partner.dashboard')
                ->with('warning', 'Your account is under review. You will be notified once approved.');
        }

        // EXPLICIT REDIRECT - Do not use intended() for delivery partners
        // This ensures we always redirect to the dashboard
        return redirect('/delivery-partner/dashboard')
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
            ->with('success', 'Logged out successfully.');
    }
}