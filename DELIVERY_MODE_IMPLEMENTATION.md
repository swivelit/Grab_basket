# GrabBaskets 10-Minute Delivery System Implementation

## Overview

This is a complete Zepto-like 10-minute express delivery system integrated into GrabBaskets. The implementation includes separate delivery modes for:

1. **10-Minute Express Delivery** - For quick grocery pickup from nearby shops (5km radius)
2. **Normal Delivery** - Full product catalog including food, groceries, and daily essentials

## Features

### 10-Minute Delivery Mode ⚡

- **5km Radius Filtering**: Only shows shops within 5km radius of user location
- **Quick Categories**: Limited to time-sensitive categories:
  - Groceries
  - Vegetables
  - Fruits
  - Dairy & Eggs
  - Bread & Bakery
  - Beverages
  - Snacks
  - Household Items
  - Personal Care
  - Health & Wellness

- **Features**:
  - Real-time countdown timer (10 minutes)
  - Nearby shop listings with distance
  - Geolocation-based store discovery
  - Fast checkout experience
  - Delivery partner pickup from multiple shops

### Normal Delivery Mode 📦

- **Full Catalog**: All products and categories available
- **Food Section**: Special section for restaurants and food items
- **Standard Delivery**: Regular delivery with wider service area
- **Multiple Categories**: All product types available

## File Structure

```
grabbaskets/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── DeliveryModeController.php (NEW)
│   └── Models/
│       └── Seller.php (UPDATED)
├── database/
│   └── migrations/
│       └── 2025_12_10_000000_add_delivery_mode_support.php (NEW)
├── resources/
│   └── views/
│       └── delivery/
│           ├── ten-minute-index.blade.php (NEW)
│           └── normal-index.blade.php (NEW)
└── routes/
    └── web.php (UPDATED)
```

## Setup Instructions

### 1. Run Database Migration

```bash
php artisan migrate
```

This will add the following columns to the `sellers` table:
- `available_for_10_min_delivery` (boolean)
- `latitude` (decimal)
- `longitude` (decimal)
- `delivery_radius_km` (integer)
- `delivery_mode` (enum: 'normal', '10-minute', 'both')

### 2. Update Seller Information

Add location data to sellers for geolocation filtering:

```sql
UPDATE sellers SET 
  latitude = 28.7041,  -- Example coordinates (Delhi)
  longitude = 77.1025,
  available_for_10_min_delivery = 1,
  delivery_radius_km = 5,
  delivery_mode = 'both'
WHERE id = 1;
```

### 3. Clear Cache

```bash
php artisan cache:clear
php artisan config:cache
```

## API Routes

### 10-Minute Delivery Routes

| Method | Route | Controller | Description |
|--------|-------|-----------|-------------|
| GET | `/10-minute-delivery` | DeliveryModeController@tenMinuteDelivery | 10-min delivery index |
| POST | `/store-location` | DeliveryModeController@storeLocation | Store user location |
| GET | `/delivery/category/{categoryId}` | DeliveryModeController@getCategoryProducts | Category products for 10-min delivery |

### Normal Delivery Routes

| Method | Route | Controller | Description |
|--------|-------|-----------|-------------|
| GET | `/normal-delivery` | DeliveryModeController@normalDelivery | Normal delivery index |

## Controller: DeliveryModeController

### Methods

#### `tenMinuteDelivery(Request $request)`
Returns Zepto-style index with:
- Nearby shops within 5km radius
- Quick-pickup categories
- Featured products from nearby stores
- Countdown timer (10 minutes)

**Parameters**:
- `lat` - User latitude (optional)
- `lng` - User longitude (optional)

**Returns**: View with filtered data

#### `normalDelivery(Request $request)`
Returns full catalog index with:
- All categories
- All products
- Food section
- No distance restrictions

**Returns**: View with full product data

#### `storeLocation(Request $request)`
Stores user location in session for future filtering.

**Request Body**:
```json
{
  "latitude": 28.7041,
  "longitude": 77.1025,
  "address": "Connaught Place, Delhi"
}
```

#### `getCategoryProducts(Request $request, $categoryId)`
Fetches products by category for 10-minute delivery.

**Parameters**:
- `categoryId` - Category ID to filter

**Returns**: Paginated products within 5km radius

### Haversine Formula Implementation

The controller uses the Haversine formula to calculate distances between user and shops:

```
distance = 6371 * acos(
  cos(radians(user_lat)) * cos(radians(shop_lat)) * 
  cos(radians(shop_lng) - radians(user_lng)) + 
  sin(radians(user_lat)) * sin(radians(shop_lat))
)
```

This provides accurate distance calculations in kilometers.

## Frontend Features

### 10-Minute Delivery UI
- **Navbar**: Delivery mode badge, search bar, cart
- **Hero Banner**: Countdown timer, delivery details
- **Sticky Categories**: Quick-filter categories
- **Product Grid**: 2-column grid on mobile, 3-4 on desktop
- **Store Listings**: Nearby shops with distance and ETA
- **Mobile Optimized**: Touch-friendly UI for mobile users

### Normal Delivery UI
- **Full Navigation**: Logo, search, wishlist, cart
- **Hero Banner**: Delivery options toggle (10-min vs Normal)
- **All Categories**: Complete category listing
- **Product Grid**: Full product catalog
- **Food Section**: Special orange-themed food section
- **Responsive Design**: Works on all devices

## Styling

### Color Scheme

**10-Minute Delivery**:
- Primary: `#0C831F` (Green) - Healthy, fresh products
- Secondary: `#F8CB46` (Yellow) - Accent
- Theme: Fast, fresh, urgent

**Normal Delivery**:
- Primary: `#FF6B00` (Orange) - Zomato-like style
- Secondary: `#FFD700` (Gold) - Accent
- Food Section: Orange gradient

## Database Schema

### Sellers Table Updates

```sql
ALTER TABLE sellers ADD COLUMN available_for_10_min_delivery BOOLEAN DEFAULT 0;
ALTER TABLE sellers ADD COLUMN latitude DECIMAL(10,8);
ALTER TABLE sellers ADD COLUMN longitude DECIMAL(11,8);
ALTER TABLE sellers ADD COLUMN delivery_radius_km INT DEFAULT 5;
ALTER TABLE sellers ADD COLUMN delivery_mode ENUM('normal', '10-minute', 'both') DEFAULT 'normal';
```

### Delivery Settings Table

```sql
CREATE TABLE delivery_settings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  setting_name VARCHAR(255),
  setting_value VARCHAR(255),
  description TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

## JavaScript Features

### Cart Management
```javascript
addToCart(productId)     // Add product to cart
updateCartCount()        // Update cart badge
showToast(message)       // Show success/error message
```

### Timer
- 10-minute countdown timer on express delivery page
- Auto-stops after 10 minutes

### Category Filtering
- Dynamic category switching
- Filter products by selected category

### Delivery Mode Toggle
- Switch between 10-minute and normal delivery
- Preserves cart and preferences

## How to Use

### For Users

1. **Choose Delivery Mode**:
   - Visit `/10-minute-delivery` for express delivery
   - Visit `/normal-delivery` for regular shopping

2. **Enable Location** (for 10-minute):
   - Allow browser geolocation
   - Or manually enter location

3. **Browse & Shop**:
   - Click category pills to filter
   - Search for products
   - Add items to cart

4. **Checkout**:
   - Review items
   - Select delivery option
   - Complete payment

### For Admins

1. **Update Seller Info**:
   - Set `available_for_10_min_delivery = true`
   - Add `latitude` and `longitude`
   - Set `delivery_mode` to '10-minute' or 'both'

2. **Monitor Deliveries**:
   - Track delivery partners
   - Monitor order status
   - Manage multiple stores

## Performance Optimization

- **Haversine Query**: Indexes on latitude/longitude improve query speed
- **Caching**: Use Laravel cache for category lists
- **Lazy Loading**: Load products on demand
- **Image Optimization**: Serve optimized images
- **CDN**: Use CDN for static assets

## Future Enhancements

1. **Real-time GPS Tracking**: Track delivery partners in real-time
2. **Predictive ETA**: ML-based delivery time prediction
3. **Multi-language Support**: Support multiple languages
4. **Dark Mode**: Dark theme option
5. **Push Notifications**: Order updates and promotions
6. **Scheduled Orders**: Pre-order for later delivery
7. **Subscription Plans**: Regular delivery subscriptions
8. **Premium Features**: Priority support, loyalty points

## Testing

### Test Locations

Use these test coordinates for development:

```php
// Delhi - Connaught Place
latitude: 28.6273
longitude: 77.1905

// Mumbai - Fort
latitude: 18.9556
longitude: 72.8295

// Bangalore - MG Road
latitude: 12.9352
longitude: 77.6245
```

### Sample Seller Setup

```sql
INSERT INTO sellers (
  name, 
  email, 
  latitude, 
  longitude, 
  available_for_10_min_delivery, 
  delivery_radius_km, 
  delivery_mode,
  is_active
) VALUES (
  'Quick Shop Delhi',
  'shop@quick.com',
  28.6273,
  77.1905,
  1,
  5,
  'both',
  1
);
```

## Troubleshooting

### Issue: Stores not showing in 10-minute delivery

**Solution**: 
1. Check seller has `available_for_10_min_delivery = true`
2. Verify latitude/longitude are set correctly
3. Ensure user location is within 5km radius
4. Check database query with correct Haversine formula

### Issue: Categories not appearing

**Solution**:
1. Ensure categories exist in database
2. Check category names match the predefined list
3. Verify categories are linked to products
4. Clear cache: `php artisan cache:clear`

### Issue: Cart not working

**Solution**:
1. Verify cart route exists: `POST /cart/add`
2. Check CSRF token in request header
3. Ensure cart controller is available
4. Check browser console for JavaScript errors

## Support

For issues or questions:
1. Check the logs: `storage/logs/laravel.log`
2. Review the database schema
3. Test API endpoints with Postman
4. Check browser developer tools for errors

## License

This implementation is part of the GrabBaskets project and follows the same license terms.

---

**Version**: 1.0  
**Last Updated**: December 10, 2025  
**Status**: Production Ready ✅
