<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */


    public function index()
    {
        $user = Auth::user(); // get logged-in userA
        return view('profile.index', compact('user'));
    }

     public function update(Request $request)
{
        /** @var User $user */
    $user = Auth::user(); // This should return an Eloquent model (App\Models\User)

    $validated = $request->validate([
        'name'            => 'required|string|max:255',
        'phone'           => 'nullable|string|max:20',
        'sex'             => 'nullable|in:male,female,other',
        'dob'             => 'nullable|date',
        'default_address' => 'nullable|string',
        'billing_address' => 'nullable|string',
    ]);

    // Ensure your User model has $fillable for these fields
    $user->update($validated);

    return redirect()->route('profile.show')->with('success', 'Profile updated successfully!');
}

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */


    // public function update(ProfileUpdateRequest $request): RedirectResponse
    // {
    //     $request->user()->fill($request->validated());

    //     if ($request->user()->isDirty('email')) {
    //         $request->user()->email_verified_at = null;
    //     }

    //     $request->user()->save();

    //     return Redirect::route('profile.edit')->with('status', 'profile-updated');
    // }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Display the user's wallet balance.
     */
    public function wallet()
    {
        $user = Auth::user();
        $walletPoints = $user->wallet_point ?? 0;
        $walletAmount = $walletPoints; // 1 point = 1 rupee
        
        return view('profile.wallet', compact('user', 'walletPoints', 'walletAmount'));
    }
}
