# Push Product Images to GitHub for CDN
# This script commits and pushes all product images to GitHub
# so they can be served via raw.githubusercontent.com

Write-Host "🚀 Pushing Product Images to GitHub CDN" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Check if storage/app/public exists
if (-not (Test-Path "storage/app/public")) {
    Write-Host "❌ storage/app/public folder not found!" -ForegroundColor Red
    exit 1
}

# Count images
$imageCount = (Get-ChildItem -Path "storage/app/public" -Recurse -File -Include *.jpg,*.jpeg,*.png,*.gif,*.webp).Count
Write-Host "📸 Found $imageCount images in storage/app/public" -ForegroundColor Green
Write-Host ""

# Add images to git
Write-Host "📦 Adding images to git..." -ForegroundColor Yellow
git add storage/app/public/

# Check if there are changes
$status = git status --porcelain storage/app/public/
if ([string]::IsNullOrWhiteSpace($status)) {
    Write-Host "✅ No new images to commit (already in GitHub)" -ForegroundColor Green
    Write-Host ""
    Write-Host "🌐 Images are accessible via:" -ForegroundColor Cyan
    Write-Host "   https://raw.githubusercontent.com/grabbaskets-hash/grabbaskets/main/storage/app/public/{image-path}" -ForegroundColor White
    exit 0
}

# Commit images
Write-Host "💾 Committing images..." -ForegroundColor Yellow
git commit -m "Add product images to GitHub CDN storage

- Total images: $imageCount
- Location: storage/app/public/
- Purpose: Serve images via GitHub raw CDN
- Backup: Laravel Cloud storage (AWS S3-compatible)"

# Push to GitHub
Write-Host "⬆️  Pushing to GitHub..." -ForegroundColor Yellow
git push origin main

Write-Host ""
Write-Host "✅ SUCCESS! Images pushed to GitHub" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 Your images are now accessible via:" -ForegroundColor Cyan
Write-Host "   https://raw.githubusercontent.com/grabbaskets-hash/grabbaskets/main/storage/app/public/{image-path}" -ForegroundColor White
Write-Host ""
Write-Host "📝 Example URLs:" -ForegroundColor Cyan
Get-ChildItem -Path "storage/app/public/products" -File -Include *.jpg,*.jpeg,*.png | Select-Object -First 3 | ForEach-Object {
    $relativePath = $_.FullName -replace [regex]::Escape((Get-Location).Path + "\storage\app\public\"), ""
    $relativePath = $relativePath -replace '\\', '/'
    Write-Host "   https://raw.githubusercontent.com/grabbaskets-hash/grabbaskets/main/storage/app/public/$relativePath" -ForegroundColor White
}
Write-Host ""
Write-Host "Done! Clear your Laravel Cloud cache" -ForegroundColor Green

