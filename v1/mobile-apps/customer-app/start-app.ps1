# GrabBaskets Android App - Quick Start Script
# Run this to set up and start your Android app

Write-Host "🚀 GrabBaskets Android App - Quick Start" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Navigate to app directory
$appDir = "e:\New folder (3)\grabbaskets\mobile-apps\customer-app"
Set-Location $appDir

Write-Host "📂 Current Directory: $appDir" -ForegroundColor Yellow
Write-Host ""

# Check if node_modules exists
if (Test-Path "node_modules") {
    Write-Host "✅ Dependencies already installed" -ForegroundColor Green
} else {
    Write-Host "📦 Installing dependencies..." -ForegroundColor Yellow
    npm install
    Write-Host "✅ Dependencies installed!" -ForegroundColor Green
}

Write-Host ""
Write-Host "🎯 Choose an option:" -ForegroundColor Cyan
Write-Host "1. Run on Android Emulator/Device" -ForegroundColor White
Write-Host "2. Build Debug APK" -ForegroundColor White
Write-Host "3. Build Release AAB" -ForegroundColor White
Write-Host "4. Clean Build" -ForegroundColor White
Write-Host "5. View Documentation" -ForegroundColor White
Write-Host ""

$choice = Read-Host "Enter your choice (1-5)"

switch ($choice) {
    "1" {
        Write-Host ""
        Write-Host "🚀 Starting Android App..." -ForegroundColor Green
        Write-Host ""
        Write-Host "This will:" -ForegroundColor Yellow
        Write-Host "  1. Start Metro bundler" -ForegroundColor White
        Write-Host "  2. Build and install app on connected device/emulator" -ForegroundColor White
        Write-Host ""
        Write-Host "Make sure you have:" -ForegroundColor Yellow
        Write-Host "  ✓ Android device connected with USB debugging enabled" -ForegroundColor White
        Write-Host "  ✓ OR Android emulator running" -ForegroundColor White
        Write-Host ""
        
        # Check connected devices
        Write-Host "Checking connected devices..." -ForegroundColor Yellow
        adb devices
        Write-Host ""
        
        $continue = Read-Host "Continue? (Y/n)"
        if ($continue -ne "n") {
            npm run android
        }
    }
    
    "2" {
        Write-Host ""
        Write-Host "🔨 Building Debug APK..." -ForegroundColor Green
        Write-Host ""
        Set-Location android
        .\gradlew assembleDebug
        Set-Location ..
        Write-Host ""
        Write-Host "✅ Debug APK built successfully!" -ForegroundColor Green
        Write-Host "📍 Location: android\app\build\outputs\apk\debug\app-debug.apk" -ForegroundColor Cyan
    }
    
    "3" {
        Write-Host ""
        Write-Host "🔨 Building Release AAB..." -ForegroundColor Green
        Write-Host ""
        Write-Host "⚠️  Make sure you have configured signing keys!" -ForegroundColor Yellow
        Write-Host "See ANDROID_BUILD_GUIDE.md for instructions" -ForegroundColor Yellow
        Write-Host ""
        $continue = Read-Host "Continue? (Y/n)"
        if ($continue -ne "n") {
            Set-Location android
            .\gradlew bundleRelease
            Set-Location ..
            Write-Host ""
            Write-Host "✅ Release AAB built successfully!" -ForegroundColor Green
            Write-Host "📍 Location: android\app\build\outputs\bundle\release\app-release.aab" -ForegroundColor Cyan
        }
    }
    
    "4" {
        Write-Host ""
        Write-Host "🧹 Cleaning build..." -ForegroundColor Green
        Write-Host ""
        Set-Location android
        .\gradlew clean
        Set-Location ..
        Write-Host ""
        Write-Host "✅ Build cleaned!" -ForegroundColor Green
        Write-Host "You can now run: npm run android" -ForegroundColor Cyan
    }
    
    "5" {
        Write-Host ""
        Write-Host "📚 Opening Documentation..." -ForegroundColor Green
        Write-Host ""
        Write-Host "Available Guides:" -ForegroundColor Cyan
        Write-Host "  1. README.md - Complete setup guide" -ForegroundColor White
        Write-Host "  2. ANDROID_BUILD_GUIDE.md - Build and deployment" -ForegroundColor White
        Write-Host "  3. ANDROID_APP_COMPLETE.md - Feature overview" -ForegroundColor White
        Write-Host ""
        
        # Open main README
        if (Test-Path "README.md") {
            Start-Process "README.md"
        }
    }
    
    default {
        Write-Host ""
        Write-Host "❌ Invalid choice" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "📝 Useful Commands:" -ForegroundColor Cyan
Write-Host "  npm start           - Start Metro bundler" -ForegroundColor White
Write-Host "  npm run android     - Run on Android" -ForegroundColor White
Write-Host "  adb devices         - List connected devices" -ForegroundColor White
Write-Host "  npm run clean       - Clean cache" -ForegroundColor White
Write-Host ""
Write-Host "📖 Documentation: See README.md and ANDROID_BUILD_GUIDE.md" -ForegroundColor Cyan
Write-Host ""
Write-Host "🎉 Happy Coding!" -ForegroundColor Green
