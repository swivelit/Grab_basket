<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\InternApplication;
use App\Models\Internship;

class InternshipController extends Controller
{
    // // Live Razorpay Keys (hardcoded)


    public $razorpayId = 'rzp_live_RZLX30zmmnhHum';
    public $razorpaySecret = 'XKmsdH5PbR49EiT74CgehYYi';

    public function index()
    {
        return view('intern.internship');
    }

    public function job()
    {
        return view('intern.job');
    }
    public function form(Request $request)
    {
        // Receive values sent from Apply Now button
        $name = $request->name;
        $fee = $request->fee;
        $weeks = $request->weeks;
        $domain = $request->domain;

        return view('intern.form', compact('name', 'fee', 'weeks', 'domain'));
    }

    public function payment(Request $request)
    {
        // Save form data as pending
        $intern = Internship::create([
            'name' => $request->studentName,
            'email' => $request->studentEmail,
            'phone' => $request->studentPhone,
            'course_name' => $request->courseName,
            'domain' => $request->domain,
            'weeks' => $request->weeks,
            'fee' => $request->fee,
        ]);

        // Create Razorpay Order
        $api = new Api($this->razorpayId, $this->razorpaySecret);
        $order = $api->order->create([
            'receipt' => 'order_' . $intern->id,
            'amount' => $request->fee * 100, // Amount in paise
            'currency' => 'INR'
        ]);

        return response()->json([
            'order_id' => $order['id'],
            'amount' => $request->fee * 100,
            'intern_id' => $intern->id,
            'razorpay_key' => $this->razorpayId,
        ]);
    }

    public function paymentSuccess(Request $request)
    {
        $intern = Internship::find($request->intern_id);
        $intern->payment_id = $request->razorpay_payment_id;
        $intern->status = 'paid';
        $intern->save();

        return view('intern.success', ['intern' => $intern]);
    }

    public function details()
{
    $intern = Internship::all();
    return view('intern.details', compact('intern'));
}

}
