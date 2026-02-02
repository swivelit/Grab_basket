<?php

namespace App\Http\Controllers\DeliveryPartner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    /**
     * Display support page
     */
    public function index()
    {
        $partner = auth('delivery_partner')->user();
        
        // TODO: Implement support tickets/FAQs fetching
        $faqs = [];
        $tickets = [];
        
        return view('delivery-partner.support.index', compact('faqs', 'tickets'));
    }

    /**
     * Submit support ticket
     */
    public function submit(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'category' => 'required|string',
        ]);
        
        // TODO: Implement ticket creation logic
        
        return redirect()->back()->with('success', 'Support ticket submitted successfully! We will respond shortly.');
    }
}
