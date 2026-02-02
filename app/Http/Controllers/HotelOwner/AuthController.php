<?php

namespace App\Http\Controllers\HotelOwner;

use App\Http\Controllers\Controller;
use App\Models\HotelOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('hotel-owner.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('hotel_owner')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('hotel-owner.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegistrationForm()
    {
        return view('hotel-owner.auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:hotel_owners',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => 'nullable|string|max:20',
            'restaurant_name' => 'required|string|max:255',
            'restaurant_address' => 'required|string',
            'restaurant_phone' => 'required|string|max:20',
            'cuisine_type' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|integer|min:0',
            'delivery_time' => 'nullable|integer|min:10|max:120',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $hotelOwner = HotelOwner::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'restaurant_name' => $request->restaurant_name,
            'restaurant_address' => $request->restaurant_address,
            'restaurant_phone' => $request->restaurant_phone,
            'cuisine_type' => $request->cuisine_type,
            'description' => $request->description,
            'delivery_fee' => $request->delivery_fee ?? 0,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'delivery_time' => $request->delivery_time ?? 30,
            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
            'operating_days' => $request->operating_days ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'status' => 'pending', // Admin approval required
        ]);

        Auth::guard('hotel_owner')->login($hotelOwner);

        return redirect()->route('hotel-owner.dashboard')->with('success', 'Registration successful! Your account is pending admin approval.');
    }

    public function logout(Request $request)
    {
        Auth::guard('hotel_owner')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('hotel-owner.login');
    }
}
