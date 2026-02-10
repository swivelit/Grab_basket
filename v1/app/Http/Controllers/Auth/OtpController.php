<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    public function send(Request $request)
    {
        $login = $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $login)->first();
        if (!$user) {
            return back()->withErrors(['login' => 'User not found.']);
        }

        $code = rand(100000, 999999);
        $expires = Carbon::now()->addMinutes(5);
        Otp::create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => $field,
            'expires_at' => $expires,
        ]);

        if ($field === 'email') {
            Mail::raw("Your OTP code is: $code", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Your Login OTP');
            });
        } else {
            // Implement SMS sending logic here (use a service like Twilio or any SMS gateway)
            // Example: Notification::send($user, new SmsOtpNotification($code));
        }

        return view('auth.verify-otp', ['user_id' => $user->id, 'type' => $field]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string',
        ]);
        $otp = Otp::where('user_id', $request->user_id)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->latest()->first();
        if (!$otp) {
            return back()->withErrors(['code' => 'Invalid or expired OTP.']);
        }
        Auth::loginUsingId($request->user_id);
        $user = User::find($request->user_id);
        if ($user->role === 'seller') {
            return redirect()->route('seller.dashboard');
        } else {
            return redirect()->route('buyer.dashboard');
        }
    }
}
