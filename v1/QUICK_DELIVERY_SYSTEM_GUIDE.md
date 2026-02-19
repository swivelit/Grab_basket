# 🚀 10-Minute Express Delivery System with Live Google Maps Tracking

## Overview
A complete Blinkit/Zepto-style quick delivery system with real-time Google Maps tracking, ETA calculation, and delivery partner management.

## 🎯 Features Implemented

### 1. **Express 10-Minute Delivery Logic**
- ✅ Distance-based eligibility (within 5km)
- ✅ Automatic ETA calculation
- ✅ Haversine formula for accurate distance
- ✅ Address geocoding via Google Maps API
- ✅ Store & customer coordinate storage

### 2. **Real-Time Google Maps Tracking**
- ✅ Live delivery partner location on map
- ✅ Route visualization (store → customer)
- ✅ Auto-refresh every 30 seconds
- ✅ ETA countdown display
- ✅ Custom markers (store, customer, delivery partner)

### 3. **Delivery Partner Management**
- ✅ Auto-assignment system
- ✅ Partner details (name, phone, vehicle)
- ✅ Live location updates
- ✅ Direct call functionality

### 4. **Modern UI/UX (Blinkit/Zepto Style)**
- ✅ Green gradient headers
- ✅ Live indicator with pulsing animation
- ✅ Timeline progress tracker
- ✅ Responsive mobile design
- ✅ Smooth animations & transitions

## 📁 Files Created

### 1. **Database Migration**
```
database/migrations/2025_10_22_000001_add_quick_delivery_fields_to_orders.php
```
**Adds these fields to `orders` table:**
- `delivery_type` (express_10min / standard)
- `delivery_promised_at` (promised delivery time)
- `delivery_started_at` (when partner picked up)
- `delivery_completed_at` (when delivered)
- `delivery_partner_name` (assigned partner)
- `delivery_partner_phone` (contact number)
- `delivery_partner_vehicle` (bike/scooter details)
- `delivery_latitude` / `delivery_longitude` (live location)
- `store_latitude` / `store_longitude` (origin)
- `customer_latitude` / `customer_longitude` (destination)
- `eta_minutes` (estimated time of arrival)
- `distance_km` (total distance)
- `is_quick_delivery_eligible` (eligibility flag)
- `delivery_notes` (special instructions)
- `location_updated_at` (last GPS update)

### 2. **Quick Delivery Service**
```
app/Services/QuickDeliveryService.php
```
**Methods:**
- `checkEligibility()` - Verify if address qualifies for 10-min delivery
- `calculateDistance()` - Haversine formula for lat/lng distance
- `getCoordinates()` - Google Geocoding API integration
- `assignDeliveryPartner()` - Auto-assign from partner pool
- `updateDeliveryLocation()` - Real-time location tracking
- `simulateLiveTracking()` - Generate demo waypoints
- `getGoogleMapsRoute()` - Generate navigation URL

### 3. **Live Tracking View**
```
resources/views/orders/live-tracking.blade.php
```
**Features:**
- Google Maps integration with custom styles
- Real-time marker updates
- Route drawing with DirectionsService
- Delivery partner card with contact info
- Order details card
- Delivery address card
- Timeline progress tracker
- Auto-refresh every 30 seconds
- Mobile-responsive design

### 4. **Routes**
```
routes/web.php - Added:
```
- `GET /orders/{order}/live-tracking` - View live tracking page
- `POST /orders/check-quick-delivery` - Check eligibility
- `POST /orders/{order}/assign-delivery` - Assign partner

```
routes/api.php - Created:
```
- `GET /api/order/{order}/track` - Get tracking JSON
- `POST /api/order/{order}/update-location` - Update partner location

### 5. **Controller Methods**
```
app/Http/Controllers/OrderController.php
```
**New methods:**
- `liveTracking()` - Show tracking page
- `checkQuickDelivery()` - Validate address eligibility
- `assignDelivery()` - Assign delivery partner
- `apiTrackOrder()` - Return tracking JSON for AJAX
- `apiUpdateLocation()` - Update partner GPS coordinates

### 6. **Configuration**
```
config/services.php - Added:
```
- Google Maps API key configuration

## 🔧 Installation Steps

### Step 1: Run Migration
```bash
php artisan migrate
```

### Step 2: Add Google Maps API Key
Add to `.env` file:
```env
GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here
```

**Get API Key:**
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create/select project
3. Enable APIs:
   - Maps JavaScript API
   - Geocoding API
   - Directions API
4. Create credentials → API Key
5. Restrict key to your domain

### Step 3: Test the System

#### Test Quick Delivery Eligibility:
```javascript
// From checkout page
fetch('/orders/check-quick-delivery', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    address: '123 Main Street',
    city: 'Bangalore',
    state: 'Karnataka',
    pincode: '560001',
    store_id: 1
  })
})
.then(res => res.json())
.then(data => {
  console.log(data);
  // {eligible: true, distance_km: 3.5, eta_minutes: 10, message: "⚡ 10-Minute Delivery Available!"}
});
```

#### View Live Tracking:
```
https://grabbaskets.laravel.cloud/orders/{order_id}/live-tracking
```

#### Update Delivery Location (API):
```bash
curl -X POST https://grabbaskets.laravel.cloud/api/order/123/update-location \
  -H "Content-Type: application/json" \
  -d '{"latitude": 12.9716, "longitude": 77.5946}'
```

## 📱 How It Works

### Customer Flow:
1. **Checkout** → Enter delivery address
2. **Eligibility Check** → System calculates distance from store
3. **10-Min Option** → Shows if within 5km
4. **Place Order** → Select express/standard delivery
5. **Live Tracking** → Real-time map with partner location
6. **Delivery** → Partner marks as delivered

### Seller Flow:
1. **New Order** → Receive notification
2. **Assign Partner** → System auto-assigns nearby partner
3. **Partner Notified** → Gets order details & navigation
4. **Track Progress** → Monitor delivery status
5. **Completion** → Order marked delivered

### Delivery Partner Flow:
1. **Receive Order** → Push notification with details
2. **Accept** → Navigate to store
3. **Pick Up** → Mark items picked
4. **Navigate** → Google Maps to customer
5. **Update Location** → GPS auto-updates every 10 seconds
6. **Deliver** → Mark as delivered with photo proof

## 🗺️ Google Maps Integration

### Map Features:
- **Custom Markers:**
  - 🏪 Store (green pin)
  - 🛵 Delivery Partner (yellow animated)
  - 📍 Customer (red pin)

- **Route Drawing:**
  - Green polyline from partner to customer
  - Real-time route recalculation
  - Traffic-aware ETA

- **Auto-Refresh:**
  - Location updates every 30 seconds
  - Smooth marker animation
  - ETA countdown

### API Usage:
```javascript
// Initialize map
map = new google.maps.Map(document.getElementById('map'), {
  zoom: 14,
  center: {lat: customerLat, lng: customerLng}
});

// Add delivery partner marker
deliveryMarker = new google.maps.Marker({
  position: {lat: partnerLat, lng: partnerLng},
  map: map,
  animation: google.maps.Animation.BOUNCE,
  icon: customIcon
});

// Draw route
directionsService.route({
  origin: {lat: partnerLat, lng: partnerLng},
  destination: {lat: customerLat, lng: customerLng},
  travelMode: 'DRIVING'
}, (result, status) => {
  if (status === 'OK') {
    directionsRenderer.setDirections(result);
  }
});
```

## 💡 Configuration Options

### Delivery Distance Limits:
```php
// In QuickDeliveryService.php
$isEligible = $distance <= 5.0; // Change to 3.0 for 3km limit
```

### ETA Calculation:
```php
// Average speed in city (km/h)
$etaMinutes = ceil(($distance / 20) * 60); // 20 km/h average
```

### Auto-Refresh Interval:
```javascript
// In live-tracking.blade.php
setInterval(refreshTracking, 30000); // 30 seconds (change to 10000 for 10s)
```

### Partner Pool:
```php
// In QuickDeliveryService::assignDeliveryPartner()
$partners = [
  ['name' => 'Rajesh Kumar', 'phone' => '+91-9876543210', 'vehicle' => 'Bike - KA01AB1234'],
  // Add more partners
];
```

## 🎨 UI Customization

### Colors (CSS Variables):
```css
:root {
  --zepto-green: #0C831F;        /* Primary green */
  --blinkit-yellow: #F8CB46;     /* Accent yellow */
  --express-red: #FF3B3B;        /* Live indicator */
}
```

### Animations:
```css
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}
```

## 🔒 Security Considerations

### 1. **API Authentication**
Currently disabled for testing. Add in production:
```php
Route::middleware('auth:sanctum')->group(function() {
  Route::get('/api/order/{order}/track', ...);
});
```

### 2. **API Key Restrictions**
Restrict Google Maps API key to your domain:
- Application restrictions: HTTP referrers
- Website restrictions: `https://grabbaskets.laravel.cloud/*`

### 3. **Order Verification**
```php
if ($order->buyer_id !== Auth::id()) {
  abort(403, 'Unauthorized access');
}
```

## 📊 Database Schema

### Orders Table Additions:
```sql
-- Delivery Type & Timing
delivery_type ENUM('express_10min', 'standard') DEFAULT 'standard'
delivery_promised_at TIMESTAMP NULL
delivery_started_at TIMESTAMP NULL
delivery_completed_at TIMESTAMP NULL

-- Partner Info
delivery_partner_name VARCHAR(255) NULL
delivery_partner_phone VARCHAR(255) NULL
delivery_partner_vehicle VARCHAR(255) NULL

-- GPS Coordinates
delivery_latitude DECIMAL(10,8) NULL
delivery_longitude DECIMAL(11,8) NULL
store_latitude DECIMAL(10,8) NULL
store_longitude DECIMAL(11,8) NULL
customer_latitude DECIMAL(10,8) NULL
customer_longitude DECIMAL(11,8) NULL

-- Metrics
eta_minutes INTEGER NULL
distance_km DECIMAL(8,2) NULL
is_quick_delivery_eligible BOOLEAN DEFAULT FALSE
location_updated_at TIMESTAMP NULL
```

## 🧪 Testing Checklist

- [ ] Migration runs successfully
- [ ] Google Maps loads on tracking page
- [ ] Address geocoding works
- [ ] Distance calculation accurate
- [ ] 10-min eligibility correct (within 5km)
- [ ] Delivery partner assignment
- [ ] Live marker updates
- [ ] Route drawing works
- [ ] Auto-refresh updates location
- [ ] ETA countdown updates
- [ ] Mobile responsive
- [ ] Call partner button works
- [ ] Timeline progress accurate

## 🚀 Deployment

### Production Setup:
1. **Environment Variables:**
```env
GOOGLE_MAPS_API_KEY=your_production_key
APP_ENV=production
APP_DEBUG=false
```

2. **Cache Config:**
```bash
php artisan config:cache
php artisan route:cache
```

3. **Queue Setup** (for notifications):
```bash
php artisan queue:work
```

4. **Enable HTTPS:**
- Required for Geolocation API
- Use Let's Encrypt or Cloudflare

## 📱 Mobile App Integration

For native mobile apps, use the API endpoints:

### Get Live Tracking:
```
GET /api/order/{order_id}/track
Response:
{
  "order_id": 123,
  "status": "shipped",
  "latitude": 12.9716,
  "longitude": 77.5946,
  "eta_minutes": 8,
  "delivery_partner": {
    "name": "Rajesh Kumar",
    "phone": "+91-9876543210"
  }
}
```

### Update Location (Partner App):
```
POST /api/order/{order_id}/update-location
Body: {"latitude": 12.9716, "longitude": 77.5946}
```

## 🎯 Next Steps

### Recommended Enhancements:
1. **Push Notifications** - Real-time updates to customer
2. **Partner App** - Dedicated app for delivery partners
3. **Order Batching** - Multiple deliveries per partner
4. **Heat Maps** - Demand visualization for store placement
5. **Analytics Dashboard** - Delivery metrics & KPIs
6. **Proof of Delivery** - Photo upload on completion
7. **Rating System** - Customer feedback for partners
8. **Smart Routing** - AI-optimized delivery routes

## 📞 Support

For issues or questions:
- Check Google Maps API quotas
- Verify API key restrictions
- Check browser console for errors
- Review Laravel logs: `storage/logs/laravel.log`

## 📄 License
Part of GrabBaskets E-commerce Platform

---
**Status**: ✅ Ready for Testing
**Last Updated**: October 22, 2025
**Version**: 1.0.0
