# Admin Panel - Index Page Editor & Emoji Management

## Overview
This feature provides admin panel interfaces for:
1. **Index Page Editor** - Customize homepage appearance and content
2. **Category Emoji Management** - Manage emojis for all categories

## Features Implemented

### 1. Index Page Editor

#### Access
Navigate to: `http://yourdomain.com/admin/index-editor`

Or from Admin Dashboard: Click **"Index Page Editor"** in the sidebar

#### What You Can Customize

##### Hero Section
- **Hero Title**: Main headline on homepage
- **Hero Subtitle**: Supporting text below title

##### Section Visibility (Show/Hide)
- ✅ Categories Section
- ✅ Banners Carousel
- ✅ Featured Products
- ✅ Trending Products
- ✅ Newsletter Subscription

##### Section Titles
- **Featured Products Title**: Customize "Featured Products" heading
- **Trending Section Title**: Customize "Trending Now" heading
- **Newsletter Title**: Newsletter section heading
- **Newsletter Subtitle**: Newsletter description text

##### Layout Settings
- **Products Per Row**: Choose 2, 3, 4, 5, or 6 products per row
  - Default: 4 products

##### Theme Colors
- **Primary Color**: Main theme color (default: #FF6B00 - Orange)
- **Secondary Color**: Accent color (default: #FFD700 - Gold)
- Color picker with live preview

#### Features
- 🎨 **Live Color Picker**: Visual color selection
- 👁️ **Preview Button**: Opens homepage in new tab to see changes
- 💾 **Auto-Save**: Settings saved to config file
- 🔄 **Cache Clear**: Automatically clears config cache on save

#### How to Use

1. **Login to Admin Panel**
   ```
   http://yourdomain.com/admin
   ```

2. **Navigate to Index Page Editor**
   - Click **"Index Page Editor"** in sidebar
   - Or visit: `http://yourdomain.com/admin/index-editor`

3. **Customize Settings**
   - Edit text fields for titles/subtitles
   - Toggle switches to show/hide sections
   - Select products per row from dropdown
   - Pick colors using color picker

4. **Preview Changes**
   - Click **"Preview Homepage"** button
   - Opens homepage in new tab

5. **Save Changes**
   - Click **"Save Changes"** button
   - Settings saved to `config/index-page.php`
   - Success message displayed

### 2. Category Emoji Management

#### Access
Navigate to: `http://yourdomain.com/admin/category-emojis`

Or from Admin Dashboard: Click **"Category Emojis"** in the sidebar

#### Features (Already Implemented)
- View all categories with current emojis
- Update emoji for each category
- Bulk update multiple emojis at once
- Get AI-suggested emojis based on category name

#### Emoji Suggestions
The system provides smart emoji suggestions for category names:

| Category | Suggested Emojis |
|----------|------------------|
| Electronics | 🖥️ 💻 📱 ⚡ 🔌 |
| Men's Fashion | 👔 👨‍💼 🤵 👕 👖 |
| Women's Fashion | 👗 👠 💃 👛 💄 |
| Home & Kitchen | 🍽️ 🏠 🍳 🔪 🍴 |
| Beauty & Care | 💄 💅 🧴 🪞 ✨ |
| Sports & Fitness | 🏃‍♂️ ⚽ 🏋️‍♂️ 🚴‍♂️ 🏆 |
| Books & Education | 📚 📖 🎓 ✏️ 📝 |
| Kids & Toys | 🧸 🎮 🎯 🎪 🎠 |
| And many more... |

## Technical Implementation

### Files Created/Modified

#### New Files
1. **Controller**: `app/Http/Controllers/Admin/IndexPageEditorController.php`
   - Handles index page settings CRUD operations
   - Saves settings to config file
   - Manages preview functionality

2. **View**: `resources/views/admin/index-editor/index.blade.php`
   - Modern, responsive admin interface
   - Festive gradient design
   - Toggle switches for boolean settings
   - Color pickers with live preview

3. **Config**: `config/index-page.php` (auto-generated on first save)
   - Stores all customization settings
   - PHP array format for fast loading

#### Modified Files
1. **Routes**: `routes/web.php`
   - Added index-editor routes
   - GET `/admin/index-editor` - Show editor
   - PUT `/admin/index-editor/update` - Save settings
   - GET `/admin/index-editor/preview` - Preview homepage

2. **Admin Dashboard**: `resources/views/admin/dashboard.blade.php`
   - Added 3 new menu items:
     - 🖼️ Banner Management
     - 🏠 Index Page Editor
     - 😊 Category Emojis

### Routes Added

#### Index Page Editor Routes
```php
GET  /admin/index-editor              - Show editor interface
PUT  /admin/index-editor/update       - Save settings
GET  /admin/index-editor/preview      - Preview homepage
```

#### Category Emoji Routes (Already Existed)
```php
GET  /admin/category-emojis           - List all categories
PUT  /admin/category-emojis/{id}      - Update single emoji
POST /admin/category-emojis/bulk      - Bulk update emojis
POST /admin/category-emojis/suggest   - Get emoji suggestions
```

### Settings Structure

The index page settings are stored as a PHP array in `config/index-page.php`:

```php
<?php

return [
    'hero_title' => 'Welcome to GrabBaskets',
    'hero_subtitle' => 'Your one-stop shop for all your needs',
    'show_categories' => true,
    'show_featured_products' => true,
    'show_trending' => true,
    'featured_section_title' => 'Featured Products',
    'trending_section_title' => 'Trending Now',
    'products_per_row' => 4,
    'show_banners' => true,
    'show_newsletter' => true,
    'newsletter_title' => 'Subscribe to Our Newsletter',
    'newsletter_subtitle' => 'Get updates on new products and special offers',
    'theme_color' => '#FF6B00',
    'secondary_color' => '#FFD700',
];
```

### Using Settings in Views

To use these settings in your index.blade.php or other views:

```php
@php
    $indexSettings = config('index-page', []);
@endphp

<!-- Example usage -->
@if($indexSettings['show_categories'] ?? true)
    <!-- Show categories section -->
@endif

<h2>{{ $indexSettings['featured_section_title'] ?? 'Featured Products' }}</h2>

<div style="color: {{ $indexSettings['theme_color'] ?? '#FF6B00' }}">
    <!-- Your content -->
</div>
```

## Admin Dashboard Updates

### New Menu Items

1. **🖼️ Banner Management** (`/admin/banners`)
   - Create and manage homepage banners
   - Set active/inactive status
   - Schedule banners with start/end dates

2. **🏠 Index Page Editor** (`/admin/index-editor`)
   - Customize homepage appearance
   - Toggle section visibility
   - Set theme colors

3. **😊 Category Emojis** (`/admin/category-emojis`)
   - Assign emojis to categories
   - Get smart suggestions
   - Bulk update multiple categories

## User Guide

### For Admins

#### Quick Start - Index Page Editor

1. **Login** to admin panel
2. Click **"Index Page Editor"** in sidebar
3. Customize any settings:
   - Type new titles
   - Toggle switches on/off
   - Select different layouts
   - Pick new colors
4. Click **"Preview Homepage"** to see changes
5. Click **"Save Changes"** to apply

#### Quick Start - Category Emojis

1. **Login** to admin panel
2. Click **"Category Emojis"** in sidebar
3. See all categories with current emojis
4. Click emoji to change
5. Select from suggestions or type custom emoji
6. Changes save automatically

### Tips

1. **Colors**: Use brand colors for consistency
2. **Section Visibility**: Hide unused sections for cleaner layout
3. **Products Per Row**: 
   - 4 products = Balanced (default)
   - 3 products = Larger product images
   - 6 products = More products visible
4. **Preview First**: Always preview before saving
5. **Mobile Responsive**: All settings work on mobile devices

## Security

- ✅ Admin authentication required for all routes
- ✅ CSRF protection on all forms
- ✅ Input validation on all fields
- ✅ Session-based access control
- ✅ No direct file system access from frontend

## Performance

- ⚡ Settings cached in config file
- ⚡ No database queries for settings
- ⚡ Auto cache clearing on save
- ⚡ Lightweight color picker
- ⚡ Optimized form validation

## Browser Compatibility

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers

## Troubleshooting

### Settings Not Saving
1. Check file permissions on `config/` directory
2. Ensure write access: `chmod 775 config/`
3. Check Laravel logs: `storage/logs/laravel.log`

### Colors Not Applying
1. Clear browser cache (Ctrl+F5)
2. Check if settings saved: Visit `/admin/index-editor`
3. Clear Laravel cache: `php artisan config:clear`

### Preview Not Working
1. Check if homepage is accessible
2. Ensure no popup blockers
3. Try opening in new tab manually

## Future Enhancements

Potential additions:
- [ ] Upload custom homepage banner
- [ ] Add custom CSS section
- [ ] Create multiple homepage templates
- [ ] A/B testing for different layouts
- [ ] Schedule homepage changes
- [ ] Export/Import settings
- [ ] Reset to default button

## API Documentation

### Index Page Editor API

#### GET /admin/index-editor
Returns the editor interface

**Response**: HTML page with form

#### PUT /admin/index-editor/update
Saves settings to config file

**Request Body**:
```json
{
    "hero_title": "string",
    "hero_subtitle": "string",
    "show_categories": boolean,
    "show_featured_products": boolean,
    "show_trending": boolean,
    "featured_section_title": "string",
    "trending_section_title": "string",
    "products_per_row": integer (2-6),
    "show_banners": boolean,
    "show_newsletter": boolean,
    "newsletter_title": "string",
    "newsletter_subtitle": "string",
    "theme_color": "string (#RRGGBB)",
    "secondary_color": "string (#RRGGBB)"
}
```

**Response**: Redirect with success message

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify routes: `php artisan route:list --name=admin`
3. Clear caches: `php artisan optimize:clear`
4. Review this documentation

---

**Version**: 1.0  
**Created**: October 14, 2025  
**Status**: ✅ Production Ready  
**Tested**: ✅ Fully Functional
