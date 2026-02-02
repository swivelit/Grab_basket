# E-Commerce Project with Razorpay Integration

## Features Implemented

### 🚀 Payment Integration
- **Razorpay Payment Gateway**: Secure payment processing with cards, UPI, and wallets
- **Cash on Delivery (COD)**: Alternative payment option
- **Payment Verification**: Server-side payment verification for security
- **Order Confirmation**: Automatic order creation upon successful payment

### 🛒 Shopping Cart Features
- **Add to Cart**: Products can be added with quantity selection
- **Update Quantity**: Modify item quantities in cart
- **Remove Items**: Delete individual items from cart
- **Move to Wishlist**: Transfer items from cart to wishlist
- **Clear Cart**: Remove all items at once
- **Gift Options**: Automatic gift option detection for applicable products

### ❤️ Wishlist Management
- **Add to Wishlist**: Heart button on products for wishlist management
- **Toggle Wishlist**: Click heart to add/remove from wishlist
- **Wishlist Page**: Dedicated page to view all wishlist items
- **Move to Cart**: Transfer items from wishlist to cart
- **Visual Indicators**: Heart icon changes based on wishlist status

### 📦 Order Management
- **Order Tracking**: Comprehensive order status tracking
- **Status Updates**: Sellers can update order status (pending, confirmed, shipped, delivered, cancelled)
- **Order History**: Buyers can view all their orders
- **Visual Progress**: Step-by-step order progress indicators
- **Gift Support**: Automatic gift option inclusion if available

### 🔔 Notification System
- **Real-time Notifications**: Bell icon with unread count in navbar
- **Order Notifications**: 
  - Sellers get notified when orders are placed
  - Buyers get notified when orders are confirmed/shipped
- **Notification Bell**: Dropdown with recent notifications
- **Mark as Read**: Individual and bulk mark as read functionality
- **Auto Polling**: Notifications update every 30 seconds

### 📧 Email Notifications
- **Order Confirmation**: Buyers receive detailed order confirmation emails
- **Seller Notifications**: Sellers get notified of new orders via email
- **Order Status Updates**: Email notifications for status changes
- **Professional Templates**: Well-designed HTML email templates

### 🏠 Address Management
- **Multiple Addresses**: Users can save multiple delivery addresses
- **Address Selection**: Choose from saved addresses during checkout
- **New Address**: Add new addresses during checkout
- **Geolocation**: Current location detection for address auto-fill

### 👥 User Management
- **Buyer Dashboard**: Shopping interface with product browsing
- **Seller Dashboard**: Order management and product listings
- **Role-based Access**: Different features for buyers and sellers
- **Profile Management**: User profile and address management

## Technical Implementation

### Models Created/Updated
- `Order`: Enhanced with gift options and quantity tracking
- `Wishlist`: User-product wishlist relationships
- `Notification`: User notification system
- `User`: Enhanced with relationship methods
- `CartItem`: Existing model with wishlist integration
- `Product`: Existing with gift option support

### Controllers Implemented
- `PaymentController`: Razorpay integration and payment processing
- `WishlistController`: Wishlist CRUD operations
- `OrderController`: Order tracking and management
- `NotificationController`: Notification system
- `CartController`: Enhanced with wishlist integration

### Database Migrations
- `create_wishlists_table`: User-product wishlist relationships
- `create_notifications_table`: Notification storage
- `add_gift_and_quantity_to_orders_table`: Order enhancements

### Frontend Components
- **Wishlist Heart Button**: Reusable component for wishlist toggle
- **Notification Bell**: Real-time notification dropdown
- **Order Progress**: Visual order status tracking
- **Responsive Design**: Mobile-friendly interfaces

### JavaScript Features
- **Razorpay Integration**: Client-side payment processing
- **AJAX Wishlist**: Seamless wishlist management
- **Real-time Notifications**: Automatic notification updates
- **Payment Verification**: Secure payment confirmation

## Routes Added

### Payment Routes
- `POST /payment/create-order`: Create Razorpay order
- `POST /payment/verify`: Verify payment signature

### Wishlist Routes
- `GET /wishlist`: View wishlist items
- `POST /wishlist/toggle`: Add/remove from wishlist
- `POST /wishlist/move-to-cart`: Move wishlist item to cart
- `GET /wishlist/check/{product}`: Check wishlist status

### Order Routes
- `GET /orders/track`: Track order status
- `GET /orders/{order}`: View order details
- `GET /seller/orders`: Seller order management
- `POST /orders/{order}/update-status`: Update order status

### Notification Routes
- `GET /notifications`: View all notifications
- `POST /notifications/{id}/mark-as-read`: Mark notification as read
- `POST /notifications/mark-all-as-read`: Mark all as read
- `GET /notifications/unread-count`: Get unread count
- `GET /notifications/recent`: Get recent notifications

### Cart Enhancements
- `POST /cart/{cartItem}/move-to-wishlist`: Move cart item to wishlist

## Configuration

### Razorpay Setup
1. Add Razorpay credentials to `.env`:
   ```
   RAZORPAY_KEY_ID=your_key_id
   RAZORPAY_KEY_SECRET=your_key_secret
   ```

2. Configuration in `config/services.php`:
   ```php
   'razorpay' => [
       'key' => env('RAZORPAY_KEY_ID'),
       'secret' => env('RAZORPAY_KEY_SECRET'),
   ],
   ```

### Email Configuration
Ensure mail configuration is set up in `.env` for order notifications:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="Your App Name"
```

## Usage Instructions

### For Buyers
1. **Browse Products**: View products with wishlist heart buttons
2. **Add to Wishlist**: Click heart icon on any product
3. **Add to Cart**: Add products with quantity selection
4. **Checkout Process**:
   - Review cart items (with gift options displayed)
   - Enter/select delivery address
   - Choose payment method (Razorpay or COD)
   - Complete payment through Razorpay
5. **Track Orders**: Monitor order status and progress
6. **Manage Wishlist**: View, add to cart, or remove items
7. **Notifications**: Check bell icon for order updates

### For Sellers
1. **View Orders**: Check incoming orders in seller dashboard
2. **Update Status**: Change order status as items are processed
3. **Email Notifications**: Receive immediate email alerts for new orders
4. **Order Management**: Track all orders for your products

### Gift Options
- Products with `gift_option = true` automatically show gift indicators
- Gift options are displayed in cart, checkout, and order confirmations
- Email templates include gift option information

## Security Features
- CSRF protection on all forms
- Payment signature verification
- User authorization checks
- SQL injection prevention through Eloquent ORM

## Performance Optimizations
- Efficient database queries with eager loading
- Minimal JavaScript for real-time features
- Optimized notification polling
- Responsive design for all devices

## Future Enhancements
- Push notifications for mobile apps
- Advanced order filtering and search
- Bulk order operations for sellers
- Order analytics and reporting
- Return and refund management
- Multi-language support
- Advanced gift options (custom messages, packaging)

This comprehensive e-commerce system provides a complete shopping experience with modern payment integration, real-time notifications, and user-friendly interfaces for both buyers and sellers.