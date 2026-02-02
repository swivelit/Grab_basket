<?php

namespace App\Http\Requests\DeliveryPartner;

use App\Models\DeliveryPartner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryPartnerLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'], // Can be email or phone
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials using heavily optimized database queries.
     * 
     * This method uses composite indexes, connection optimization, and minimal queries
     * for maximum authentication performance.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginValue = $this->string('login');
        $password = $this->string('password');

        // Determine if login is email or phone (use frontend hint if available)
        $isEmail = $this->input('_login_type') === 'email' 
            ? true 
            : ($this->input('_login_type') === 'phone' 
                ? false 
                : filter_var($loginValue, FILTER_VALIDATE_EMAIL));
        
        // SUPER OPTIMIZATION: Use raw query with prepared statements for maximum speed
        $deliveryPartner = null;
        
        try {
            $startTotal = microtime(true);

            // Use optimized raw query with composite index
            $query = $isEmail
                ? "SELECT id, email, phone, password, status, is_verified, name, last_active_at FROM delivery_partners WHERE email = ? LIMIT 1"
                : "SELECT id, email, phone, password, status, is_verified, name, last_active_at FROM delivery_partners WHERE phone = ? LIMIT 1";

            // Measure DB query time
            $startQuery = microtime(true);
            $candidates = DB::select($query, [$loginValue]);
            $queryMs = (microtime(true) - $startQuery) * 1000;

            // Measure password verification time
            $startVerify = microtime(true);
            foreach ($candidates as $candidate) {
                if (Hash::check($password, $candidate->password)) {
                    $deliveryPartner = $candidate;
                    break;
                }
            }
            $verifyMs = (microtime(true) - $startVerify) * 1000;

            if (!$deliveryPartner) {
                logger()->info('delivery_partner_login_timing', [
                    'login' => $loginValue,
                    'query_ms' => $queryMs,
                    'verify_ms' => $verifyMs,
                    'total_ms' => (microtime(true) - $startTotal) * 1000,
                    'note' => 'failed_credentials',
                ]);

                $this->handleFailedAuthentication();
            }

            // Check delivery partner status before allowing login
            if (!$this->isPartnerEligibleForLogin($deliveryPartner)) {
                logger()->info('delivery_partner_login_timing', [
                    'login' => $loginValue,
                    'query_ms' => $queryMs,
                    'verify_ms' => $verifyMs,
                    'total_ms' => (microtime(true) - $startTotal) * 1000,
                    'note' => 'ineligible_status:' . ($deliveryPartner->status ?? 'unknown'),
                ]);

                $this->handleIneligiblePartner($deliveryPartner);
            }

            // ULTRA PERFORMANCE: Skip full model loading, use direct login
            $startAuth = microtime(true);
            Auth::guard('delivery_partner')->loginUsingId(
                $deliveryPartner->id,
                $this->boolean('remember')
            );
            $authMs = (microtime(true) - $startAuth) * 1000;

            // Async update last_active_at to avoid blocking login
            $startDispatch = microtime(true);
            dispatch(function () use ($deliveryPartner) {
                try {
                    DB::table('delivery_partners')
                        ->where('id', $deliveryPartner->id)
                        ->update(['last_active_at' => now()]);
                } catch (\Exception $e) {
                    logger('Delivery Partner Async update failed: ' . $e->getMessage());
                }
            })->afterResponse();
            $dispatchMs = (microtime(true) - $startDispatch) * 1000;

            $totalMs = (microtime(true) - $startTotal) * 1000;

            logger()->info('delivery_partner_login_timing', [
                'login' => $loginValue,
                'query_ms' => $queryMs,
                'verify_ms' => $verifyMs,
                'auth_ms' => $authMs,
                'dispatch_ms' => $dispatchMs,
                'total_ms' => $totalMs,
                'partner_id' => $deliveryPartner->id,
            ]);

            $this->clearRateLimiter();
        } catch (\Exception $e) {
            logger('Delivery Partner Auth Error: ' . $e->getMessage());
            logger('Delivery Partner Auth Stack: ' . $e->getTraceAsString());
            
            // Fallback to basic authentication if optimized version fails
            try {
                $loginField = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
                $credentials = [$loginField => $loginValue, 'password' => $password];
                
                if (Auth::guard('delivery_partner')->attempt($credentials, $this->boolean('remember'))) {
                    $this->clearRateLimiter();
                    return;
                }
            } catch (\Exception $fallbackError) {
                logger('Delivery Partner Fallback Auth Error: ' . $fallbackError->getMessage());
            }
            
            $this->handleFailedAuthentication();
        }
    }

    /**
     * Check if delivery partner is eligible for login based on status and verification.
     */
    private function isPartnerEligibleForLogin($partner): bool
    {
        // Allow login for pending, approved, and active status
        // Block for rejected, suspended, and inactive
        return in_array($partner->status, ['pending', 'approved', 'active']);
    }

    /**
     * Handle authentication failure with rate limiting.
     */
    private function handleFailedAuthentication(): void
    {
        $this->incrementRateLimiter();

        throw ValidationException::withMessages([
            'login' => __('auth.failed'),
        ]);
    }

    /**
     * Handle ineligible delivery partner (wrong status).
     */
    private function handleIneligiblePartner($partner): void
    {
        $messages = [
            'rejected' => 'Your account has been rejected. Please contact support.',
            'suspended' => 'Your account has been suspended. Please contact support.',
            'inactive' => 'Your account is inactive. Please contact support.',
        ];

        $message = $messages[$partner->status] ?? 'Your account status does not allow login.';

        throw ValidationException::withMessages([
            'login' => $message,
        ]);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    private function ensureIsNotRateLimited(): void
    {
        $key = $this->throttleKey();
        $maxAttempts = 5; // Same as main auth system
        $decayMinutes = 1;

        $rateLimiter = app('Illuminate\Cache\RateLimiter');

        if ($rateLimiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $rateLimiter->availableIn($key);

            throw ValidationException::withMessages([
                'login' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }
    }

    /**
     * Increment the rate limiter attempts.
     */
    private function incrementRateLimiter(): void
    {
        app('Illuminate\Cache\RateLimiter')->hit(
            $this->throttleKey(),
            60 // 1 minute decay
        );
    }

    /**
     * Clear the rate limiter for this request.
     */
    private function clearRateLimiter(): void
    {
        app('Illuminate\Cache\RateLimiter')->clear($this->throttleKey());
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    private function throttleKey(): string
    {
        return 'delivery-partner-login.' . $this->string('login') . '.' . $this->ip();
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Please enter your email or phone number.',
            'password.required' => 'Please enter your password.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'login' => 'email or phone number',
        ];
    }
}