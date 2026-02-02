<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Mail\DeliveryPartnerApproved;
use App\Mail\DeliveryPartnerBlocked;
use App\Notifications\DeliveryPartnerNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class DeliveryPartnerController extends Controller
{
    /**
     * Display a listing of delivery partners.
     */
    public function index(Request $request): View
    {
        $query = DeliveryPartner::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by name, email, or phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sort by created_at descending (newest first) by default
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $partners = $query->with('wallet')->paginate(20)->withQueryString();

        // Get counts for each status
        $statusCounts = [
            'all' => DeliveryPartner::count(),
            'pending' => DeliveryPartner::where('status', 'pending')->count(),
            'approved' => DeliveryPartner::where('status', 'approved')->count(),
            'rejected' => DeliveryPartner::where('status', 'rejected')->count(),
            'suspended' => DeliveryPartner::where('status', 'suspended')->count(),
            'inactive' => DeliveryPartner::where('status', 'inactive')->count(),
        ];

        return view('admin.delivery-partners.index', compact('partners', 'statusCounts'));
    }

    /**
     * Display the specified delivery partner.
     */
    public function show(int $id): View
    {
        $partner = DeliveryPartner::with('wallet')->findOrFail($id);
        
        // Get partner's wallet
        $wallet = $partner->wallet ?? null;
        
        // Get partner's recent deliveries (if relationship exists)
        $recentDeliveries = [];
        try {
            if (method_exists($partner, 'deliveries')) {
                $recentDeliveries = $partner->deliveries()
                    ->latest()
                    ->take(10)
                    ->get();
            }
        } catch (\Exception $e) {
            Log::warning('Could not load deliveries for partner', ['partner_id' => $id, 'error' => $e->getMessage()]);
        }
        
        return view('admin.delivery-partners.show', compact('partner', 'wallet', 'recentDeliveries'));
    }

    /**
     * Approve a delivery partner.
     */
    public function approve(int $id): RedirectResponse
    {
        $partner = DeliveryPartner::findOrFail($id);

        if ($partner->status === 'approved') {
            return back()->with('info', 'This delivery partner is already approved.');
        }

        $partner->update([
            'status' => 'approved',
            'is_verified' => true,
        ]);

        // Send approval email to the delivery partner
        try {
            Mail::to($partner->email)->send(new DeliveryPartnerApproved($partner));
            Log::info('Approval email sent to delivery partner', ['partner_id' => $partner->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send approval email', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage()
            ]);
        }

        // Send in-app notification
        try {
            $partner->notify(new DeliveryPartnerNotification(
                'Account Approved! 🎉',
                'Congratulations! Your delivery partner account has been approved. You can now go online and start accepting delivery requests.',
                'success',
                route('delivery-partner.dashboard'),
                'Go to Dashboard',
                ['send_email' => false] // Already sent approval email above
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send approval notification', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage()
            ]);
        }

        return back()->with('success', "Delivery partner '{$partner->name}' has been approved and notified via email.");
    }

    /**
     * Block/suspend a delivery partner.
     */
    public function block(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $partner = DeliveryPartner::findOrFail($id);

        if ($partner->status === 'suspended') {
            return back()->with('info', 'This delivery partner is already suspended.');
        }

        $partner->update([
            'status' => 'suspended',
            'is_online' => false,
            'is_available' => false,
        ]);

        // Send suspension email to the delivery partner
        try {
            Mail::to($partner->email)->send(new DeliveryPartnerBlocked($partner, $request->reason));
            Log::info('Suspension email sent to delivery partner', [
                'partner_id' => $partner->id,
                'reason' => $request->reason
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send suspension email', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage()
            ]);
        }

        // Send in-app notification
        try {
            $partner->notify(new DeliveryPartnerNotification(
                'Account Suspended',
                'Your account has been suspended. Reason: ' . $request->reason . '. Please contact support for assistance.',
                'danger',
                null,
                null,
                ['send_email' => false, 'reason' => $request->reason]
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send suspension notification', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage()
            ]);
        }

        return back()->with('success', "Delivery partner '{$partner->name}' has been suspended and notified via email.");
    }

    /**
     * Unblock/reactivate a delivery partner.
     */
    public function unblock(int $id): RedirectResponse
    {
        $partner = DeliveryPartner::findOrFail($id);

        if ($partner->status !== 'suspended') {
            return back()->with('info', 'This delivery partner is not suspended.');
        }

        $partner->update([
            'status' => 'approved',
        ]);

        // Send reactivation email (reuse approval email)
        try {
            Mail::to($partner->email)->send(new DeliveryPartnerApproved($partner));
            Log::info('Reactivation email sent to delivery partner', ['partner_id' => $partner->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send reactivation email', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage()
            ]);
        }

        return back()->with('success', "Delivery partner '{$partner->name}' has been reactivated and notified via email.");
    }

    /**
     * Reject a delivery partner application.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $partner = DeliveryPartner::findOrFail($id);

        if ($partner->status === 'rejected') {
            return back()->with('info', 'This delivery partner application is already rejected.');
        }

        $partner->update([
            'status' => 'rejected',
        ]);

        // Send rejection email (use blocked template with custom message)
        try {
            Mail::to($partner->email)->send(new DeliveryPartnerBlocked($partner, "Application Rejected: " . $request->reason));
            Log::info('Rejection email sent to delivery partner', [
                'partner_id' => $partner->id,
                'reason' => $request->reason
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send rejection email', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage()
            ]);
        }

        return back()->with('success', "Delivery partner application for '{$partner->name}' has been rejected.");
    }

    /**
     * Update the delivery partner status (legacy method for backwards compatibility).
     */
    public function updateStatus(Request $request, DeliveryPartner $deliveryPartner): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,suspended,inactive'
        ]);

        $oldStatus = $deliveryPartner->status;
        $deliveryPartner->status = $request->status;
        
        // Send appropriate email based on status change
        if ($oldStatus !== 'approved' && $request->status === 'approved') {
            try {
                Mail::to($deliveryPartner->email)->send(new DeliveryPartnerApproved($deliveryPartner));
                Log::info('Approval email sent', ['partner_id' => $deliveryPartner->id]);
            } catch (\Exception $e) {
                Log::error('Failed to send approval email: ' . $e->getMessage());
            }
        } elseif ($request->status === 'suspended' || $request->status === 'rejected') {
            try {
                $reason = $request->input('reason', 'Status changed by admin');
                Mail::to($deliveryPartner->email)->send(new DeliveryPartnerBlocked($deliveryPartner, $reason));
                Log::info('Suspension/Rejection email sent', ['partner_id' => $deliveryPartner->id]);
            } catch (\Exception $e) {
                Log::error('Failed to send suspension/rejection email: ' . $e->getMessage());
            }
        }

        $deliveryPartner->save();

        return back()->with('success', 'Delivery partner status updated successfully.');
    }

    /**
     * Show verification documents.
     */
    public function viewDocuments(DeliveryPartner $deliveryPartner): View
    {
        return view('admin.delivery-partners.documents', compact('deliveryPartner'));
    }

    /**
     * Delete a delivery partner.
     */
    public function destroy(int $id): RedirectResponse
    {
        $partner = DeliveryPartner::findOrFail($id);
        $name = $partner->name;
        
        // Check if partner has active deliveries
        $hasActiveDeliveries = false;
        try {
            if (method_exists($partner, 'deliveries')) {
                $hasActiveDeliveries = $partner->deliveries()
                    ->whereIn('status', ['pending', 'accepted', 'picked_up', 'in_transit'])
                    ->exists();
            }
        } catch (\Exception $e) {
            Log::warning('Could not check active deliveries', ['partner_id' => $id, 'error' => $e->getMessage()]);
        }

        if ($hasActiveDeliveries) {
            return back()->with('error', 'Cannot delete delivery partner with active deliveries.');
        }

        $partner->delete();

        return redirect()
            ->route('admin.delivery-partners.index')
            ->with('success', "Delivery partner '{$name}' has been deleted.");
    }

    /**
     * Toggle online status (AJAX).
     */
    public function toggleOnline(int $id): JsonResponse
    {
        $partner = DeliveryPartner::findOrFail($id);
        
        $partner->update([
            'is_online' => !$partner->is_online,
            'is_available' => !$partner->is_online ? false : $partner->is_available,
        ]);

        return response()->json([
            'success' => true,
            'is_online' => $partner->is_online,
            'is_available' => $partner->is_available,
        ]);
    }
}
