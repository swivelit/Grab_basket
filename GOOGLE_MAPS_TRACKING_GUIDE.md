# Google Maps Live Order Tracking Integration

## Overview
Comprehensive Google Maps integration for real-time order tracking with delivery partner live location updates.

## Features Implemented

### ✅ 1. Live Order Tracking Page
**File:** `resources/views/orders/live-track.blade.php`

**Features:**
- 🗺️ **Interactive Google Map** showing delivery partner and buyer locations
- 📍 **Real-time Location Updates** (auto-refresh every 30 seconds)
- 🚚 **Delivery Partner Info** with call button
- ⏱️ **Live ETA Calculation** based on distance
- 📊 **Order Status Timeline** with visual progress
- 🔄 **Manual Refresh** button for instant updates
- 📱 **Mobile Responsive** design

**Route:** `https://grabbaskets.com/orders/live-track`

### ✅ 2. Google Maps API Configuration
**File:** `.env`
```env
GOOGLE_MAPS_API_KEY=AIzaSyBFbU1UkuV2HVULSP2rnTwQWYM0xpFvG20
```

**File:** `config/services.php`
```php
'google' => [
    'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
],
```

### ✅ 3. API Endpoints

#### Get Live Location
```
GET /api/orders/{id}/location
```

**Response:**
```json
{
    "success": true,
    "status": "shipped",
    "delivery_lat": 20.5937,
    "delivery_lng": 78.9629,
    "delivery_partner": {
        "name": "John Doe",
        "phone": "+91 9876543210"
    }
}
```

## Setup Instructions

### Step 1: Enable Google Maps APIs

1. **Go to Google Cloud Console:**
   - Visit: https://console.cloud.google.com/

2. **Enable Required APIs:**
   - Maps JavaScript API
   - Geocoding API
   - Places API
   - Geolocation API
   - Distance Matrix API (optional, for ETA)

3. **Create/Copy API Key:**
   - Go to: APIs & Services → Credentials
   - Click "Create Credentials" → "API Key"
   - Copy your API key

4. **Restrict API Key (Security):**
   - Click on your API key
   - Under "API restrictions" → Select "Restrict key"
   - Choose the APIs listed above
   - Under "Application restrictions" → Select "HTTP referrers"
   - Add: `https://grabbaskets.com/*`

### Step 2: Update Environment Variables

**Your .env file** (already configured):
```env
GOOGLE_MAPS_API_KEY=AIzaSyBFbU1UkuV2HVULSP2rnTwQWYM0xpFvG20
```

### Step 3: Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Step 4: Test the Integration

1. **Visit Live Tracking Page:**
   ```
   https://grabbaskets.com/orders/live-track
   ```

2. **Select an Order** from the dropdown

3. **Verify Map Loads** with markers:
   - 🔴 Red marker = Delivery address (buyer location)
   - 🔵 Blue marker = Delivery partner current location
   - Purple line = Route between them

## How It Works

### Frontend Flow

1. **User selects order** from dropdown
2. **JavaScript initializes Google Map:**
   ```javascript
   map = new google.maps.Map(document.getElementById('map'), {
       zoom: 13,
       center: buyerLocation
   });
   ```

3. **Markers are placed:**
   - Buyer location (from order delivery address)
   - Delivery partner location (from DeliveryPartner model)

4. **Route is drawn** using Polyline:
   ```javascript
   routePath = new google.maps.Polyline({
       path: [deliveryLocation, buyerLocation],
       strokeColor: '#667eea',
       strokeWeight: 4
   });
   ```

5. **ETA is calculated** using distance:
   ```javascript
   const distance = google.maps.geometry.spherical.computeDistanceBetween(start, end);
   const etaMinutes = Math.ceil((distance / 1000) * 3); // 3 min per km
   ```

6. **Auto-refresh** every 30 seconds:
   ```javascript
   setInterval(refreshTracking, 30000);
   ```

### Backend Flow

1. **OrderController::liveTrack()**
   - Fetches user's active orders (paid/confirmed/shipped)
   - Loads delivery partner relationships
   - Returns view with orders data

2. **OrderController::getLocation($id)** (API)
   - Verifies user ownership
   - Fetches delivery partner's current location
   - Returns JSON with coordinates

3. **DeliveryPartner Model** should have:
   - `latitude` column (DECIMAL 10,8)
   - `longitude` column (DECIMAL 11,8)
   - Updated when delivery partner app sends location

## Database Requirements

### Orders Table
```sql
ALTER TABLE orders ADD COLUMN delivery_partner_id BIGINT UNSIGNED NULL;
ALTER TABLE orders ADD COLUMN delivery_latitude DECIMAL(10, 8) NULL;
ALTER TABLE orders ADD COLUMN delivery_longitude DECIMAL(11, 8) NULL;
ALTER TABLE orders ADD FOREIGN KEY (delivery_partner_id) REFERENCES delivery_partners(id);
```

### Delivery Partners Table
```sql
-- Should already exist, ensure these columns:
latitude DECIMAL(10, 8) NULL
longitude DECIMAL(11, 8) NULL
is_online BOOLEAN DEFAULT 0
is_available BOOLEAN DEFAULT 1
last_location_update TIMESTAMP NULL
```

## Integration with Delivery Partner App

The delivery partner mobile app should:

### 1. Send Location Updates
```javascript
// Every 10-30 seconds while online
POST /api/v1/delivery/location
{
    "latitude": 20.5937,
    "longitude": 78.9629
}
```

### 2. Update Order Status
```javascript
POST /api/v1/delivery/orders/{id}/pickup
POST /api/v1/delivery/orders/{id}/complete
```

## Usage Guide for Customers

### Accessing Live Tracking

**Option 1: From Order History**
1. Go to "My Orders" → Click on order
2. Click "Live Track" button
3. See real-time delivery location

**Option 2: Direct Link**
1. Visit: `https://grabbaskets.com/orders/live-track`
2. Select your order from dropdown
3. Watch live tracking

### Understanding the Map

- **Red Marker** 🔴 = Your delivery address
- **Blue Marker** 🔵 = Delivery partner (moves in real-time)
- **Purple Line** 💜 = Estimated route
- **ETA Badge** ⏱️ = Estimated time of arrival
- **Live Indicator** 🔴 = Real-time tracking active

### Features Available

1. **Call Delivery Partner:**
   - Click "Call" button next to partner info
   - Directly call from your phone

2. **Refresh Location:**
   - Click floating refresh button (bottom-right)
   - Get instant location update

3. **Auto-Refresh:**
   - Map updates automatically every 30 seconds
   - No manual refresh needed

## Troubleshooting

### Map Not Loading

**Issue:** Blank map or "For development purposes only" watermark

**Solutions:**
1. Check API key is valid:
   ```bash
   curl "https://maps.googleapis.com/maps/api/geocode/json?address=India&key=YOUR_API_KEY"
   ```

2. Enable billing in Google Cloud Console

3. Check API restrictions match your domain

4. Verify API key in `.env`:
   ```bash
   php artisan config:clear
   ```

### Location Not Updating

**Issue:** Delivery partner marker not moving

**Possible Causes:**
1. Delivery partner app not sending location
2. `latitude`/`longitude` columns null in database
3. API endpoint returning cached data

**Solutions:**
1. Check delivery partner table:
   ```sql
   SELECT id, name, latitude, longitude, last_location_update 
   FROM delivery_partners;
   ```

2. Test API endpoint:
   ```bash
   curl -H "Authorization: Bearer YOUR_TOKEN" \
        https://grabbaskets.com/api/orders/123/location
   ```

3. Clear caches:
   ```bash
   php artisan cache:clear
   ```

### ETA Not Showing

**Issue:** "Calculating..." never changes

**Solutions:**
1. Check if both buyer and delivery partner locations exist
2. Verify Google Maps Geometry library is loaded:
   ```javascript
   console.log(google.maps.geometry); // Should not be undefined
   ```

3. Check browser console for JavaScript errors (F12)

## API Key Security

### Current Configuration
- ✅ API key stored in `.env` (not committed to Git)
- ✅ Key restricted to specific APIs
- ⚠️ Add domain restriction in Google Cloud Console

### Recommended Restrictions

**Application Restrictions:**
- HTTP referrers (websites)
- Add: `https://grabbaskets.com/*`

**API Restrictions:**
- Select "Restrict key"
- Enable only:
  - Maps JavaScript API
  - Geocoding API
  - Places API
  - Geolocation API

### Monitoring Usage

**Google Cloud Console → APIs & Services → Quotas:**
- Check daily quota usage
- Set up billing alerts
- Monitor for suspicious activity

## Cost Estimation

### Google Maps Pricing (as of 2024)

**Maps JavaScript API:**
- First 28,000 loads/month: FREE
- Additional: $7 per 1,000 loads

**Geocoding API:**
- First 40,000 requests/month: FREE  
- Additional: $5 per 1,000 requests

**Typical Usage:**
- 100 orders/day with live tracking
- 100 × 30 page loads × 30 days = 90,000 loads/month
- Cost: ~$450/month

**Optimization Tips:**
1. Cache geocoding results
2. Implement map clustering for multiple orders
3. Use static maps for order history
4. Limit auto-refresh frequency

## Advanced Features

### 1. Geofencing Alerts
```javascript
// Alert when delivery partner is near (within 500m)
const distance = google.maps.geometry.spherical.computeDistanceBetween(
    deliveryLocation, 
    buyerLocation
);

if (distance < 500) {
    showNotification('Delivery partner is nearby!');
}
```

### 2. Route Optimization
```javascript
// Use Directions API for accurate routing
const directionsService = new google.maps.DirectionsService();

directionsService.route({
    origin: deliveryLocation,
    destination: buyerLocation,
    travelMode: 'DRIVING'
}, function(result, status) {
    if (status === 'OK') {
        directionsDisplay.setDirections(result);
    }
});
```

### 3. Traffic Layer
```javascript
// Show live traffic
const trafficLayer = new google.maps.TrafficLayer();
trafficLayer.setMap(map);
```

## Testing Checklist

- [ ] Google Maps API key is configured
- [ ] Map loads correctly on live-track page
- [ ] Order dropdown shows active orders
- [ ] Buyer location marker appears
- [ ] Delivery partner location marker appears (if assigned)
- [ ] Route line is drawn between markers
- [ ] ETA is calculated and displayed
- [ ] Auto-refresh updates location every 30 seconds
- [ ] Manual refresh button works
- [ ] Call button opens phone dialer
- [ ] Status timeline shows correct progress
- [ ] Mobile responsive design works
- [ ] API endpoint returns valid JSON
- [ ] Unauthorized users cannot access other's orders

## Next Steps

1. **Deploy to Production:**
   - Upload `resources/views/orders/live-track.blade.php`
   - Upload updated `app/Http/Controllers/OrderController.php`
   - Upload updated `routes/web.php` and `routes/api.php`
   - Run: `php artisan config:clear`

2. **Test End-to-End:**
   - Place a test order
   - Assign delivery partner
   - Update delivery partner location
   - View live tracking

3. **Enable for Delivery Partners:**
   - Ensure delivery partner app sends location updates
   - Test location update API endpoint
   - Verify location refreshes on customer map

4. **Monitor Performance:**
   - Check Google Maps API usage
   - Monitor page load times
   - Track customer engagement

## Support

**Issues?**
1. Check browser console (F12) for errors
2. Verify API key in Google Cloud Console
3. Test API endpoints with Postman
4. Check Laravel logs: `storage/logs/laravel.log`

**Need Help?**
- Google Maps Documentation: https://developers.google.com/maps
- Laravel Docs: https://laravel.com/docs

---

**Status:** ✅ Ready for deployment and testing
**Last Updated:** 2025-11-01
