# Git Commit and Push Latest Changes
# This will update your Hostinger deployment

Write-Host "=== GIT COMMIT & PUSH TO HOSTINGER ===" -ForegroundColor Cyan
Write-Host ""

# Change to project directory
Set-Location "E:\e-com_updated_final\e-com_updated"

# Check git status
Write-Host "Checking git status..." -ForegroundColor Yellow
git status

Write-Host ""
Write-Host "Adding all changes..." -ForegroundColor Yellow
git add .

Write-Host ""
Write-Host "Creating commit..." -ForegroundColor Yellow
$commitMessage = "Fix: Update APP_URL and clear caches for grabbaskets.com domain"
git commit -m $commitMessage

Write-Host ""
Write-Host "Pushing to GitHub..." -ForegroundColor Yellow
git push origin main

Write-Host ""
Write-Host "=== DEPLOYMENT SUCCESSFUL ===" -ForegroundColor Green
Write-Host ""
Write-Host "NEXT STEPS:" -ForegroundColor Cyan
Write-Host "1. Go to Hostinger control panel" -ForegroundColor White
Write-Host "2. Pull latest changes (if auto-deploy is not enabled)" -ForegroundColor White
Write-Host "3. Upload and run: clear_caches_hostinger.php" -ForegroundColor White
Write-Host "   URL: https://grabbaskets.com/clear_caches_hostinger.php" -ForegroundColor Yellow
Write-Host ""
Write-Host "This will fix:" -ForegroundColor Green
Write-Host "- Category 24 (500 error)" -ForegroundColor White
Write-Host "- All URLs showing grabbaskets.laravel.cloud" -ForegroundColor White
Write-Host ""
