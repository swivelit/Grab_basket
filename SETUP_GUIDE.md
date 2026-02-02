# 🚀 Quick Setup Guide - 10-Minute Delivery System

## Step-by-Step Setup

### 1️⃣ Run Migration

```bash
cd E:\GRAB_DEC\grabbaskets
php artisan migrate
```

This adds new columns to your `sellers` table for location data and delivery mode support.

### 2️⃣ Update Your Sellers

Add location data to your existing sellers so they appear in 10-minute delivery:

```bash
php artisan tinker
```

```php
// Update existing seller with location data
$seller = Seller::find(1); // Replace 1 with your seller ID
$seller->available_for_10_min_delivery = true;
$seller->latitude = 28.6273; // Your shop latitude
$seller->longitude = 77.1905; // Your shop longitude
$seller->delivery_radius_km = 5;
$seller->delivery_mode = 'both'; // 'normal', '10-minute', or 'both'
$seller->save();

exit; // Exit tinker
```

### 3️⃣ Clear Cache

```bash
php artisan cache:clear
php artisan config:cache
```

### 4️⃣ Test the Routes

**10-Minute Delivery**:
```
http://localhost:8000/10-minute-delivery
```

**Normal Delivery**:
```
http://localhost:8000/normal-delivery
```

---

## 📍 Key Features

### 10-Minute Delivery ⚡
- ✅ Zepto-style interface
- ✅ 5km radius filtering
- ✅ Real-time 10-minute countdown
- ✅ Limited quick-pickup categories
- ✅ Nearby shop listings
- ✅ Fast checkout

### Normal Delivery 📦
- ✅ Full product catalog
- ✅ All categories
- ✅ Food section
- ✅ Standard delivery options
- ✅ Multi-category browsing

---

## 🎨 Customization

### Change 10-Minute Delivery Categories

Edit `app/Http/Controllers/DeliveryModeController.php`:

```php
private function getTenMinuteDeliveryCategories()
{
    $tenMinuteCategories = [
        'Groceries',
        'Vegetables',
        // Add or remove categories here
    ];
    
    return Category::whereIn('name', $tenMinuteCategories)
        ->with('subcategories')
        ->get();
}
```

### Change Color Themes

**10-Minute Delivery Colors**:
- Primary: `#0C831F` (Green)
- Secondary: `#F8CB46` (Yellow)

Edit in `resources/views/delivery/ten-minute-index.blade.php`:

```css
:root {
    --primary-color: #0C831F;    /* Change this */
    --secondary-color: #F8CB46;  /* And this */
}
```

**Normal Delivery Colors**:
- Primary: `#FF6B00` (Orange)
- Secondary: `#FFD700` (Gold)

Edit in `resources/views/delivery/normal-index.blade.php`

### Change Radius

Default is 5km. To change, edit `DeliveryModeController`:

```php
// In tenMinuteDelivery() method
$stores = $this->getNearbyStores($userLat, $userLng, 5); // Change 5 to desired km
```

---

## 📊 Database Fields Added

To sellers table:

| Field | Type | Default | Purpose |
|-------|------|---------|---------|
| `available_for_10_min_delivery` | BOOLEAN | false | Enable 10-min delivery |
| `latitude` | DECIMAL(10,8) | NULL | Shop latitude |
| `longitude` | DECIMAL(11,8) | NULL | Shop longitude |
| `delivery_radius_km` | INT | 5 | Service radius |
| `delivery_mode` | ENUM | 'normal' | Delivery type |

---

## 🔧 API Endpoints

### Get 10-Minute Delivery Index
```
GET /10-minute-delivery?lat=28.6273&lng=77.1905
```

### Get Normal Delivery Index
```
GET /normal-delivery
```

### Store User Location
```
POST /store-location
Body: {
  "latitude": 28.6273,
  "longitude": 77.1905,
  "address": "Connaught Place, Delhi"
}
```

### Get Category Products (10-min)
```
GET /delivery/category/1
```

---

## 🧪 Test with Sample Data

```sql
-- Add test seller with location
INSERT INTO sellers (
  name,
  email,
  phone,
  available_for_10_min_delivery,
  latitude,
  longitude,
  delivery_radius_km,
  delivery_mode,
  is_active,
  created_at,
  updated_at
) VALUES (
  'Quick Mart Delhi',
  'quickmart@example.com',
  '9999999999',
  1,
  28.6273,
  77.1905,
  5,
  'both',
  1,
  NOW(),
  NOW()
);
```

---

## 📱 Mobile Testing

Both pages are fully responsive and mobile-optimized:

1. **10-Minute Index**: 2-column grid, sticky navbar, touch-friendly
2. **Normal Index**: 2-column grid, categories carousel, food section

Test on different screen sizes:
- 320px (iPhone SE)
- 375px (iPhone 12)
- 768px (iPad)
- 1024px (Desktop)

---

## ⚠️ Common Issues

### Sellers not showing in 10-minute delivery?
- ✅ Check `available_for_10_min_delivery = true`
- ✅ Verify latitude/longitude are set
- ✅ Ensure seller is within 5km of user location
- ✅ Check `delivery_mode` is '10-minute' or 'both'

### Categories not loading?
- ✅ Run `php artisan cache:clear`
- ✅ Check categories exist in database
- ✅ Verify category names match the list in controller

### Cart not working?
- ✅ Check CSRF token in request header
- ✅ Verify cart route exists
- ✅ Check browser console for errors

---

## 📞 Support

If you encounter issues:
1. Check `storage/logs/laravel.log`
2. Run `php artisan tinker` to test queries
3. Use browser DevTools to inspect network requests
4. Verify database fields are properly added

---

**Created**: December 10, 2025  
**Version**: 1.0  
**Status**: ✅ Ready for Production
