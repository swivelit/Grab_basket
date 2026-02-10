# District-Wise Delivery System Implementation

## Overview
This implementation adds a district-wise delivery filtering system to GrabBaskets, allowing users to:
- View products available for **10-minute express delivery** only within their district and 5km radius
- View **all products** for standard delivery nationwide
- Separate checkout flows for 10-minute and standard delivery orders

## Features Implemented

### 1. **Database Schema Changes**
Location and delivery zone fields added via migration `2025_11_20_000001_add_district_and_delivery_zones.php`

#### Users Table
- `district` - User's district/locality
- `latitude` - User's GPS latitude (for 10-min delivery)
- `longitude` - User's GPS longitude (for 10-min delivery)

#### Products Table
- `delivery_district` - District where product is available
- `available_for_10min` - Boolean flag for 10-minute delivery availability
- `delivery_radius_km` - Delivery radius in kilometers (default: 5km)

#### Sellers Table
- `district` - Store's district
- `store_latitude` - Store's GPS latitude
- `store_longitude` - Store's GPS longitude

#### Orders Table
- `delivery_zone` - Zone where order was delivered
- `is_10min_delivery` - Track if it was a 10-minute delivery order

### 2. **DeliveryZoneService Class**
Location: `app/Services/DeliveryZoneService.php`

**Methods:**
- `calculateDistance($lat1, $lon1, $lat2, $lon2)` - Haversine formula for distance calculation
- `getFilteredProducts($deliveryType, $perPage)` - Filter products by delivery type and district
- `isDeliverableIn10Minutes($product, $user)` - Check if product qualifies for 10-min delivery
- `getUserDeliveryZone($user)` - Get user's delivery zone info
- `getNearbyStores($user, $radiusKm)` - Get stores within radius for 10-min delivery

### 3. **ProductController Updates**
New endpoints for delivery zone filtering:

```php
GET /api/products/by-delivery-type?delivery_type=express_10min&page=1
GET /api/nearby-stores (requires authentication)
GET /api/product/{id}/check-10min (requires authentication)
```

### 4. **CartController**
Already supports separate delivery types:
- `showCheckoutNew()` - Shows split checkout with express and standard sections
- `switchDeliveryType()` - Allows switching between delivery types

### 5. **Routes**
New API routes added to `routes/web.php`:
```php
Route::get('/api/products/by-delivery-type', [ProductController::class, 'getByDeliveryType']);
Route::get('/api/nearby-stores', [ProductController::class, 'getNearbyStores'])->middleware('auth');
Route::get('/api/product/{id}/check-10min', [ProductController::class, 'check10MinDelivery'])->middleware('auth');
```

## How It Works

### For 10-Minute Express Delivery:
1. User logs in - system captures their district and GPS coordinates
2. Products filtered by:
   - `available_for_10min = true`
   - `delivery_district` matches user's district
   - Distance from seller ≤ 5km (if GPS available)
3. Limited product set (max 50 products) for quick browsing
4. Separate checkout process with 10-minute delivery label

### For Standard Delivery:
1. All products shown regardless of district
2. District prioritized if user has one set
3. Standard delivery charges apply
4. Normal checkout process

### Distance Calculation:
Uses **Haversine formula** for accurate distance between coordinates:
```
Distance = 2 * R * asin(sqrt(sin²((lat2-lat1)/2) + cos(lat1) * cos(lat2) * sin²((lon2-lon1)/2)))
R = Earth radius (6371 km)
```

## Setup Instructions

### 1. Run Migration
```bash
php artisan migrate
```

This will create the necessary columns in existing tables.

### 2. Update User Locations
When users login/update profile, capture:
```php
// In ProfileController or AuthController
$user->update([
    'district' => 'Chennai',
    'latitude' => 13.0827,
    'longitude' => 80.2707,
]);
```

### 3. Mark Products for 10-Minute Delivery
When sellers create products:
```php
Product::create([
    'name' => 'Fresh Tomatoes',
    'delivery_district' => 'Chennai',
    'available_for_10min' => true,
    'delivery_radius_km' => 5,
    // ... other fields
]);
```

### 4. Update Seller Store Locations
```php
Seller::where('id', $sellerId)->update([
    'district' => 'Chennai',
    'store_latitude' => 13.0827,
    'store_longitude' => 80.2707,
]);
```

## Frontend Implementation

### Show Delivery Type Tabs
```php
@php
$expressItems = $items->where('delivery_type', 'express_10min');
$standardItems = $items->where('delivery_type', 'standard');
@endphp

<div class="delivery-tabs">
  @if($expressItems->count() > 0)
    <div class="tab">
      <h3>🚚 10-Minute Express Delivery</h3>
      <!-- Show express items -->
    </div>
  @endif

  @if($standardItems->count() > 0)
    <div class="tab">
      <h3>📦 Standard Delivery</h3>
      <!-- Show standard items -->
    </div>
  @endif
</div>
```

### Check 10-Min Availability
```javascript
// JavaScript to check if 10-min delivery available
fetch(`/api/product/${productId}/check-10min`)
  .then(r => r.json())
  .then(data => {
    if(data.is_10min_deliverable) {
      // Show 10-min delivery option
      showButton("Express 10-Min Delivery");
    }
  });
```

### Add to Cart with Delivery Type
```javascript
const addToCart = (productId, deliveryType) => {
  fetch('/cart/add', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('[name=csrf-token]').content
    },
    body: JSON.stringify({
      product_id: productId,
      delivery_type: deliveryType, // 'express_10min' or 'standard'
      quantity: 1
    })
  });
};
```

## Testing

### Test 10-Minute Delivery Filtering
```bash
# Get products for 10-minute delivery (requires login)
GET /api/products/by-delivery-type?delivery_type=express_10min

# Get nearby stores
GET /api/nearby-stores

# Check if specific product has 10-min delivery
GET /api/product/123/check-10min
```

### Database Queries
```sql
-- Products available for 10-min delivery in user's district
SELECT * FROM products 
WHERE available_for_10min = true 
AND delivery_district = 'Chennai'
AND (6371 * acos(...)) <= 5; -- Distance calculation

-- All products for standard delivery
SELECT * FROM products;
```

## Admin Panel Updates Needed

### Seller Dashboard:
- ✅ Mark product as "Available for 10-Minute Delivery"
- ✅ Set delivery district
- ✅ Set delivery radius

### Location Management:
- ✅ Add/update store GPS coordinates
- ✅ Set service districts

### Order Analytics:
- ✅ Filter orders by delivery type
- ✅ Track 10-minute delivery performance

## API Responses

### GET /api/products/by-delivery-type
```json
{
  "success": true,
  "delivery_type": "express_10min",
  "products": [...],
  "pagination": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8
  }
}
```

### GET /api/nearby-stores
```json
{
  "success": true,
  "nearby_stores": [
    {
      "id": 1,
      "name": "Fresh Mart",
      "distance": 2.5,
      "store_latitude": 13.0827,
      "store_longitude": 80.2707
    }
  ],
  "user_location": {
    "district": "Chennai",
    "latitude": 13.0827,
    "longitude": 80.2707
  }
}
```

### GET /api/product/{id}/check-10min
```json
{
  "success": true,
  "product_id": 123,
  "is_10min_deliverable": true,
  "delivery_charge": 0
}
```

## Troubleshooting

### Products Not Showing for 10-Minute Delivery
1. Check if product has `available_for_10min = true`
2. Check if `delivery_district` matches user's district
3. Check GPS distance (must be ≤ 5km if coordinates available)
4. Verify user has district set in profile

### Distance Calculation Issues
- Ensure seller has valid `store_latitude` and `store_longitude`
- Ensure user has valid `latitude` and `longitude`
- Test with known coordinates from Google Maps

## Future Enhancements

1. **Dynamic Pricing** - Different prices for 10-min delivery
2. **Delivery Time Slots** - Show available delivery windows
3. **Multi-District Support** - Sellers can serve multiple districts
4. **Heat Map** - Show 10-min delivery availability on map
5. **Smart Routing** - Optimize delivery routes using distance matrix
6. **Real-Time Updates** - WebSocket updates for availability

## Files Modified/Created

### Created:
- `database/migrations/2025_11_20_000001_add_district_and_delivery_zones.php`
- `app/Services/DeliveryZoneService.php`

### Modified:
- `app/Http/Controllers/ProductController.php` - Added 3 new methods
- `app/Models/Product.php` - Added fillable fields
- `routes/web.php` - Added 3 new API routes

### Existing (Already Implemented):
- `app/Http/Controllers/CartController.php` - Already has separate checkout
- `resources/views/cart/checkout-new.blade.php` - Already supports split carts

## Testing Checklist

- [ ] Migration runs successfully
- [ ] Products can be marked as 10-min available
- [ ] Sellers can set store location
- [ ] Users can set delivery district
- [ ] 10-min products filter by district
- [ ] 10-min products filter by distance
- [ ] Checkout shows separate carts
- [ ] Orders tracked with delivery type
- [ ] API endpoints return correct responses
- [ ] Guest users only see standard delivery
- [ ] Authenticated users see both options

## Support
For implementation help or issues, refer to the existing checkout flow in `resources/views/cart/checkout-new.blade.php` and the product filtering logic in `app/Services/DeliveryZoneService.php`.
