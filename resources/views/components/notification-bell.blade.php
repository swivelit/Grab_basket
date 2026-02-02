@auth
<div class="dropdown">
    <button class="btn btn-outline-light position-relative notification-bell" 
            type="button" 
            id="notificationDropdown" 
            data-bs-toggle="dropdown" 
            aria-expanded="false">
        <i class="bi bi-bell"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count" 
              style="display: none;">
            0
        </span>
    </button>
    
    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
        <li class="dropdown-header d-flex justify-content-between align-items-center">
            <span>Notifications</span>
            <button class="btn btn-sm btn-link text-primary mark-all-read-btn" style="font-size: 0.8rem;">
                Mark all as read
            </button>
        </li>
        <li><hr class="dropdown-divider"></li>
        
        <div class="notification-items">
            <li class="dropdown-item text-center text-muted">
                Loading...
            </li>
        </div>
        
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item text-center text-primary" href="{{ route('notifications.index') }}">
                View All Notifications
            </a>
        </li>
    </ul>
</div>

<script>
// Add this script only once in your layout
if (typeof notificationBellInit === 'undefined') {
    const notificationBellInit = true;
    
    let notificationInterval;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    
    function initNotificationBell() {
        loadNotifications();
        startNotificationPolling();
        
        // Mark all as read
        document.querySelector('.mark-all-read-btn')?.addEventListener('click', function() {
            markAllAsRead();
        });
    }
    
    function loadNotifications() {
        // Load unread count
        fetch('/notifications/unread-count')
            .then(response => response.json())
            .then(data => {
                updateNotificationCount(data.count);
            })
            .catch(error => console.error('Error loading notification count:', error));
        
        // Load recent notifications
        fetch('/notifications/recent')
            .then(response => response.json())
            .then(data => {
                updateNotificationItems(data.notifications);
            })
            .catch(error => console.error('Error loading notifications:', error));
    }
    
    function updateNotificationCount(count) {
        const badge = document.querySelector('.notification-count');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    function updateNotificationItems(notifications) {
        const container = document.querySelector('.notification-items');
        if (!container) return;
        
        if (notifications.length === 0) {
            container.innerHTML = '<li class="dropdown-item text-center text-muted">No new notifications</li>';
            return;
        }
        
        const items = notifications.map(notification => {
            const isUnread = !notification.read_at;
            const timeAgo = getTimeAgo(new Date(notification.created_at));
            
            return `
                <li class="dropdown-item notification-item ${isUnread ? 'fw-bold' : ''}" 
                    data-notification-id="${notification.id}"
                    style="white-space: normal; padding: 10px 15px; ${isUnread ? 'background-color: #f8f9ff;' : ''}">
                    <div class="d-flex align-items-start">
                        <div class="me-2 mt-1">
                            ${getNotificationIcon(notification.type)}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size: 0.9rem;">${notification.title}</div>
                            <div class="text-muted small">${notification.message}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">${timeAgo}</div>
                        </div>
                        ${isUnread ? '<div class="text-primary small">●</div>' : ''}
                    </div>
                </li>
            `;
        }).join('');
        
        container.innerHTML = items;
        
        // Add click handlers for individual notifications
        container.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-notification-id');
                markAsRead(notificationId);
            });
        });
    }
    
    function getNotificationIcon(type) {
        switch(type) {
            case 'order_received':
                return '<i class="bi bi-bag-check-fill text-success"></i>';
            case 'order_placed':
                return '<i class="bi bi-cart-check-fill text-info"></i>';
            case 'order_status_update':
                return '<i class="bi bi-truck text-warning"></i>';
            default:
                return '<i class="bi bi-info-circle text-primary"></i>';
        }
    }
    
    function getTimeAgo(date) {
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        return `${Math.floor(diffInSeconds / 86400)}d ago`;
    }
    
    function markAsRead(notificationId) {
        fetch(`/notifications/${notificationId}/mark-as-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications(); // Refresh notifications
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }
    
    function markAllAsRead() {
        fetch('/notifications/mark-all-as-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications(); // Refresh notifications
            }
        })
        .catch(error => console.error('Error marking all notifications as read:', error));
    }
    
    function startNotificationPolling() {
        // Poll for new notifications every 30 seconds
        notificationInterval = setInterval(loadNotifications, 30000);
    }
    
    function stopNotificationPolling() {
        if (notificationInterval) {
            clearInterval(notificationInterval);
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initNotificationBell);
    
    // Clean up on page unload
    window.addEventListener('beforeunload', stopNotificationPolling);
}
</script>

<style>
.notification-bell {
    position: relative;
}

.notification-dropdown {
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.notification-item:hover {
    background-color: #f8f9fa !important;
    cursor: pointer;
}

.notification-count {
    font-size: 0.7rem;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endauth