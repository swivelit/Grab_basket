# Mobile App Development Plan - GrabBaskets E-commerce

## Project Overview
Convert the existing Laravel e-commerce website into **2 separate mobile applications** for Google Play Store:
1. **Customer App** - For buyers to browse, purchase, and manage orders
2. **Delivery Partner App** - For delivery partners to manage deliveries, track earnings

## App Architecture

### Technology Stack
- **Frontend**: React Native (Cross-platform iOS/Android)
- **Backend**: Laravel API (existing codebase with new API endpoints)
- **Database**: MySQL (existing)
- **State Management**: Redux Toolkit
- **Navigation**: React Navigation v6
- **Authentication**: JWT tokens
- **Real-time**: Socket.io for live updates
- **Maps**: Google Maps API
- **Payments**: Razorpay SDK
- **Push Notifications**: Firebase Cloud Messaging

## App 1: Customer App (GrabBaskets)

### Features
1. **Authentication**
   - Phone/Email OTP login
   - Social login (Google, Facebook)
   - Profile management with avatar

2. **Product Browsing**
   - Category-wise browsing with emojis
   - Search with filters
   - Product details with image gallery
   - Reviews and ratings
   - Wishlist functionality

3. **Shopping Cart & Checkout**
   - Add/remove/modify cart items
   - Quick delivery (10-minute) option
   - Multiple payment methods
   - Address management
   - Order tracking

4. **User Features**
   - Order history
   - Profile management
   - Notifications
   - Support chat
   - Location-based stores

5. **Quick Delivery (Blinkit-style)**
   - 10-minute delivery option
   - Real-time order tracking
   - Live delivery partner location

### Screens Structure
```
src/
├── screens/
│   ├── Auth/
│   │   ├── LoginScreen.js
│   │   ├── OTPScreen.js
│   │   └── ProfileSetupScreen.js
│   ├── Home/
│   │   ├── HomeScreen.js
│   │   ├── CategoryScreen.js
│   │   └── SearchScreen.js
│   ├── Product/
│   │   ├── ProductListScreen.js
│   │   ├── ProductDetailScreen.js
│   │   └── ReviewsScreen.js
│   ├── Cart/
│   │   ├── CartScreen.js
│   │   ├── CheckoutScreen.js
│   │   └── PaymentScreen.js
│   ├── Orders/
│   │   ├── OrdersScreen.js
│   │   ├── OrderDetailScreen.js
│   │   └── TrackingScreen.js
│   └── Profile/
│       ├── ProfileScreen.js
│       ├── AddressScreen.js
│       └── WishlistScreen.js
```

## App 2: Delivery Partner App (GrabBaskets Delivery)

### Features
1. **Authentication**
   - Partner registration with documents
   - Phone verification
   - Profile with earnings dashboard

2. **Order Management**
   - Available orders map view
   - Accept/reject orders
   - Pickup and delivery workflow
   - Order details and customer info

3. **Navigation & Tracking**
   - GPS navigation to pickup/delivery
   - Real-time location sharing
   - Route optimization
   - Delivery proof (photo/signature)

4. **Earnings & Wallet**
   - Daily/weekly/monthly earnings
   - ₹25 per delivery rewards
   - Wallet management
   - Withdrawal requests

5. **Partner Features**
   - Online/offline status toggle
   - Delivery history
   - Ratings and feedback
   - Support system

### Screens Structure
```
src/
├── screens/
│   ├── Auth/
│   │   ├── LoginScreen.js
│   │   ├── RegisterScreen.js
│   │   └── DocumentUploadScreen.js
│   ├── Dashboard/
│   │   ├── DashboardScreen.js
│   │   ├── EarningsScreen.js
│   │   └── StatusScreen.js
│   ├── Orders/
│   │   ├── AvailableOrdersScreen.js
│   │   ├── OrderDetailScreen.js
│   │   ├── NavigationScreen.js
│   │   └── DeliveryProofScreen.js
│   ├── Wallet/
│   │   ├── WalletScreen.js
│   │   ├── TransactionsScreen.js
│   │   └── WithdrawScreen.js
│   └── Profile/
│       ├── ProfileScreen.js
│       ├── DocumentsScreen.js
│       └── HistoryScreen.js
```

## Laravel API Development

### New API Endpoints Required

#### Authentication API
```
POST /api/auth/login
POST /api/auth/register
POST /api/auth/verify-otp
POST /api/auth/refresh-token
POST /api/auth/logout
```

#### Customer App API
```
GET /api/categories
GET /api/products
GET /api/products/{id}
GET /api/search
POST /api/cart/add
GET /api/cart
POST /api/orders
GET /api/orders
GET /api/orders/{id}/track
```

#### Delivery Partner API
```
GET /api/delivery/available-orders
POST /api/delivery/orders/{id}/accept
POST /api/delivery/orders/{id}/pickup
POST /api/delivery/orders/{id}/complete
GET /api/delivery/earnings
POST /api/delivery/location
```

### Database Modifications
```sql
-- Add mobile-specific fields
ALTER TABLE users ADD COLUMN device_token VARCHAR(255);
ALTER TABLE users ADD COLUMN app_version VARCHAR(20);
ALTER TABLE delivery_partners ADD COLUMN current_latitude DECIMAL(10,8);
ALTER TABLE delivery_partners ADD COLUMN current_longitude DECIMAL(11,8);
ALTER TABLE orders ADD COLUMN live_tracking_url TEXT;
```

## Development Timeline

### Phase 1: API Development (Week 1-2)
- [ ] Create API controllers and routes
- [ ] Implement JWT authentication
- [ ] Setup API documentation
- [ ] Test API endpoints

### Phase 2: Customer App Development (Week 3-5)
- [ ] Setup React Native project
- [ ] Implement authentication flow
- [ ] Build product browsing screens
- [ ] Shopping cart and checkout
- [ ] Order tracking features

### Phase 3: Delivery Partner App (Week 6-7)
- [ ] Setup React Native project
- [ ] Partner authentication
- [ ] Order management system
- [ ] GPS tracking and navigation
- [ ] Earnings dashboard

### Phase 4: Integration & Testing (Week 8)
- [ ] API integration testing
- [ ] Real-time features testing
- [ ] Payment gateway testing
- [ ] Performance optimization

### Phase 5: Play Store Deployment (Week 9)
- [ ] App signing and build
- [ ] Play Store assets creation
- [ ] App listing and submission
- [ ] Beta testing setup

## Play Store Requirements

### App Store Assets Needed
1. **App Icons** (512x512, 192x192, 144x144, etc.)
2. **Screenshots** (Phone and tablet)
3. **Feature Graphics** (1024x500)
4. **App Descriptions** (Short and full)
5. **Privacy Policy** and **Terms of Service**
6. **Content Rating** questionnaire
7. **App Signing Key** (Android App Bundle)

### App Names
- **Customer App**: "GrabBaskets - Quick Grocery Delivery"
- **Delivery Partner App**: "GrabBaskets Delivery Partner"

## Key Considerations

### Security
- JWT token expiration handling
- API rate limiting
- Input validation and sanitization
- Secure file uploads

### Performance
- Image optimization and caching
- Lazy loading for product lists
- Background sync for offline functionality
- Push notification optimization

### User Experience
- Intuitive navigation
- Fast loading times
- Offline mode support
- Error handling and retry mechanisms

### Scalability
- Modular code architecture
- Caching strategies
- Database optimization
- CDN for images

## Next Steps

1. **Setup Development Environment**
   - Install React Native CLI
   - Setup Android Studio/Xcode
   - Configure device/emulator testing

2. **Create Project Structure**
   - Initialize both React Native projects
   - Setup navigation and state management
   - Configure API integration

3. **Start with Authentication**
   - Implement OTP-based login
   - JWT token management
   - User session handling

This plan provides a comprehensive roadmap for converting your Laravel e-commerce website into professional mobile applications ready for Google Play Store deployment.