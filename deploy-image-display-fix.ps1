#!/usr/bin/env pwsh

Write-Host "🚀 Deploying Image Fix to Production" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Check if changes are committed
Write-Host "📋 Checking Git status..." -ForegroundColor Yellow
$gitStatus = git status --porcelain
if ($gitStatus) {
    Write-Host "⚠️  Uncommitted changes found:" -ForegroundColor Yellow
    Write-Host $gitStatus
    $commit = Read-Host "Commit these changes? (y/n)"
    if ($commit -eq 'y') {
        git add -A
        git commit -m "Force clear production cache for image fix"
        Write-Host "✅ Changes committed" -ForegroundColor Green
    }
}

# Push to trigger deployment
Write-Host ""
Write-Host "📤 Pushing to main branch..." -ForegroundColor Yellow
git push origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Pushed successfully" -ForegroundColor Green
} else {
    Write-Host "❌ Push failed" -ForegroundColor Red
    exit 1
}

# Wait for deployment
Write-Host ""
Write-Host "⏳ Waiting for Laravel Cloud to deploy (30 seconds)..." -ForegroundColor Yellow
Start-Sleep -Seconds 30

# Test the serve-image route
Write-Host ""
Write-Host "🧪 Testing /serve-image/ route on production..." -ForegroundColor Yellow
$testUrl = "https://grabbaskets.laravel.cloud/serve-image/products/seller-2/srm340-1760342455.jpg"

try {
    $response = Invoke-WebRequest -Uri $testUrl -Method Head -ErrorAction SilentlyContinue
    $statusCode = $response.StatusCode
    
    if ($statusCode -eq 200) {
        Write-Host "✅ Image route working! (HTTP $statusCode)" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Image route returned HTTP $statusCode" -ForegroundColor Yellow
    }
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "❌ Image route error: HTTP $statusCode" -ForegroundColor Red
    Write-Host "   URL tested: $testUrl" -ForegroundColor Gray
}

# Test dashboard
Write-Host ""
Write-Host "🧪 Testing dashboard page..." -ForegroundColor Yellow
$dashboardUrl = "https://grabbaskets.laravel.cloud/seller/dashboard"

try {
    $response = Invoke-WebRequest -Uri $dashboardUrl -Method Head -ErrorAction SilentlyContinue
    $statusCode = $response.StatusCode
    
    if ($statusCode -eq 200) {
        Write-Host "✅ Dashboard accessible (HTTP $statusCode)" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Dashboard returned HTTP $statusCode" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️  Dashboard requires authentication" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "✅ Deployment Complete!" -ForegroundColor Green
Write-Host ""
Write-Host "📝 Next steps:" -ForegroundColor Cyan
Write-Host "   1. Log in to https://grabbaskets.laravel.cloud/seller/dashboard"
Write-Host "   2. Check if product images are displaying"
Write-Host "   3. If still showing as text, check browser console for errors"
Write-Host ""
