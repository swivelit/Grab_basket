// Dashboard data loading
document.addEventListener('DOMContentLoaded', function() {
    // Show loading indicators
    const loadingElements = document.querySelectorAll('.loading-indicator');
    loadingElements.forEach(el => el.style.display = 'block');

    // Load stats
    fetch('/api/delivery-partner/dashboard/stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data.data);
            }
        })
        .catch(error => console.error('Failed to load stats:', error))
        .finally(() => {
            document.querySelector('#stats-loading').style.display = 'none';
        });

    // Load orders
    fetch('/api/delivery-partner/dashboard/orders')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardOrders(data.data);
            }
        })
        .catch(error => console.error('Failed to load orders:', error))
        .finally(() => {
            document.querySelector('#orders-loading').style.display = 'none';
        });
});

// Update dashboard statistics
function updateDashboardStats(stats) {
    Object.keys(stats).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            if (key.includes('earnings') || key.includes('balance')) {
                element.textContent = formatCurrency(stats[key]);
            } else {
                element.textContent = stats[key];
            }
        }
    });
}

// Update orders section
function updateDashboardOrders(orders) {
    // Update recent orders
    const recentOrdersList = document.querySelector('#recent-orders');
    if (recentOrdersList && orders.recent.length > 0) {
        recentOrdersList.innerHTML = orders.recent.map(order => `
            <div class="order-item">
                <div class="order-header">
                    <span class="order-id">#${order.order_id}</span>
                    <span class="order-status ${order.status}">${order.status}</span>
                </div>
                <div class="order-details">
                    <span class="order-time">${formatDate(order.created_at)}</span>
                    <span class="order-amount">${formatCurrency(order.delivery_fee)}</span>
                </div>
            </div>
        `).join('');
    }

    // Update available orders
    const availableOrdersList = document.querySelector('#available-orders');
    if (availableOrdersList && orders.available.length > 0) {
        availableOrdersList.innerHTML = orders.available.map(order => `
            <div class="order-item available">
                <div class="order-header">
                    <span class="order-id">#${order.order_number}</span>
                    <span class="order-amount">${formatCurrency(order.total_amount)}</span>
                </div>
                <div class="delivery-address">${order.delivery_address}</div>
                <button class="accept-btn" onclick="acceptOrder(${order.id})">Accept Order</button>
            </div>
        `).join('');
    }
}

// Helper functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Accept order function
function acceptOrder(orderId) {
    if (confirm('Are you sure you want to accept this order?')) {
        fetch(`/api/delivery-partner/orders/${orderId}/accept`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to accept order');
            }
        })
        .catch(error => {
            console.error('Error accepting order:', error);
            alert('Failed to accept order. Please try again.');
        });
    }
}