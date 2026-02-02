# GrabBaskets Mobile Apps - Windows Deployment Script
# PowerShell script to build and deploy both Customer and Delivery Partner apps

param(
    [switch]$CustomerOnly,
    [switch]$DeliveryOnly,
    [switch]$BackendOnly,
    [switch]$Debug,
    [switch]$Upload,
    [switch]$Help
)

# Configuration
$CustomerAppDir = "mobile-apps\customer-app"
$DeliveryAppDir = "mobile-apps\delivery-partner-app"
$BuildType = if ($Debug) { "debug" } else { "release" }
$UploadToPlayStore = $Upload

# Function to print colored output
function Write-Status {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-ErrorMsg {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

# Function to check prerequisites
function Test-Prerequisites {
    Write-Status "Checking prerequisites..."
    
    # Check Node.js
    if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
        Write-ErrorMsg "Node.js is not installed"
        exit 1
    }
    
    # Check React Native CLI
    if (-not (Get-Command react-native -ErrorAction SilentlyContinue)) {
        Write-ErrorMsg "React Native CLI is not installed"
        exit 1
    }
    
    # Check Android SDK
    if (-not $env:ANDROID_HOME) {
        Write-ErrorMsg "ANDROID_HOME environment variable is not set"
        exit 1
    }
    
    # Check Java
    if (-not (Get-Command java -ErrorAction SilentlyContinue)) {
        Write-ErrorMsg "Java is not installed"
        exit 1
    }
    
    Write-Status "All prerequisites are met ✅"
}

# Function to install dependencies
function Install-Dependencies {
    param(
        [string]$AppDir,
        [string]$AppName
    )
    
    Write-Status "Installing dependencies for $AppName..."
    
    Push-Location $AppDir
    
    # Install Node.js dependencies
    if (Test-Path "yarn.lock") {
        yarn install
    } else {
        npm install
    }
    
    Pop-Location
    Write-Status "Dependencies installed for $AppName ✅"
}

# Function to build Android APK/AAB
function Build-Android {
    param(
        [string]$AppDir,
        [string]$AppName
    )
    
    Write-Status "Building Android $BuildType for $AppName..."
    
    Push-Location "$AppDir\android"
    
    # Clean previous builds
    .\gradlew.bat clean
    
    # Build APK
    $capitalizedBuildType = (Get-Culture).TextInfo.ToTitleCase($BuildType)
    .\gradlew.bat "assemble$capitalizedBuildType"
    
    # Build AAB (App Bundle) for Play Store
    if ($BuildType -eq "release") {
        .\gradlew.bat "bundle$capitalizedBuildType"
    }
    
    Pop-Location
    Write-Status "Android build completed for $AppName ✅"
}

# Function to run tests
function Invoke-Tests {
    param(
        [string]$AppDir,
        [string]$AppName
    )
    
    Write-Status "Running tests for $AppName..."
    
    Push-Location $AppDir
    
    # Run Jest tests
    if (Test-Path "yarn.lock") {
        yarn test --watchAll=false
    } else {
        npm test -- --watchAll=false
    }
    
    Pop-Location
    Write-Status "Tests completed for $AppName ✅"
}

# Function to create release notes
function New-ReleaseNotes {
    param(
        [string]$Version,
        [string]$AppName
    )
    
    $appNameLower = $AppName.ToLower() -replace " ", "-"
    $releaseNotesContent = @"
# $AppName v$Version Release Notes

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
Generated on $(Get-Date)
"@
    
    $releaseNotesFile = "release-notes-$appNameLower-$Version.md"
    $releaseNotesContent | Out-File -FilePath $releaseNotesFile -Encoding UTF8
    
    Write-Status "Release notes created for $AppName v$Version"
}

# Function to deploy Laravel backend
function Deploy-Backend {
    Write-Status "Deploying Laravel backend API..."
    
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
    $sanctumCheck = php artisan package:discover | Select-String "Laravel\\Sanctum"
    if (-not $sanctumCheck) {
        composer require laravel/sanctum
        php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
        php artisan migrate
    }
    
    Write-Status "Laravel backend deployed ✅"
}

# Function to validate app signing
function Test-AppSigning {
    param(
        [string]$AppDir,
        [string]$AppName
    )
    
    Write-Status "Validating app signing for $AppName..."
    
    $keystoreFile = "$AppDir\android\app\my-upload-key.keystore"
    $gradleProps = "$AppDir\android\gradle.properties"
    
    if (-not (Test-Path $keystoreFile)) {
        Write-Warning "Keystore file not found for $AppName"
        Write-Warning "Generate keystore with: keytool -genkeypair -v -storetype PKCS12 -keystore my-upload-key.keystore -alias my-key-alias -keyalg RSA -keysize 2048 -validity 10000"
        return $false
    }
    
    if (-not (Test-Path $gradleProps) -or -not (Select-String -Path $gradleProps -Pattern "MYAPP_UPLOAD_STORE_FILE")) {
        Write-Warning "Gradle properties not configured for signing in $AppName"
        return $false
    }
    
    Write-Status "App signing validation passed for $AppName ✅"
    return $true
}

# Function to generate build info
function New-BuildInfo {
    param(
        [string]$AppName,
        [string]$Version,
        [string]$BuildNumber
    )
    
    $gitCommit = ""
    $gitBranch = ""
    
    try {
        $gitCommit = git rev-parse HEAD
        $gitBranch = git rev-parse --abbrev-ref HEAD
    }
    catch {
        $gitCommit = "unknown"
        $gitBranch = "unknown"
    }
    
    $buildInfo = @{
        app_name = $AppName
        version = $Version
        build_number = $BuildNumber
        build_date = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
        git_commit = $gitCommit
        git_branch = $gitBranch
        build_type = $BuildType
    }
    
    $appNameLower = $AppName.ToLower() -replace " ", "-"
    $buildInfoFile = "build-info-$appNameLower.json"
    $buildInfo | ConvertTo-Json -Depth 3 | Out-File -FilePath $buildInfoFile -Encoding UTF8
    
    Write-Status "Build info generated for $AppName"
}

# Main deployment function
function Deploy-App {
    param(
        [string]$AppDir,
        [string]$AppName
    )
    
    Write-Status "Starting deployment for $AppName..."
    
    # Install dependencies
    Install-Dependencies -AppDir $AppDir -AppName $AppName
    
    # Run tests
    Invoke-Tests -AppDir $AppDir -AppName $AppName
    
    # Validate signing for release builds
    if ($BuildType -eq "release") {
        Test-AppSigning -AppDir $AppDir -AppName $AppName
    }
    
    # Build Android
    Build-Android -AppDir $AppDir -AppName $AppName
    
    # Generate build artifacts
    $packageJsonPath = "$AppDir\package.json"
    $packageJson = Get-Content $packageJsonPath | ConvertFrom-Json
    $version = $packageJson.version
    $buildNumber = (Get-Date).ToString("yyyyMMddHHmm")
    
    New-BuildInfo -AppName $AppName -Version $version -BuildNumber $buildNumber
    New-ReleaseNotes -Version $version -AppName $AppName
    
    Write-Status "$AppName deployment completed ✅"
}

# Function to show usage
function Show-Usage {
    Write-Host "Usage: .\deploy-mobile-apps.ps1 [OPTIONS]"
    Write-Host ""
    Write-Host "Options:"
    Write-Host "  -CustomerOnly     Deploy only Customer App"
    Write-Host "  -DeliveryOnly     Deploy only Delivery Partner App"
    Write-Host "  -BackendOnly      Deploy only Laravel backend"
    Write-Host "  -Debug           Build in debug mode"
    Write-Host "  -Upload          Upload to Play Store (requires additional setup)"
    Write-Host "  -Help            Show this help message"
    Write-Host ""
    Write-Host "Examples:"
    Write-Host "  .\deploy-mobile-apps.ps1                    # Deploy both apps"
    Write-Host "  .\deploy-mobile-apps.ps1 -CustomerOnly      # Deploy only customer app"
    Write-Host "  .\deploy-mobile-apps.ps1 -Debug            # Build in debug mode"
}

# Main execution
function Main {
    if ($Help) {
        Show-Usage
        return
    }
    
    Write-Status "🚀 Starting GrabBaskets Mobile Apps Deployment"
    Write-Status "Build type: $BuildType"
    
    # Check prerequisites
    Test-Prerequisites
    
    # Deploy backend if requested
    if ($BackendOnly) {
        Deploy-Backend
        return
    }
    
    # Deploy Customer App
    if ($CustomerOnly -or -not $DeliveryOnly) {
        if (Test-Path $CustomerAppDir) {
            Deploy-App -AppDir $CustomerAppDir -AppName "Customer App"
        } else {
            Write-ErrorMsg "Customer app directory not found: $CustomerAppDir"
            exit 1
        }
    }
    
    # Deploy Delivery Partner App
    if ($DeliveryOnly -or -not $CustomerOnly) {
        if (Test-Path $DeliveryAppDir) {
            Deploy-App -AppDir $DeliveryAppDir -AppName "Delivery Partner App"
        } else {
            Write-ErrorMsg "Delivery partner app directory not found: $DeliveryAppDir"
            exit 1
        }
    }
    
    # Deploy backend API
    if (-not $CustomerOnly -and -not $DeliveryOnly) {
        Deploy-Backend
    }
    
    Write-Status "🎉 All deployments completed successfully!"
    Write-Status "📱 Apps are ready for testing and Play Store submission"
    
    # Show next steps
    Write-Host ""
    Write-Host "Next Steps:"
    Write-Host "1. Test the built APKs on devices"
    Write-Host "2. Upload AAB files to Play Store Console"
    Write-Host "3. Update store listings with new screenshots"
    Write-Host "4. Submit for review"
    Write-Host ""
    
    # Show build locations
    Write-Host "Build Artifacts:"
    if ($CustomerOnly -or -not $DeliveryOnly) {
        Write-Host "📦 Customer App APK: $CustomerAppDir\android\app\build\outputs\apk\release\"
        Write-Host "📦 Customer App AAB: $CustomerAppDir\android\app\build\outputs\bundle\release\"
    }
    if ($DeliveryOnly -or -not $CustomerOnly) {
        Write-Host "📦 Delivery App APK: $DeliveryAppDir\android\app\build\outputs\apk\release\"
        Write-Host "📦 Delivery App AAB: $DeliveryAppDir\android\app\build\outputs\bundle\release\"
    }
}

# Run main function
Main