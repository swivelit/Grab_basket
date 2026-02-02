<?php

namespace App\Http\Controllers\DeliveryPartner;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryPartner\DeliveryPartnerLoginRequest;
use App\Models\DeliveryPartner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Mail\DeliveryPartnerRegistered;
use App\Mail\DeliveryPartnerWelcome;
use App\Notifications\DeliveryPartnerNotification;

class AuthController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegisterForm(): View
    {
        return view('delivery-partner.auth.register');
    }

    /**
     * Show the quick registration form.
     */
    public function showQuickRegisterForm(): View
    {
        return view('delivery-partner.auth.quick-register');
    }

    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('delivery-partner.auth.login');
    }

    /**
     * Handle registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        // OPTIMIZED VALIDATION - Split into required and optional for faster processing
        $requiredRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|size:10',
            'password' => 'required|string|min:6|confirmed',
            'vehicle_type' => 'required|in:bike,scooter,bicycle,car,auto',
            'vehicle_number' => 'required|string|max:20',
            'license_number' => 'required|string|max:50',
            'aadhar_number' => 'required|string|size:12',
            'terms_accepted' => 'required|accepted',
        ];

        $optionalRules = [
            'alternate_phone' => 'nullable|string|size:10',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|size:6',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'license_expiry' => 'nullable|date|after:today',
            'vehicle_rc_number' => 'nullable|string|max:50',
            'insurance_number' => 'nullable|string|max:50',
            'insurance_expiry' => 'nullable|date|after:today',

            // Documents - reduced size limit for faster upload
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'license_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'vehicle_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'aadhar_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'pan_number' => 'nullable|string|size:10',
            'pan_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',

            // Bank Details
            'bank_account_holder' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:20',
            'bank_ifsc_code' => 'nullable|string|max:11',
            'bank_name' => 'nullable|string|max:255',

            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|size:10',
            'emergency_contact_relation' => 'nullable|string|max:100',
        ];

        $validator = Validator::make($request->all(), array_merge($requiredRules, $optionalRules));

        // Quick unique check for performance
        if ($request->filled('email') && DeliveryPartner::where('email', $request->email)->exists()) {
            return back()->withErrors(['email' => 'Email already registered'])->withInput();
        }

        if ($request->filled('phone') && DeliveryPartner::where('phone', $request->phone)->exists()) {
            return back()->withErrors(['phone' => 'Phone number already registered'])->withInput();
        }

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Prepare data for creation
            $data = $validator->validated();
            $data['password'] = Hash::make($data['password']);
            $data['status'] = 'pending';
            $data['is_verified'] = false;
            $data['is_online'] = false;
            $data['is_available'] = false;

            // Remove terms_accepted as it's not in the model
            unset($data['terms_accepted']);

            // Handle file uploads - OPTIMIZED FOR SPEED
            $uploadedFiles = [];
            $fileFields = [
                'profile_photo',
                'license_photo',
                'vehicle_photo',
                'aadhar_photo',
                'pan_photo'
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = 'delivery-partner/' . $field . '/' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                    // Store ONLY in public disk for immediate registration
                    $path = $file->storeAs('', $filename, 'public');
                    $data[$field] = $filename;
                    $uploadedFiles[] = $filename;

                    // Skip R2 upload during registration for speed - handle in background job later
                }
            }

            // Create delivery partner
            $deliveryPartner = DeliveryPartner::create($data);

            // Send email notification to admin about new registration
            try {
                $adminEmail = config('mail.support_email');
                Mail::to($adminEmail)->send(new DeliveryPartnerRegistered($deliveryPartner));
                Log::info('Admin notification email sent for new delivery partner', ['partner_id' => $deliveryPartner->id]);
            } catch (\Exception $e) {
                Log::error('Failed to send admin notification email', [
                    'partner_id' => $deliveryPartner->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Send welcome email to the new delivery partner
            try {
                Mail::to($deliveryPartner->email)->send(new DeliveryPartnerWelcome($deliveryPartner));
                Log::info('Welcome email sent to delivery partner', ['partner_id' => $deliveryPartner->id]);
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email to delivery partner', [
                    'partner_id' => $deliveryPartner->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Send in-app notification
            try {
                $deliveryPartner->notify(new DeliveryPartnerNotification(
                    'Welcome to ' . config('app.name') . '!',
                    'Your registration has been received and is pending admin approval. You will be notified once your account is approved.',
                    'info',
                    route('delivery-partner.dashboard'),
                    'Go to Dashboard',
                    ['send_email' => false] // Already sent welcome email above
                ));
            } catch (\Exception $e) {
                Log::error('Failed to send in-app notification', [
                    'partner_id' => $deliveryPartner->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Create wallet for the new partner
            try {
                \App\Models\DeliveryPartnerWallet::create([
                    'delivery_partner_id' => $deliveryPartner->id,
                    'balance' => 0.00,
                    'total_earnings' => 0.00,
                    'total_withdrawals' => 0.00,
                ]);
            } catch (\Exception $walletError) {
                logger('Failed to create wallet for delivery partner: ' . $walletError->getMessage());
            }

            return redirect()
                ->route('delivery-partner.login')
                ->with('success', 'Registration successful! Your application is under review. You will receive an email once approved.');

        } catch (\Exception $e) {
            // Clean up uploaded files if creation fails
            foreach ($uploadedFiles as $filename) {
                try {
                    Storage::disk('public')->delete($filename);
                    Storage::disk('r2')->delete($filename);
                } catch (\Exception $deleteError) {
                    // Ignore cleanup errors
                }
            }

            logger('Delivery Partner Registration Error: ' . $e->getMessage());

            return back()
                ->with('error', 'Registration failed. Please try again.')
                ->withInput();
        }
    }

    /**
     * Handle quick registration request - OPTIMIZED FOR SPEED
     */
    public function quickRegister(Request $request): RedirectResponse
    {
        // Minimal validation for quick registration
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:delivery_partners,email',
            'phone' => 'required|string|size:10|unique:delivery_partners,phone',
            'password' => 'required|string|min:6|confirmed',
            'vehicle_type' => 'required|in:bike,scooter,bicycle,car,auto',
            'city' => 'required|string|max:100',
            'terms_accepted' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Create delivery partner with minimal required data
            $data = $validator->validated();
            unset($data['terms_accepted']);

            $deliveryPartner = DeliveryPartner::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'vehicle_type' => $data['vehicle_type'],
                'city' => $data['city'],
                'address' => 'To be provided', // Placeholder for quick registration
                'state' => 'To be provided', // Required field
                'pincode' => '000000', // Placeholder pincode
                'date_of_birth' => '2000-01-01', // Placeholder DOB
                'gender' => 'male', // Default gender
                'vehicle_number' => 'To be provided', // Placeholder
                'license_number' => 'To be provided', // Placeholder
                'license_expiry' => '2025-12-31', // Placeholder
                'aadhar_number' => 'To be provided', // Placeholder
                'status' => 'pending', // Use valid enum value
                'is_verified' => false,
                'is_online' => false,
                'is_available' => false,
                'registration_type' => 'quick', // Track registration type
            ]);

            // Create wallet immediately
            \App\Models\DeliveryPartnerWallet::create([
                'delivery_partner_id' => $deliveryPartner->id,
                'balance' => 0.00,
                'total_earned' => 0.00,
                'total_withdrawn' => 0.00,
                'pending_amount' => 0.00,
                'total_deliveries' => 0,
                'successful_deliveries' => 0,
                'average_rating' => 0.00,
                'is_active' => true,
            ]);

            // Send email notification to admin about new registration
            try {
                $adminEmail = config('mail.support_email');
                Mail::to($adminEmail)->send(new DeliveryPartnerRegistered($deliveryPartner));
                Log::info('Admin notification email sent for quick registered delivery partner', ['partner_id' => $deliveryPartner->id]);
            } catch (\Exception $e) {
                Log::error('Failed to send admin notification email for quick registration', [
                    'partner_id' => $deliveryPartner->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Send welcome email to the new delivery partner
            try {
                Mail::to($deliveryPartner->email)->send(new DeliveryPartnerWelcome($deliveryPartner));
                Log::info('Welcome email sent to quick registered delivery partner', ['partner_id' => $deliveryPartner->id]);
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email to quick registered delivery partner', [
                    'partner_id' => $deliveryPartner->id,
                    'error' => $e->getMessage()
                ]);
            }

            return redirect()
                ->route('delivery-partner.login')
                ->with('success', 'Quick registration successful! Login to complete your profile and upload documents.');

        } catch (\Exception $e) {
            logger('Quick Registration Error: ' . $e->getMessage());
            logger('Quick Registration Stack Trace: ' . $e->getTraceAsString());
            logger('Quick Registration Data: ' . json_encode($request->all()));

            return back()
                ->with('error', 'Registration failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Check if phone number exists (AJAX)
     */
    public function checkPhone(Request $request)
    {
        $phone = $request->input('phone');
        $exists = DeliveryPartner::where('phone', $phone)->exists();

        return response()->json(['exists' => $exists]);
    }

    /**
     * Check if email exists (AJAX)
     */
    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        $exists = DeliveryPartner::where('email', $email)->exists();

        return response()->json(['exists' => $exists]);
    }

    /**
     * Handle login request with performance monitoring.
     */
    public function login(Request $request): RedirectResponse
    {
        $startTime = microtime(true);

        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Determine if login is email or phone
        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $loginData = [
            $loginField => $credentials['login'],
            'password' => $credentials['password']
        ];

        $authStartTime = microtime(true);
        // Use efficient authentication with minimal queries
        if (Auth::guard('delivery_partner')->attempt($loginData, $request->boolean('remember'))) {
            $authTime = (microtime(true) - $authStartTime) * 1000;

            $sessionStartTime = microtime(true);
            $request->session()->regenerate();
            $sessionTime = (microtime(true) - $sessionStartTime) * 1000;

            $userStartTime = microtime(true);
            $partner = Auth::guard('delivery_partner')->user();
            $userTime = (microtime(true) - $userStartTime) * 1000;

            // Update last active timestamp efficiently (only if more than 1 hour old)
            $updateStartTime = microtime(true);
            $updateTime = 0;
            try {
                if (!$partner->last_active_at || $partner->last_active_at->diffInHours(now()) >= 1) {
                    $partner->update(['last_active_at' => now()]);
                    $updateTime = (microtime(true) - $updateStartTime) * 1000;
                }
            } catch (\Exception $e) {
                // Silent fail - not critical
                $updateTime = (microtime(true) - $updateStartTime) * 1000;
            }

            // Check partner status
            if ($partner->status === 'pending') {
                return redirect()
                    ->route('delivery-partner.dashboard')
                    ->with('warning', 'Your account is still under review. You will be notified once approved.');
            } elseif ($partner->status === 'rejected') {
                Auth::guard('delivery_partner')->logout();
                return back()
                    ->withErrors(['login' => 'Your account has been rejected. Please contact support.']);
            } elseif ($partner->status === 'suspended') {
                Auth::guard('delivery_partner')->logout();
                return back()
                    ->withErrors(['login' => 'Your account has been suspended. Please contact support.']);
            } elseif ($partner->status === 'inactive') {
                Auth::guard('delivery_partner')->logout();
                return back()
                    ->withErrors(['login' => 'Your account is inactive. Please contact support.']);
            }

            $totalTime = (microtime(true) - $startTime) * 1000;

            // Log performance metrics
            Log::info("DeliveryPartner Login Success", [
                'partner_id' => $partner->id,
                'login_field' => $loginField,
                'auth_time_ms' => round($authTime, 2),
                'session_time_ms' => round($sessionTime, 2),
                'user_time_ms' => round($userTime, 2),
                'update_time_ms' => round($updateTime, 2),
                'total_time_ms' => round($totalTime, 2),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return redirect()
                ->intended(route('delivery-partner.dashboard'))
                ->with('success', 'Welcome back, ' . $partner->name . '!');
        }

        $failTime = (microtime(true) - $startTime) * 1000;
        Log::warning("DeliveryPartner Login Failed", [
            'login_field' => $loginField,
            'total_time_ms' => round($failTime, 2),
            'ip' => $request->ip()
        ]);

        return back()
            ->withErrors(['login' => 'Invalid credentials.'])
            ->onlyInput('login');
    }



    /**
     * Handle logout request.
     */
    public function logout(Request $request): RedirectResponse
    {
        $partner = Auth::guard('delivery_partner')->user();

        if ($partner) {
            // Mark as offline when logging out
            $partner->update([
                'is_online' => false,
                'is_available' => false
            ]);
        }

        Auth::guard('delivery_partner')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('delivery-partner.login')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Show profile page.
     */
    public function profile(): View
    {
        $partner = Auth::guard('delivery_partner')->user();
        return view('delivery-partner.auth.profile', compact('partner'));
    }

    /**
     * Update profile.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $partner = Auth::guard('delivery_partner')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:delivery_partners,email,' . $partner->id,
            'phone' => 'required|string|size:10|unique:delivery_partners,phone,' . $partner->id,
            'alternate_phone' => 'nullable|string|size:10',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|size:6',
            'vehicle_number' => 'required|string|max:20|unique:delivery_partners,vehicle_number,' . $partner->id,
            'license_expiry' => 'required|date|after:today',
            'insurance_expiry' => 'nullable|date|after:today',
            'bank_account_holder' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:20',
            'bank_ifsc_code' => 'nullable|string|max:11',
            'bank_name' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|size:10',
            'emergency_contact_relation' => 'nullable|string|max:100',
            'max_delivery_distance' => 'nullable|integer|min:1|max:50',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $validator->validated();

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                // Delete old photo if exists
                if ($partner->profile_photo) {
                    try {
                        Storage::disk('public')->delete($partner->profile_photo);
                        Storage::disk('r2')->delete($partner->profile_photo);
                    } catch (\Exception $e) {
                        // Ignore deletion errors
                    }
                }

                $file = $request->file('profile_photo');
                $filename = 'delivery-partner/profile_photo/' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                $file->storeAs('', $filename, 'public');
                $data['profile_photo'] = $filename;

                // Try to sync to R2
                try {
                    Storage::disk('r2')->put($filename, $file->get());
                } catch (\Exception $e) {
                    logger('R2 upload failed for delivery partner profile photo: ' . $e->getMessage());
                }
            }

            $partner->update($data);

            return back()->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            logger('Delivery Partner Profile Update Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update profile. Please try again.');
        }
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $partner = Auth::guard('delivery_partner')->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        if (!Hash::check($request->current_password, $partner->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $partner->update(['password' => Hash::make($request->new_password)]);

        return back()->with('success', 'Password changed successfully!');
    }

    /**
     * Toggle online/offline status.
     */
    public function toggleOnlineStatus(Request $request)
    {
        $partner = Auth::guard('delivery_partner')->user();

        if (!$partner->is_online && !$partner->canGoOnline()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot go online. Please ensure your account is approved and verified.'
            ]);
        }

        if ($partner->is_online) {
            $partner->goOffline();
            $message = 'You are now offline';
        } else {
            $partner->goOnline();
            $message = 'You are now online';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_online' => $partner->is_online,
            'is_available' => $partner->is_available
        ]);
    }

    /**
     * Toggle availability status.
     */
    public function toggleAvailability(Request $request)
    {
        $partner = Auth::guard('delivery_partner')->user();

        if (!$partner->is_online) {
            return response()->json([
                'success' => false,
                'message' => 'You must be online to change availability.'
            ]);
        }

        $partner->toggleAvailability();

        $message = $partner->is_available ? 'You are now available for deliveries' : 'You are now busy';

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_available' => $partner->is_available
        ]);
    }

    /**
     * Update location.
     */
    public function updateLocation(Request $request)
    {
        $partner = Auth::guard('delivery_partner')->user();

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid location data.'
            ]);
        }

        $success = $partner->updateLocation(
            $request->latitude,
            $request->longitude,
            $request->address
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Location updated successfully' : 'Failed to update location'
        ]);
    }

    /**
     * Notify admin about new registration.
     */
    private function notifyAdminNewRegistration(DeliveryPartner $partner): void
    {
        // Implementation for admin notification
        // This could be email, SMS, or in-app notification
    }
}