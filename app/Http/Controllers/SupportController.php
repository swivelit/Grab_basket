<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function chatbotSupport(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'email' => 'nullable|email',
        ]);
        $userEmail = $request->input('email') ?: 'anonymous@grabbasket.com';
        $question = $request->input('question');
        $to = config('mail.support_email', env('MAIL_SUPPORT', 'support@grabbasket.com'));
        $subject = 'Chatbot Support Request';
        $body = "Question from: $userEmail\n\n$question";
        try {
            Mail::raw($body, function ($mail) use ($to, $subject, $userEmail) {
                $mail->to($to)
                    ->replyTo($userEmail)
                    ->subject($subject);
            });
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
}
