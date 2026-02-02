@props(['product'])

<button class="btn btn-link p-1 wishlist-heart-btn" 
        data-product-id="{{ $product->id }}" 
        title="Add to Wishlist">
    <i class="bi bi-heart wishlist-icon" style="color: #ccc; font-size: 1.25rem; transition: all 0.2s;"></i>
</button>

<script>
// Add this script only once in your layout or main page
if (typeof wishlistHeartInit === 'undefined') {
    const wishlistHeartInit = true;
    
    // Setup CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    
    // Initialize wishlist hearts
    function initWishlistHearts() {
        document.querySelectorAll('.wishlist-heart-btn').forEach(button => {
            const productId = button.getAttribute('data-product-id');
            
            // Check if product is in wishlist
            if (typeof checkWishlistStatus === 'function') {
                checkWishlistStatus(productId, button);
            }
            
            // Add click event
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleWishlist(productId, button);
            });
        });
    }
    
    // Check wishlist status
    function checkWishlistStatus(productId, button) {
        fetch(`/wishlist/check/${productId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            updateHeartIcon(button, data.in_wishlist);
        })
        .catch(error => console.error('Error checking wishlist status:', error));
    }
    
    // Toggle wishlist
    function toggleWishlist(productId, button) {
        fetch('/wishlist/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ product_id: productId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateHeartIcon(button, data.in_wishlist);
                
                // Show toast notification (if you have a toast system)
                if (typeof showToast === 'function') {
                    showToast(data.message, 'success');
                }
            } else {
                alert(data.message || 'Failed to update wishlist');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
    
    // Update heart icon
    function updateHeartIcon(button, inWishlist) {
        const icon = button.querySelector('.wishlist-icon');
        if (inWishlist) {
            icon.classList.remove('bi-heart');
            icon.classList.add('bi-heart-fill');
            icon.style.color = '#e74c3c';
            button.setAttribute('title', 'Remove from Wishlist');
        } else {
            icon.classList.remove('bi-heart-fill');
            icon.classList.add('bi-heart');
            icon.style.color = '#ccc';
            button.setAttribute('title', 'Add to Wishlist');
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initWishlistHearts);
    
    // Re-initialize if new hearts are added dynamically
    window.reinitWishlistHearts = initWishlistHearts;
}
</script>

<style>
.wishlist-heart-btn:hover .wishlist-icon {
    color: #e74c3c !important;
    transform: scale(1.1);
}
</style>