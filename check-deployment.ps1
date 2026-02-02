Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║         CHECKING DEPLOYMENT STATUS (grabbaskets.com)      ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

Write-Host "🔍 Testing if new code is deployed..." -ForegroundColor Yellow
Write-Host ""

# Test the diagnostic endpoint on the LIVE domain
$url = "https://grabbaskets.com/debug/image-display-test"
Write-Host "Testing URL: $url" -ForegroundColor Gray

try {
    $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 10
    $content = $response.Content | ConvertFrom-Json
    
    Write-Host ""
    Write-Host "═══════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "           DEPLOYMENT STATUS RESULT             " -ForegroundColor Cyan
    Write-Host "═══════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host ""
    
    # Check if we got a valid JSON response with expected keys
    if ($content.status -eq "Testing basic response" -and $content.app_url) {
        Write-Host "✅ SUCCESS! LIVE SITE IS RUNNING NEW CODE!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Status: $($content.status)" -ForegroundColor White
        Write-Host "App URL: $($content.app_url)" -ForegroundColor White
        Write-Host "Product Count: $($content.product_count)" -ForegroundColor White
        Write-Host ""
        Write-Host "The fix is live on production!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Next steps:" -ForegroundColor Yellow
        Write-Host "1. Visit: https://grabbaskets.com/seller/dashboard" -ForegroundColor White
        Write-Host "2. Images should now display correctly! ✅" -ForegroundColor Green
        Write-Host ""
        
    } else {
        Write-Host "⚠️  UNEXPECTED RESPONSE" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "The page loaded but returned unexpected data." -ForegroundColor Yellow
        Write-Host "Response: $($response.Content)" -ForegroundColor Gray
    }
    
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    
    if ($statusCode -eq 404) {
        Write-Host "❌ DIAGNOSTIC ROUTE NOT FOUND (404)" -ForegroundColor Red
        Write-Host ""
        Write-Host "This might mean the deployment hasn't finished yet or failed." -ForegroundColor Red
        Write-Host ""
    } else {
        Write-Host "❌ ERROR: HTTP $statusCode" -ForegroundColor Red
        Write-Host ""
        Write-Host "Could not access the diagnostic page." -ForegroundColor Yellow
        Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
