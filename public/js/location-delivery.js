/**
 * Location-Based Delivery System for 10-Minute Express Delivery
 * Fetches products within 2km range using user's current location
 */

class LocationDelivery {
    constructor() {
        this.userLat = null;
        this.userLng = null;
        this.radiusKm = 2;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.autoRefreshInterval = null;
        this.lastUpdateTime = null;
    }

    /**
     * Get user's current location using Geolocation API
     */
    getUserLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation is not supported by this browser'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.userLat = position.coords.latitude;
                    this.userLng = position.coords.longitude;
                    resolve({ lat: this.userLat, lng: this.userLng });
                },
                (error) => {
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });
    }

    /**
     * Store location in session for the server
     */
    async storeLocationInSession() {
        try {
            const response = await fetch('/store-location', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    latitude: this.userLat,
                    longitude: this.userLng,
                    address: await this.getAddressFromCoordinates(this.userLat, this.userLng),
                }),
            });

            if (!response.ok) throw new Error('Failed to store location');
            return await response.json();
        } catch (error) {
            console.error('Error storing location:', error);
            throw error;
        }
    }

    /**
     * Get products within specified radius using location
     */
    async getLocationBasedProducts(categoryId = null, radiusKm = 2, limit = 12) {
        try {
            const response = await fetch('/api/location-based-products', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    latitude: this.userLat,
                    longitude: this.userLng,
                    radius_km: radiusKm,
                    category_id: categoryId,
                    limit: limit,
                }),
            });

            if (!response.ok) throw new Error('Failed to fetch location-based products');
            return await response.json();
        } catch (error) {
            console.error('Error fetching location-based products:', error);
            throw error;
        }
    }

    /**
     * Initialize location-based delivery on page load
     */
    async initialize() {
        try {
            console.log('Initializing location-based delivery...');

            // Try to get user's location
            const location = await this.getUserLocation();
            console.log('User location:', location);

            // Store location in session
            await this.storeLocationInSession();
            console.log('Location stored in session');

            // Fetch and display nearby products
            const products = await this.getLocationBasedProducts();
            console.log('Fetched products:', products);

            return products;
        } catch (error) {
            console.warn('Location-based delivery initialization failed:', error);
            console.log('Falling back to standard product display');
            return null;
        }
    }

    /**
     * Get address from coordinates (Reverse Geocoding)
     * Using Nominatim (OpenStreetMap) - no API key required
     */
    async getAddressFromCoordinates(lat, lng) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`
            );

            if (!response.ok) throw new Error('Geocoding failed');
            const data = await response.json();
            return data.address?.city || data.address?.town || data.address?.village || 'Unknown Location';
        } catch (error) {
            console.warn('Geocoding error:', error);
            return null;
        }
    }

    /**
     * Display products on the page
     */
    displayProducts(productsData, containerId = 'products-grid') {
        const container = document.getElementById(containerId);
        if (!container) {
            console.warn(`Container #${containerId} not found`);
            return;
        }

        if (!productsData?.data || productsData.data.length === 0) {
            container.innerHTML = '<p class="text-center col-12">No products found within ' + this.radiusKm + 'km</p>';
            return;
        }

        // Check if we are rendering for mobile rail (horizontal) or grid
        const isRail = container.classList.contains('rail-scroll');

        container.innerHTML = productsData.data.map(product => {
            if (isRail) {
                // Mobile Rail Card
                return `
                <div class="product-card-mobile" onclick="window.location.href='/product/${product.id}'">
                    <div class="pm-image-box">
                        <img src="${product.image_url || '/images/no-image.png'}" alt="${product.name}" class="pm-image" onerror="this.src='/images/no-image.png'">
                    </div>
                    <div class="fs-8 text-muted truncate-1">${product.distance_km}km • ${product.seller}</div>
                    <div class="fs-7 fw-bold truncate-2 mb-1" style="height: 38px;">${product.name}</div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fs-7 fw-bold">₹${parseFloat(product.price).toFixed(0)}</span>
                    </div>
                    <button class="add-btn" onclick="event.stopPropagation(); addToCart(${product.id})">ADD</button>
                </div>`;
            } else {
                // Standard Grid Card (existing logic)
                return `
                <div class="product-card animate-fade-in" onclick="window.location.href='/product/${product.id}'">
                    <img src="${product.image_url || '/images/no-image.png'}" 
                         alt="${product.name}" 
                         class="product-image"
                         onerror="this.src='/images/no-image.png'">
                    
                    <div style="padding: 12px;">
                        <div style="font-size: 0.85rem; font-weight: 500; color: #0C831F; margin-bottom: 4px;">
                            <i class="bi bi-geo-alt-fill"></i> ${product.distance_km}km away
                        </div>
                        <div class="product-title">${product.name}</div>
                        <div style="font-size: 0.75rem; color: #666; margin: 4px 0;">${product.seller}</div>
                        <div class="product-price">
                            <span class="current-price">₹${parseFloat(product.price).toFixed(2)}</span>
                        </div>
                        <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(${product.id})">
                            <i class="bi bi-cart-plus"></i> Add
                        </button>
                    </div>
                </div>`;
            }
        }).join('');
    }

    /**
     * Get distance between two coordinates in km
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c; // Distance in km
    }

    /**
     * Request location permission and set up auto-refresh (Zepto-like - every 30 seconds)
     */
    async setupAutoRefresh(intervalSeconds = 30) {
        // Clear existing interval if any
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }

        // Initial update
        await this.updateLocationAndProducts();

        // Set up recurring auto-refresh (default 30 seconds like Zepto)
        this.autoRefreshInterval = setInterval(async () => {
            await this.updateLocationAndProducts();
        }, intervalSeconds * 1000);

        console.log(`Location auto-refresh enabled every ${intervalSeconds} seconds`);
    }

    /**
     * Update location and refresh products
     */
    async updateLocationAndProducts() {
        try {
            const location = await this.getUserLocation();
            this.lastUpdateTime = new Date();

            await this.storeLocationInSession();

            const products = await this.getLocationBasedProducts();
            if (products?.data) {
                this.displayProducts(products);
                console.log(`✓ Location updated at ${this.lastUpdateTime.toLocaleTimeString()}`);
            }
        } catch (error) {
            console.warn('Auto-refresh update failed:', error);
        }
    }

    /**
     * Stop auto-refresh
     */
    stopAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
            console.log('Location auto-refresh stopped');
        }
    }
}

// Expose to global scope for manual triggering
window.LocationDelivery = LocationDelivery;

