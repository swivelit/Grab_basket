#!/bin/bash

# GrabBaskets Mobile Apps - Deployment Script
# This script automates the build and deployment process for both Customer and Delivery Partner apps

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
CUSTOMER_APP_DIR="mobile-apps/customer-app"
DELIVERY_APP_DIR="mobile-apps/delivery-partner-app"
BUILD_TYPE="release"
UPLOAD_TO_PLAY_STORE=false

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check Node.js
    if ! command -v node &> /dev/null; then
        print_error "Node.js is not installed"
        exit 1
    fi
    
    # Check React Native CLI
    if ! command -v react-native &> /dev/null; then
        print_error "React Native CLI is not installed"
        exit 1
    fi
    
    # Check Android SDK
    if [ -z "$ANDROID_HOME" ]; then
        print_error "ANDROID_HOME environment variable is not set"
        exit 1
    fi
    
    # Check Java
    if ! command -v java &> /dev/null; then
        print_error "Java is not installed"
        exit 1
    fi
    
    print_status "All prerequisites are met ✅"
}

# Function to install dependencies
install_dependencies() {
    local app_dir=$1
    local app_name=$2
    
    print_status "Installing dependencies for $app_name..."
    
    cd $app_dir
    
    # Install Node.js dependencies
    if [ -f "yarn.lock" ]; then
        yarn install
    else
        npm install
    fi
    
    # Install iOS dependencies (if on macOS)
    if [[ "$OSTYPE" == "darwin"* ]]; then
        if [ -d "ios" ]; then
            cd ios
            pod install
            cd ..
        fi
    fi
    
    cd - > /dev/null
    print_status "Dependencies installed for $app_name ✅"
}

# Function to build Android APK/AAB
build_android() {
    local app_dir=$1
    local app_name=$2
    
    print_status "Building Android $BUILD_TYPE for $app_name..."
    
    cd $app_dir/android
    
    # Clean previous builds
    ./gradlew clean
    
    # Build APK
    ./gradlew assemble${BUILD_TYPE^}
    
    # Build AAB (App Bundle) for Play Store
    if [ "$BUILD_TYPE" = "release" ]; then
        ./gradlew bundle${BUILD_TYPE^}
    fi
    
    cd - > /dev/null
    print_status "Android build completed for $app_name ✅"
}

# Function to build iOS (macOS only)
build_ios() {
    local app_dir=$1
    local app_name=$2
    
    if [[ "$OSTYPE" != "darwin"* ]]; then
        print_warning "iOS build skipped (not running on macOS)"
        return
    fi
    
    print_status "Building iOS for $app_name..."
    
    cd $app_dir
    
    # Build iOS
    react-native run-ios --configuration Release
    
    cd - > /dev/null
    print_status "iOS build completed for $app_name ✅"
}

# Function to run tests
run_tests() {
    local app_dir=$1
    local app_name=$2
    
    print_status "Running tests for $app_name..."
    
    cd $app_dir
    
    # Run Jest tests
    if [ -f "yarn.lock" ]; then
        yarn test --watchAll=false
    else
        npm test -- --watchAll=false
    fi
    
    cd - > /dev/null
    print_status "Tests completed for $app_name ✅"
}

# Function to create release notes
create_release_notes() {
    local version=$1
    local app_name=$2
    
    cat > "release-notes-${app_name,,}-${version}.md" << EOF
# $app_name v$version Release Notes

## New Features
- Feature 1
- Feature 2

## Bug Fixes
- Bug fix 1
- Bug fix 2

## Improvements
- Performance improvements
- UI/UX enhancements

## Technical Changes
- Updated dependencies
- Security improvements

---
Generated on $(date)
EOF
    
    print_status "Release notes created for $app_name v$version"
}

# Function to deploy to Laravel backend
deploy_backend() {
    print_status "Deploying Laravel backend API..."
    
    # Install PHP dependencies
    composer install --no-dev --optimize-autoloader
    
    # Run database migrations
    php artisan migrate --force
    
    # Clear and cache config
    php artisan config:clear
    php artisan config:cache
    
    # Clear and cache routes
    php artisan route:clear
    php artisan route:cache
    
    # Clear and cache views
    php artisan view:clear
    php artisan view:cache
    
    # Install Sanctum if not already installed
    if ! php artisan package:discover | grep -q "Laravel\\\Sanctum"; then
        composer require laravel/sanctum
        php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
        php artisan migrate
    fi
    
    print_status "Laravel backend deployed ✅"
}

# Function to validate app signing
validate_signing() {
    local app_dir=$1
    local app_name=$2
    
    print_status "Validating app signing for $app_name..."
    
    local keystore_file="$app_dir/android/app/my-upload-key.keystore"
    local gradle_props="$app_dir/android/gradle.properties"
    
    if [ ! -f "$keystore_file" ]; then
        print_warning "Keystore file not found for $app_name"
        print_warning "Generate keystore with: keytool -genkeypair -v -storetype PKCS12 -keystore my-upload-key.keystore -alias my-key-alias -keyalg RSA -keysize 2048 -validity 10000"
        return 1
    fi
    
    if [ ! -f "$gradle_props" ] || ! grep -q "MYAPP_UPLOAD_STORE_FILE" "$gradle_props"; then
        print_warning "Gradle properties not configured for signing in $app_name"
        return 1
    fi
    
    print_status "App signing validation passed for $app_name ✅"
}

# Function to generate build info
generate_build_info() {
    local app_name=$1
    local version=$2
    local build_number=$3
    
    cat > "build-info-${app_name,,}.json" << EOF
{
  "app_name": "$app_name",
  "version": "$version",
  "build_number": "$build_number",
  "build_date": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "git_commit": "$(git rev-parse HEAD)",
  "git_branch": "$(git rev-parse --abbrev-ref HEAD)",
  "build_type": "$BUILD_TYPE"
}
EOF
    
    print_status "Build info generated for $app_name"
}

# Main deployment function
deploy_app() {
    local app_dir=$1
    local app_name=$2
    
    print_status "Starting deployment for $app_name..."
    
    # Install dependencies
    install_dependencies $app_dir $app_name
    
    # Run tests
    run_tests $app_dir $app_name
    
    # Validate signing for release builds
    if [ "$BUILD_TYPE" = "release" ]; then
        validate_signing $app_dir $app_name
    fi
    
    # Build Android
    build_android $app_dir $app_name
    
    # Build iOS (macOS only)
    build_ios $app_dir $app_name
    
    # Generate build artifacts
    local version=$(grep -o '"version": "[^"]*"' $app_dir/package.json | cut -d'"' -f4)
    local build_number=$(date +%Y%m%d%H%M)
    
    generate_build_info $app_name $version $build_number
    create_release_notes $version $app_name
    
    print_status "$app_name deployment completed ✅"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --customer-only    Deploy only Customer App"
    echo "  --delivery-only    Deploy only Delivery Partner App"
    echo "  --backend-only     Deploy only Laravel backend"
    echo "  --debug           Build in debug mode"
    echo "  --upload          Upload to Play Store (requires additional setup)"
    echo "  --help            Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                    # Deploy both apps"
    echo "  $0 --customer-only    # Deploy only customer app"
    echo "  $0 --debug           # Build in debug mode"
}

# Parse command line arguments
CUSTOMER_ONLY=false
DELIVERY_ONLY=false
BACKEND_ONLY=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --customer-only)
            CUSTOMER_ONLY=true
            shift
            ;;
        --delivery-only)
            DELIVERY_ONLY=true
            shift
            ;;
        --backend-only)
            BACKEND_ONLY=true
            shift
            ;;
        --debug)
            BUILD_TYPE="debug"
            shift
            ;;
        --upload)
            UPLOAD_TO_PLAY_STORE=true
            shift
            ;;
        --help)
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Main execution
main() {
    print_status "🚀 Starting GrabBaskets Mobile Apps Deployment"
    print_status "Build type: $BUILD_TYPE"
    
    # Check prerequisites
    check_prerequisites
    
    # Deploy backend if requested
    if [ "$BACKEND_ONLY" = true ]; then
        deploy_backend
        exit 0
    fi
    
    # Deploy Customer App
    if [ "$CUSTOMER_ONLY" = true ] || [ "$DELIVERY_ONLY" = false ]; then
        if [ -d "$CUSTOMER_APP_DIR" ]; then
            deploy_app $CUSTOMER_APP_DIR "Customer App"
        else
            print_error "Customer app directory not found: $CUSTOMER_APP_DIR"
            exit 1
        fi
    fi
    
    # Deploy Delivery Partner App
    if [ "$DELIVERY_ONLY" = true ] || [ "$CUSTOMER_ONLY" = false ]; then
        if [ -d "$DELIVERY_APP_DIR" ]; then
            deploy_app $DELIVERY_APP_DIR "Delivery Partner App"
        else
            print_error "Delivery partner app directory not found: $DELIVERY_APP_DIR"
            exit 1
        fi
    fi
    
    # Deploy backend API
    if [ "$CUSTOMER_ONLY" = false ] && [ "$DELIVERY_ONLY" = false ]; then
        deploy_backend
    fi
    
    print_status "🎉 All deployments completed successfully!"
    print_status "📱 Apps are ready for testing and Play Store submission"
    
    # Show next steps
    echo ""
    echo "Next Steps:"
    echo "1. Test the built APKs on devices"
    echo "2. Upload AAB files to Play Store Console"
    echo "3. Update store listings with new screenshots"
    echo "4. Submit for review"
    echo ""
    
    # Show build locations
    echo "Build Artifacts:"
    if [ "$CUSTOMER_ONLY" = true ] || [ "$DELIVERY_ONLY" = false ]; then
        echo "📦 Customer App APK: $CUSTOMER_APP_DIR/android/app/build/outputs/apk/release/"
        echo "📦 Customer App AAB: $CUSTOMER_APP_DIR/android/app/build/outputs/bundle/release/"
    fi
    if [ "$DELIVERY_ONLY" = true ] || [ "$CUSTOMER_ONLY" = false ]; then
        echo "📦 Delivery App APK: $DELIVERY_APP_DIR/android/app/build/outputs/apk/release/"
        echo "📦 Delivery App AAB: $DELIVERY_APP_DIR/android/app/build/outputs/bundle/release/"
    fi
}

# Run main function
main