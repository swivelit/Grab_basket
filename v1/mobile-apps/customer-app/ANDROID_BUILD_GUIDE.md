# 📱 GrabBaskets Android App - Build & Deployment Guide

## 🎯 Project Overview
**GrabBaskets** - Quick Grocery Delivery Android App
- **Package Name**: `com.grabbaskets.customer`
- **Version**: 1.0.0
- **Min SDK**: 21 (Android 5.0 Lollipop)
- **Target SDK**: 34 (Android 14)

---

## 🛠️ Prerequisites

### 1. Install Required Software
```bash
# Node.js 18 or higher
node --version

# Java JDK 17
java -version

# Android Studio (Latest version)
# Download from: https://developer.android.com/studio
```

### 2. Environment Setup
Add to your system environment variables:
```
ANDROID_HOME=C:\Users\<YourUsername>\AppData\Local\Android\Sdk
JAVA_HOME=C:\Program Files\Java\jdk-17

PATH=%PATH%;%ANDROID_HOME%\platform-tools;%ANDROID_HOME%\tools
```

---

## 📦 Installation Steps

### 1. Install Dependencies
```bash
cd "e:\New folder (3)\grabbaskets\mobile-apps\customer-app"

# Install Node modules
npm install
# OR
yarn install
```

### 2. Install Android SDK Components
Open Android Studio SDK Manager and install:
- Android SDK Platform 34
- Android SDK Build-Tools 34.0.0
- Android Emulator
- Google Play Services

---

## 🚀 Running the App

### Development Mode

#### Using Android Emulator
```bash
# Start Metro bundler
npm start

# In another terminal, run Android
npm run android
```

#### Using Physical Device
1. Enable **Developer Options** on your Android phone:
   - Go to Settings → About Phone
   - Tap "Build Number" 7 times
   
2. Enable **USB Debugging**:
   - Settings → Developer Options → USB Debugging

3. Connect phone via USB and run:
```bash
npm run android
```

---

## 🔨 Building APK for Testing

### Debug APK
```bash
cd android
./gradlew assembleDebug
```

Output: `android/app/build/outputs/apk/debug/app-debug.apk`

### Release APK (Not Signed)
```bash
cd android
./gradlew assembleRelease
```

---

## 🔐 Building Signed APK for Play Store

### 1. Generate Signing Key
```bash
cd android/app

keytool -genkeypair -v -storetype PKCS12 -keystore grabbaskets-release.keystore -alias grabbaskets -keyalg RSA -keysize 2048 -validity 10000
```

**Save this information securely:**
- Keystore password
- Key alias: grabbaskets
- Key password

### 2. Configure Signing in Android

Create `android/gradle.properties`:
```properties
GRABBASKETS_UPLOAD_STORE_FILE=grabbaskets-release.keystore
GRABBASKETS_UPLOAD_KEY_ALIAS=grabbaskets
GRABBASKETS_UPLOAD_STORE_PASSWORD=your_keystore_password
GRABBASKETS_UPLOAD_KEY_PASSWORD=your_key_password
```

Update `android/app/build.gradle`:
```gradle
android {
    ...
    signingConfigs {
        release {
            if (project.hasProperty('GRABBASKETS_UPLOAD_STORE_FILE')) {
                storeFile file(GRABBASKETS_UPLOAD_STORE_FILE)
                storePassword GRABBASKETS_UPLOAD_STORE_PASSWORD
                keyAlias GRABBASKETS_UPLOAD_KEY_ALIAS
                keyPassword GRABBASKETS_UPLOAD_KEY_PASSWORD
            }
        }
    }
    buildTypes {
        release {
            signingConfig signingConfigs.release
            ...
        }
    }
}
```

### 3. Build Signed APK
```bash
cd android
./gradlew assembleRelease
```

Output: `android/app/build/outputs/apk/release/app-release.apk`

---

## 📲 Building AAB for Play Store

### Generate Android App Bundle
```bash
cd android
./gradlew bundleRelease
```

Output: `android/app/build/outputs/bundle/release/app-release.aab`

---

## 🎨 App Assets Required

### 1. App Icon
- **Sizes**: 192x192, 144x144, 96x96, 72x72, 48x48
- **Location**: `android/app/src/main/res/mipmap-*/ic_launcher.png`

### 2. Splash Screen
- **Location**: `android/app/src/main/res/drawable/splash_screen.png`

### 3. Feature Graphic
- **Size**: 1024x500 pixels
- For Play Store listing

---

## 📝 Play Store Submission

### Required Assets
1. **App Icon**: 512x512 PNG
2. **Feature Graphic**: 1024x500 PNG
3. **Screenshots**: 
   - Phone: At least 2 (1080x1920 recommended)
   - 7" Tablet: At least 2
   - 10" Tablet: At least 2
4. **App Description** (Short & Full)
5. **Privacy Policy URL**
6. **Content Rating** questionnaire

### Steps
1. Create Developer Account ($25 one-time fee)
2. Create new app in Play Console
3. Upload AAB file
4. Fill in store listing
5. Complete content rating
6. Set up pricing & distribution
7. Submit for review

---

## 🔧 Troubleshooting

### Common Issues

#### 1. Metro Bundler Issues
```bash
# Clean cache
npm start -- --reset-cache
```

#### 2. Gradle Build Fails
```bash
cd android
./gradlew clean
cd ..
npm run android
```

#### 3. App Won't Install on Device
```bash
# Uninstall existing app first
adb uninstall com.grabbaskets.customer

# Then reinstall
npm run android
```

---

## 📊 App Performance Optimization

### 1. Enable Proguard (Already configured)
```gradle
buildTypes {
    release {
        minifyEnabled true
        shrinkResources true
        proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
    }
}
```

### 2. Enable Hermes Engine
Already enabled in `android/app/build.gradle`:
```gradle
project.ext.react = [
    enableHermes: true
]
```

### 3. Reduce APK Size
- Use vector drawables
- Compress images
- Enable code shrinking
- Use AAB instead of APK

---

## 🧪 Testing Checklist

- [ ] Login/Signup flow works
- [ ] OTP verification works
- [ ] Product browsing smooth
- [ ] Add to cart functional
- [ ] Checkout process complete
- [ ] Payment gateway works
- [ ] Order tracking functional
- [ ] Push notifications work
- [ ] Location permissions work
- [ ] Camera permissions work (profile photo)
- [ ] App works offline (cached data)
- [ ] No crashes or ANRs

---

## 🚀 Deployment Timeline

1. **Week 1**: Complete development & testing
2. **Week 2**: Internal testing & bug fixes
3. **Week 3**: Create Play Store assets & listing
4. **Week 4**: Submit to Play Store
5. **Review**: 7-14 days (Google review time)

---

## 📞 Support & Resources

- **React Native Docs**: https://reactnative.dev
- **Android Docs**: https://developer.android.com
- **Play Console**: https://play.google.com/console

---

## 🎉 Quick Commands Reference

```bash
# Install dependencies
npm install

# Start development
npm start
npm run android

# Clean build
cd android && ./gradlew clean && cd ..

# Build debug APK
npm run build-android

# Build release AAB
npm run build-android-bundle

# Check what's connected
adb devices

# View logs
npm run android -- --log-level info
```

---

**Ready to build your Android app!** 🚀

For any issues, check the troubleshooting section or React Native documentation.
