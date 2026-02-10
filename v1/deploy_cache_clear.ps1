# Clear Cache Script for Hostinger Deployment
# Upload this file and run it via browser: https://grabbaskets.com/clear_caches_hostinger.php

Write-Host "=== DEPLOYING CACHE CLEARING SCRIPT TO HOSTINGER ===" -ForegroundColor Cyan
Write-Host ""

# FTP Configuration - UPDATE THESE WITH YOUR HOSTINGER FTP CREDENTIALS
$ftpServer = "ftp.grabbaskets.com"  # Or your Hostinger FTP hostname
$ftpUsername = "YOUR_FTP_USERNAME"   # Replace with your FTP username
$ftpPassword = "YOUR_FTP_PASSWORD"   # Replace with your FTP password
$remotePath = "/public_html/clear_caches_hostinger.php"
$localFile = "clear_all_caches.php"

Write-Host "⚠️  IMPORTANT: Please update FTP credentials in this script" -ForegroundColor Yellow
Write-Host ""
Write-Host "MANUAL DEPLOYMENT INSTRUCTIONS:" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host ""
Write-Host "1. Upload 'clear_all_caches.php' to your Hostinger account" -ForegroundColor White
Write-Host "   Location: /public_html/clear_caches_hostinger.php" -ForegroundColor White
Write-Host ""
Write-Host "2. Visit in your browser:" -ForegroundColor White
Write-Host "   https://grabbaskets.com/clear_caches_hostinger.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. After running, delete the file from server for security" -ForegroundColor White
Write-Host ""
Write-Host "Alternative: Use Hostinger File Manager" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Yellow
Write-Host "1. Log into Hostinger control panel" -ForegroundColor White
Write-Host "2. Go to File Manager" -ForegroundColor White
Write-Host "3. Navigate to public_html/" -ForegroundColor White
Write-Host "4. Upload clear_all_caches.php" -ForegroundColor White
Write-Host "5. Rename it to clear_caches_hostinger.php" -ForegroundColor White
Write-Host "6. Visit the URL above" -ForegroundColor White
Write-Host ""
Write-Host "OR: Run via SSH if available" -ForegroundColor Yellow
Write-Host "============================" -ForegroundColor Yellow
Write-Host "ssh your_username@your_server.hostinger.com" -ForegroundColor White
Write-Host "cd public_html" -ForegroundColor White
Write-Host "php clear_all_caches.php" -ForegroundColor White
Write-Host ""
