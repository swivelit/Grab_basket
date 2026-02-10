# PowerShell deployment script for Laravel application
Write-Host "Starting deployment..." -ForegroundColor Green

# Create necessary directories
Write-Host "Creating storage directories..." -ForegroundColor Yellow

$directories = @(
    "storage\framework\views",
    "storage\framework\cache\data", 
    "storage\framework\sessions",
    "storage\logs",
    "bootstrap\cache"
)

foreach ($dir in $directories) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "Created: $dir" -ForegroundColor Green
    } else {
        Write-Host "Exists: $dir" -ForegroundColor Cyan
    }
}

# Clear and cache configurations
Write-Host "Clearing and caching configurations..." -ForegroundColor Yellow
try {
    php artisan config:clear
    php artisan config:cache
    Write-Host "Config cached successfully" -ForegroundColor Green
} catch {
    Write-Host "Error with config commands: $_" -ForegroundColor Red
}

# Clear route cache
Write-Host "Clearing routes..." -ForegroundColor Yellow
try {
    php artisan route:clear
    Write-Host "Routes cleared successfully" -ForegroundColor Green
} catch {
    Write-Host "Error clearing routes: $_" -ForegroundColor Red
}

# Clear application cache
Write-Host "Clearing application cache..." -ForegroundColor Yellow
try {
    php artisan cache:clear
    Write-Host "Application cache cleared successfully" -ForegroundColor Green
} catch {
    Write-Host "Error clearing application cache: $_" -ForegroundColor Red
}

# Clear view cache (only if storage/framework/views exists)
if (Test-Path "storage\framework\views") {
    Write-Host "Clearing view cache..." -ForegroundColor Yellow
    try {
        php artisan view:clear
        Write-Host "View cache cleared successfully" -ForegroundColor Green
    } catch {
        Write-Host "Error clearing view cache: $_" -ForegroundColor Red
    }
} else {
    Write-Host "View cache directory doesn't exist, skipping view:clear" -ForegroundColor Yellow
}

# Install dependencies (production)
Write-Host "Installing dependencies..." -ForegroundColor Yellow
try {
    composer install --optimize-autoloader --no-dev
    Write-Host "Dependencies installed successfully" -ForegroundColor Green
} catch {
    Write-Host "Error installing dependencies: $_" -ForegroundColor Red
}

# Generate application key if not exists
$envContent = Get-Content .env -Raw
if ($envContent -match "APP_KEY=\s*$") {
    Write-Host "Generating application key..." -ForegroundColor Yellow
    try {
        php artisan key:generate
        Write-Host "Application key generated successfully" -ForegroundColor Green
    } catch {
        Write-Host "Error generating application key: $_" -ForegroundColor Red
    }
} else {
    Write-Host "Application key already exists" -ForegroundColor Cyan
}

Write-Host "Deployment completed successfully!" -ForegroundColor Green
Write-Host "Press any key to continue..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")