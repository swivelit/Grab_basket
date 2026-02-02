@extends('seller.layouts.app')

@section('title', '10-Min Delivery Orders')

@push('styles')
<style>
    /* Scoped or adjusted styles for this page */
    .ten-mins-container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .ten-mins-header {
        background: #4CAF50;
        color: white;
        padding: 16px 24px;
        font-size: 20px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .search-box input {
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        width: 300px;
        font-size: 14px;
    }

    /* Target tables inside our container to avoid global conflict if possible, 
       but for now keeping it simple as specific classes */
    .ten-mins-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ten-mins-table th,
    .ten-mins-table td {
        padding: 14px 16px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .ten-mins-table th {
        background-color: #f1f1f1;
        font-weight: 600;
        color: #222;
    }

    .ten-mins-table tr:hover {
        background-color: #f9f9f9;
    }

    img.product-img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        vertical-align: middle;
        margin-right: 10px;
    }

    select.status-select {
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
        cursor: pointer;
    }

    .status-pending {
        background: #fff3e0;
        color: #e65100;
    }

    .status-confirmed {
        background: #e3f2fd;
        color: #1565c0;
    }

    .status-delivered {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-cancelled {
        background: #ffebee;
        color: #c62828;
    }

    .empty-message {
        text-align: center;
        padding: 40px;
        color: #666;
        font-style: italic;
    }

    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 6px;
        color: white;
        font-weight: 600;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .notification.show {
        opacity: 1;
    }

    .success {
        background: #4CAF50;
    }

    .error {
        background: #f44336;
    }

    .order-id {
        font-weight: bold;
        color: #1976d2;
        text-decoration: none;
    }

    .order-id:hover {
        text-decoration: underline;
    }
</style>
@endpush

@section('content')
<div class="ten-mins-container">
    <header class="ten-mins-header">
        🚀 10-Min Delivery Orders
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by Order ID, Buyer, or Product...">
        </div>
    </header>

    <table class="ten-mins-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Product</th>
                <th>Buyer</th>
                <th>Amount (₹)</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Phone</th>
                <th>Placed At</th>
                <th>Est. Delivery</th>
            </tr>
        </thead>
        <tbody id="ordersTableBody">
            @forelse($ordersForSeller as $order)
            @php
            $firstItem = $order->items->first();
            $productName = $firstItem ? $firstItem->product_name : '—';
            $imagePath = null;
            if ($firstItem && $firstItem->product && $firstItem->product->image) {
            $imagePath = asset('storage/products/' . $firstItem->product->image);
            }
            $orderShowUrl = route('seller.tenmins.orders.show', $order->id);
            @endphp
            <tr data-order-id="{{ $order->id }}"
                data-buyer-name="{{ $order->customer_name ?? '' }}"
                data-product-name="{{ $productName }}">
                <td><a href="{{ $orderShowUrl }}" class="order-id">#{{ $order->id }}</a></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        @if($imagePath)
                        <img src="{{ $imagePath }}" class="product-img" alt="Product">
                        @endif
                        <span>{{ $productName }}</span>
                        @if($order->items->count() > 1)
                        <small style="color:#888;">(+{{ $order->items->count() - 1 }})</small>
                        @endif
                    </div>
                </td>
                <td>{{ $order->customer_name ?? '—' }}</td>
                <td>₹{{ number_format($order->total_amount, 2) }}</td>
                <td>
                    <select class="status-select" data-order-id="{{ $order->id }}" data-current-status="{{ $order->status }}">
                        <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </td>
                <td>{{ $order->payment_method ?? 'COD' }}</td>
                <td>{{ $order->customer_phone ?? '—' }}</td>
                <td>
                    @if($order->created_at)
                    {{ $order->created_at->format('d M Y, h:i A') }}
                    @else
                    —
                    @endif
                </td>
                <td>
                    @if($order->estimated_delivery_time)
                    {{ $order->estimated_delivery_time->format('d M Y, h:i A') }}
                    @else
                    —
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="empty-message">
                    No 10-minute delivery orders yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Notification Popup -->
<div id="notification" class="notification"></div>
@endsection

@push('scripts')
<script>
    const updateStatusUrlTemplate = "{{ route('seller.tenmins.orders.update', ['id' => '__ID__']) }}";

    function getUpdateUrl(orderId) {
        return updateStatusUrlTemplate.replace('__ID__', orderId);
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const notification = document.getElementById('notification');
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('#ordersTableBody tr[data-order-id]');

        // === Multi-Field Search (Order ID, Buyer Name, Product Name) ===
        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            if (query === '') {
                tableRows.forEach(row => row.style.display = '');
                return;
            }

            tableRows.forEach(row => {
                const orderId = row.getAttribute('data-order-id').toLowerCase();
                const buyerName = row.getAttribute('data-buyer-name').toLowerCase();
                const productName = row.getAttribute('data-product-name').toLowerCase();

                if (
                    orderId.includes(query) ||
                    buyerName.includes(query) ||
                    productName.includes(query)
                ) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // === Status Update ===
        function showNotification(message, isError = false) {
            notification.textContent = message;
            notification.className = `notification ${isError ? 'error' : 'success'} show`;
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', async function() {
                const orderId = this.getAttribute('data-order-id');
                const newStatus = this.value;
                const previousStatus = this.getAttribute('data-current-status');

                try {
                    const url = getUpdateUrl(orderId);
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            status: newStatus
                        })
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Server error:', errorText);
                        throw new Error('Server error');
                    }

                    const result = await response.json();
                    showNotification('Status updated successfully!');
                    this.setAttribute('data-current-status', newStatus);

                } catch (err) {
                    console.error('Update failed:', err);
                    showNotification('Failed to update status', true);
                    this.value = previousStatus;
                }
            });
        });
    });
</script>
@endpush