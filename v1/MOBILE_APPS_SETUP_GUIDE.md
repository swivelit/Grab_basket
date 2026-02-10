# Mobile Apps Setup & Deployment Guide

## Prerequisites

### Development Environment
1. **Node.js** (v18 or higher)
2. **React Native CLI**: `npm install -g react-native-cli`
3. **Android Studio** with Android SDK
4. **Java JDK** (v11 or higher)
5. **Gradle** (latest version)

### For Customer App
```bash
cd mobile-apps/customer-app
npm install
# or
yarn install
```

### For Delivery Partner App
```bash
cd mobile-apps/delivery-partner-app
npm install
# or
yarn install
```

## Development Setup

### 1. Environment Configuration

Create `.env` files in both app directories:

#### Customer App `.env`
```
API_BASE_URL=http://10.0.2.2:8000/api/v1
GOOGLE_MAPS_API_KEY=your_google_maps_api_key
RAZORPAY_KEY_ID=your_razorpay_key_id
FIREBASE_PROJECT_ID=your_firebase_project_id
```

#### Delivery Partner App `.env`
```
API_BASE_URL=http://10.0.2.2:8000/api/v1
GOOGLE_MAPS_API_KEY=your_google_maps_api_key
FIREBASE_PROJECT_ID=your_firebase_project_id
```

### 2. Android Configuration

#### Update `android/app/build.gradle` for both apps:

```gradle
android {
    compileSdkVersion 34
    buildToolsVersion "34.0.0"

    defaultConfig {
        applicationId "com.grabbaskets.customer" // or "com.grabbaskets.delivery"
        minSdkVersion 21
        targetSdkVersion 34
        versionCode 1
        versionName "1.0.0"
        multiDexEnabled true
    }

    signingConfigs {
        release {
            if (project.hasProperty('MYAPP_UPLOAD_STORE_FILE')) {
                storeFile file(MYAPP_UPLOAD_STORE_FILE)
                storePassword MYAPP_UPLOAD_STORE_PASSWORD
                keyAlias MYAPP_UPLOAD_KEY_ALIAS
                keyPassword MYAPP_UPLOAD_KEY_PASSWORD
            }
        }
    }

    buildTypes {
        debug {
            signingConfig signingConfigs.debug
        }
        release {
            minifyEnabled true
            proguardFiles getDefaultProguardFile("proguard-android.txt"), "proguard-rules.pro"
            signingConfig signingConfigs.release
        }
    }
}
```

### 3. Laravel API Setup

#### Install Laravel Sanctum for API authentication:
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

#### Update `config/sanctum.php`:
```php
'guards' => ['web', 'api', 'delivery_partner'],
'expiration' => 525600, // 1 year
```

## Build Process

### Development Build

#### Customer App
```bash
cd mobile-apps/customer-app
npx react-native run-android
# For iOS
npx react-native run-ios
```

#### Delivery Partner App
```bash
cd mobile-apps/delivery-partner-app
npx react-native run-android
# For iOS
npx react-native run-ios
```

### Production Build

#### 1. Generate Keystore (Android)
```bash
keytool -genkeypair -v -storetype PKCS12 -keystore grabbaskets-customer.keystore -alias grabbaskets-customer -keyalg RSA -keysize 2048 -validity 10000

keytool -genkeypair -v -storetype PKCS12 -keystore grabbaskets-delivery.keystore -alias grabbaskets-delivery -keyalg RSA -keysize 2048 -validity 10000
```

#### 2. Configure Gradle Properties
Create `android/gradle.properties` for each app:

```properties
MYAPP_UPLOAD_STORE_FILE=grabbaskets-customer.keystore
MYAPP_UPLOAD_KEY_ALIAS=grabbaskets-customer
MYAPP_UPLOAD_STORE_PASSWORD=your_keystore_password
MYAPP_UPLOAD_KEY_PASSWORD=your_key_password
```

#### 3. Build APK/AAB
```bash
# Customer App
cd mobile-apps/customer-app/android
./gradlew assembleRelease
./gradlew bundleRelease

# Delivery Partner App
cd mobile-apps/delivery-partner-app/android
./gradlew assembleRelease
./gradlew bundleRelease
```

## Play Store Deployment

### App Store Assets

#### Customer App: "GrabBaskets - Quick Grocery Delivery"
- **Package Name**: `com.grabbaskets.customer`
- **App Name**: GrabBaskets
- **Short Description**: Quick grocery delivery in 10 minutes
- **Category**: Shopping

#### Delivery Partner App: "GrabBaskets Delivery Partner"
- **Package Name**: `com.grabbaskets.delivery`
- **App Name**: GrabBaskets Delivery
- **Short Description**: Earn money by delivering groceries
- **Category**: Business

### Required Assets
1. **App Icon**: 512x512px (PNG)
2. **Screenshots**: 
   - Phone: 1080x1920px (minimum 2, maximum 8)
   - 7-inch tablet: 1200x1920px (optional)
   - 10-inch tablet: 1600x2560px (optional)
3. **Feature Graphic**: 1024x500px
4. **Privacy Policy URL**
5. **Terms of Service URL**

### Store Listing Content

#### Customer App
```
Short Description:
Get groceries delivered in 10 minutes! Fresh products, best prices, quick delivery.

Full Description:
🛒 GrabBaskets - Your Ultimate Grocery Delivery App

✨ Features:
• 10-minute quick delivery
• Fresh groceries & daily essentials
• Wide range of categories
• Multiple payment options
• Real-time order tracking
• Wishlist & favorites
• User-friendly interface

🚀 Why Choose GrabBaskets?
• Lightning-fast delivery
• Quality products from trusted sellers
• Competitive prices
• Secure payments
• 24/7 customer support

📱 Easy to Use:
1. Browse products by category
2. Add items to cart
3. Choose delivery time
4. Track your order live
5. Enjoy fresh groceries!

Download now and experience the fastest grocery delivery service!
```

#### Delivery Partner App
```
Short Description:
Become a delivery partner and earn ₹25+ per delivery. Flexible hours, instant payments.

Full Description:
💰 GrabBaskets Delivery Partner - Start Earning Today!

🔥 Earning Potential:
• ₹25+ per delivery
• Weekly/daily payouts
• Performance bonuses
• Flexible working hours

📱 Partner Benefits:
• Easy registration process
• Real-time order notifications
• GPS navigation support
• Earnings dashboard
• 24/7 partner support
• Instant payment processing

🚀 How It Works:
1. Register as delivery partner
2. Upload required documents
3. Go online to receive orders
4. Pick up from store
5. Deliver to customer
6. Earn instantly!

📋 Requirements:
• Valid driving license
• Own vehicle (bike/scooter)
• Smartphone
• Age 18+

Join thousands of delivery partners earning with GrabBaskets!
```

### Content Rating
- **Customer App**: Everyone 3+
- **Delivery Partner App**: Teen (work-related app)

### App Permissions
#### Customer App
- INTERNET
- ACCESS_FINE_LOCATION
- ACCESS_COARSE_LOCATION
- CAMERA (for profile photo)
- READ_EXTERNAL_STORAGE
- WRITE_EXTERNAL_STORAGE

#### Delivery Partner App
- INTERNET
- ACCESS_FINE_LOCATION
- ACCESS_COARSE_LOCATION
- CAMERA (for delivery proof)
- READ_EXTERNAL_STORAGE
- WRITE_EXTERNAL_STORAGE
- FOREGROUND_SERVICE (for location tracking)

## Release Checklist

### Pre-Release
- [ ] All API endpoints tested
- [ ] App icons and splash screens finalized
- [ ] Google Maps API key configured
- [ ] Firebase project setup complete
- [ ] Payment gateway integration tested
- [ ] Push notifications working
- [ ] App performance optimized
- [ ] Security vulnerabilities addressed

### Play Store Submission
- [ ] APK/AAB files generated and signed
- [ ] Store listing content ready
- [ ] Screenshots captured
- [ ] Privacy policy uploaded
- [ ] Terms of service available
- [ ] Content rating completed
- [ ] Pricing and distribution set
- [ ] App bundle uploaded
- [ ] Internal testing completed

### Post-Release
- [ ] Monitor crash reports
- [ ] Track user feedback
- [ ] Monitor app performance
- [ ] Setup analytics tracking
- [ ] Plan feature updates
- [ ] Setup automated deployments

## Troubleshooting

### Common Build Issues
1. **Metro bundler issues**: Clear cache with `npx react-native start --reset-cache`
2. **Android build fails**: Check SDK versions and clean build with `./gradlew clean`
3. **iOS build fails**: Update CocoaPods with `cd ios && pod install`

### API Integration Issues
1. **CORS errors**: Update Laravel CORS configuration
2. **Authentication fails**: Check Sanctum middleware setup
3. **Image uploads fail**: Verify storage disk configuration

This comprehensive setup guide ensures smooth development and deployment of both mobile applications to the Google Play Store.