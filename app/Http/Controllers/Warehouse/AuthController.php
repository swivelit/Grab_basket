<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Show warehouse login form
     */
    public function showLoginForm()
    {
        // Redirect if already logged in
        if (Auth::guard('warehouse')->check()) {
            return redirect()->route('warehouse.dashboard');
        }

        return view('warehouse.auth.login');
    }

    /**
     * Handle warehouse login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->only('email'));
        }

        // Rate limiting
        $key = 'warehouse.login.' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds."
            ]);
        }

        $credentials = $request->only('email', 'password');
        $credentials['is_active'] = true; // Only allow active users

        if (Auth::guard('warehouse')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Clear rate limiter
            RateLimiter::clear($key);
            
            // Update login tracking
            $user = Auth::guard('warehouse')->user();
            $user->recordLogin($request->ip());
            
            // Log successful login
            Log::info('Warehouse user logged in', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return redirect()->intended(route('warehouse.dashboard'));
        }

        // Record failed attempt
        RateLimiter::hit($key);
        
        // Log failed login attempt
        Log::warning('Failed warehouse login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return back()->withErrors([
            'email' => 'These credentials do not match our records or your account is inactive.',
        ])->withInput($request->only('email'));
    }

    /**
     * Handle warehouse logout
     */
    public function logout(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if ($user) {
            Log::info('Warehouse user logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);
        }

        Auth::guard('warehouse')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('warehouse.login')->with('success', 'You have been logged out successfully.');
    }

    /**
     * Show warehouse user profile
     */
    public function profile()
    {
        $user = Auth::guard('warehouse')->user();
        $activitySummary = $user->getActivitySummary();
        
        return view('warehouse.auth.profile', compact('user', 'activitySummary'));
    }

    /**
     * Update warehouse user profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $updateData = [
                'name' => $request->name,
                'phone' => $request->phone,
                'updated_by' => $user->name,
            ];

            // Handle password change
            if ($request->filled('new_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return back()->withErrors(['current_password' => 'Current password is incorrect.']);
                }
                
                $updateData['password'] = Hash::make($request->new_password);
                
                Log::info('Warehouse user changed password', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip()
                ]);
            }

            $user->update($updateData);

            return back()->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            Log::error('Warehouse profile update error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            
            return back()->with('error', 'Failed to update profile. Please try again.');
        }
    }

    /**
     * Show warehouse user management (managers only)
     */
    public function userManagement(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_users')) {
            abort(403, 'Access denied. Manager role required.');
        }

        $query = WarehouseUser::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->byRole($request->role);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('warehouse.auth.user-management', compact('users'));
    }

    /**
     * Create new warehouse user (managers only)
     */
    public function createUser(Request $request)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_users')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:warehouse_users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|unique:warehouse_users,employee_id',
            'role' => 'required|in:staff,supervisor,manager',
            'assigned_areas' => 'nullable|array',
            'can_add_stock' => 'boolean',
            'can_adjust_stock' => 'boolean',
            'can_manage_locations' => 'boolean',
            'can_view_reports' => 'boolean',
            'can_manage_quick_delivery' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            $newUser = WarehouseUser::createWarehouseUser($request->all());
            
            Log::info('New warehouse user created', [
                'created_by' => $user->id,
                'new_user_id' => $newUser->id,
                'new_user_email' => $newUser->email,
                'role' => $newUser->role
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully!',
                'user' => $newUser
            ]);

        } catch (\Exception $e) {
            Log::error('Warehouse user creation error', [
                'created_by' => $user->id,
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to create user. Please try again.'
            ]);
        }
    }

    /**
     * Update warehouse user (managers only)
     */
    public function updateUser(Request $request, $id)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_users')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $targetUser = WarehouseUser::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:warehouse_users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|unique:warehouse_users,employee_id,' . $id,
            'role' => 'required|in:staff,supervisor,manager',
            'assigned_areas' => 'nullable|array',
            'can_add_stock' => 'boolean',
            'can_adjust_stock' => 'boolean',
            'can_manage_locations' => 'boolean',
            'can_view_reports' => 'boolean',
            'can_manage_quick_delivery' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            $targetUser->update($request->all());
            
            Log::info('Warehouse user updated', [
                'updated_by' => $user->id,
                'target_user_id' => $targetUser->id,
                'changes' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Warehouse user update error', [
                'updated_by' => $user->id,
                'target_user_id' => $targetUser->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to update user. Please try again.'
            ]);
        }
    }

    /**
     * Toggle user active status (managers only)
     */
    public function toggleUserStatus(Request $request, $id)
    {
        $user = Auth::guard('warehouse')->user();
        
        if (!$user->hasPermission('manage_users')) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        try {
            $targetUser = WarehouseUser::findOrFail($id);
            
            // Prevent self-deactivation
            if ($targetUser->id === $user->id) {
                return response()->json([
                    'success' => false, 
                    'message' => 'You cannot deactivate your own account.'
                ]);
            }

            if ($targetUser->is_active) {
                $targetUser->deactivate();
                $message = 'User deactivated successfully.';
            } else {
                $targetUser->activate();
                $message = 'User activated successfully.';
            }

            Log::info('Warehouse user status changed', [
                'changed_by' => $user->id,
                'target_user_id' => $targetUser->id,
                'new_status' => $targetUser->is_active ? 'active' : 'inactive'
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $targetUser->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Warehouse user status toggle error', [
                'changed_by' => $user->id,
                'target_user_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to change user status.'
            ]);
        }
    }
}