# DNS Diagnostic Script for Hostinger Issues (Windows PowerShell)
# Run this in PowerShell on Windows to check DNS status

Write-Host "🔍 DNS DIAGNOSTIC FOR HOSTINGER PARKED DOMAIN ISSUE" -ForegroundColor Yellow
Write-Host "==================================================" -ForegroundColor Yellow

# Get domain from user
$Domain = Read-Host "Enter your domain name (without http://)"

if ([string]::IsNullOrEmpty($Domain)) {
    Write-Host "❌ No domain provided" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "🌐 Checking DNS for: $Domain" -ForegroundColor Cyan
Write-Host "==============================" -ForegroundColor Cyan

# Check A record using nslookup
Write-Host "📍 A Record Check:" -ForegroundColor Green
try {
    $ARecord = (Resolve-DnsName -Name $Domain -Type A -ErrorAction Stop).IPAddress
    Write-Host "✅ A record: $ARecord" -ForegroundColor Green
} catch {
    Write-Host "❌ No A record found - DNS not configured!" -ForegroundColor Red
    $ARecord = $null
}

# Check if it's pointing to Hostinger
Write-Host ""
Write-Host "🏢 Hostinger Server Check:" -ForegroundColor Cyan
$HostingerPrefixes = @("31.220.109", "195.35.37", "185.201.8", "185.201.9")
$IsHostinger = $false

if ($ARecord) {
    foreach ($prefix in $HostingerPrefixes) {
        if ($ARecord -like "$prefix*") {
            Write-Host "✅ IP points to Hostinger servers" -ForegroundColor Green
            $IsHostinger = $true
            break
        }
    }
    
    if (-not $IsHostinger) {
        Write-Host "⚠️  IP does not appear to be Hostinger servers" -ForegroundColor Yellow
        Write-Host "   Current IP: $ARecord" -ForegroundColor Yellow
        Write-Host "   Expected Hostinger IP ranges: 31.220.109.x, 195.35.37.x, 185.201.8.x, 185.201.9.x" -ForegroundColor Yellow
    }
}

# Check propagation
Write-Host ""
Write-Host "🌍 Global DNS Propagation:" -ForegroundColor Cyan
Write-Host "Check manually at: https://whatsmydns.net/#A/$Domain" -ForegroundColor White

# Test HTTP connection
Write-Host ""
Write-Host "🌐 HTTP Connection Test:" -ForegroundColor Cyan
try {
    $HttpResponse = Invoke-WebRequest -Uri "http://$Domain" -Method Head -TimeoutSec 10 -ErrorAction Stop
    $HttpStatus = $HttpResponse.StatusCode
    Write-Host "✅ HTTP connection successful ($HttpStatus)" -ForegroundColor Green
} catch {
    $HttpStatus = "Error: $($_.Exception.Message)"
    Write-Host "❌ HTTP connection failed: $HttpStatus" -ForegroundColor Red
}

# Test HTTPS connection
Write-Host ""
Write-Host "🔒 HTTPS Connection Test:" -ForegroundColor Cyan
try {
    $HttpsResponse = Invoke-WebRequest -Uri "https://$Domain" -Method Head -TimeoutSec 10 -ErrorAction Stop
    $HttpsStatus = $HttpsResponse.StatusCode
    Write-Host "✅ HTTPS connection successful ($HttpsStatus)" -ForegroundColor Green
} catch {
    $HttpsStatus = "Error: $($_.Exception.Message)"
    Write-Host "❌ HTTPS connection failed: $HttpsStatus" -ForegroundColor Red
}

# Ping test
Write-Host ""
Write-Host "🏓 Ping Test:" -ForegroundColor Cyan
if ($ARecord) {
    try {
        $PingResult = Test-Connection -ComputerName $Domain -Count 2 -ErrorAction Stop
        Write-Host "✅ Ping successful - Average: $($PingResult | Measure-Object ResponseTime -Average | Select-Object -ExpandProperty Average)ms" -ForegroundColor Green
    } catch {
        Write-Host "❌ Ping failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "📋 SUMMARY FOR HOSTINGER SUPPORT:" -ForegroundColor Yellow
Write-Host "==================================" -ForegroundColor Yellow
Write-Host "Domain: $Domain"
Write-Host "A Record: $ARecord"
Write-Host "Points to Hostinger: $IsHostinger"
Write-Host "HTTP Status: $HttpStatus"
Write-Host "HTTPS Status: $HttpsStatus"

Write-Host ""
Write-Host "🎯 DIAGNOSIS & NEXT STEPS:" -ForegroundColor Yellow

if (-not $IsHostinger -or $null -eq $ARecord) {
    Write-Host "❌ DNS ISSUE: Domain not pointing to Hostinger servers" -ForegroundColor Red
    Write-Host "   Action: Update DNS A record to point to Hostinger IP" -ForegroundColor White
    Write-Host "   Contact: Your domain registrar or Hostinger support" -ForegroundColor White
} elseif ($HttpStatus -notlike "*200*") {
    Write-Host "❌ SERVER ISSUE: DNS correct but server not responding properly" -ForegroundColor Red
    Write-Host "   Action: Contact Hostinger support - server configuration issue" -ForegroundColor White
    Write-Host "   Mention: DNS points correctly but getting parked domain page" -ForegroundColor White
} else {
    Write-Host "✅ DNS and connection appear normal" -ForegroundColor Green
    Write-Host "   Action: Check for caching issues or contact Hostinger support" -ForegroundColor White
}

Write-Host ""
Write-Host "📞 HOSTINGER SUPPORT MESSAGE TEMPLATE:" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Subject: Domain showing parked page - DNS diagnostic completed" -ForegroundColor White
Write-Host ""
Write-Host "Hi Hostinger Support," -ForegroundColor White
Write-Host ""
Write-Host "My domain $Domain is showing a parked domain page instead of my uploaded files." -ForegroundColor White
Write-Host ""
Write-Host "DNS Diagnostic Results:" -ForegroundColor White
Write-Host "- Domain: $Domain" -ForegroundColor White
Write-Host "- A Record: $ARecord" -ForegroundColor White
Write-Host "- Points to Hostinger: $IsHostinger" -ForegroundColor White
Write-Host "- HTTP Response: $HttpStatus" -ForegroundColor White
Write-Host "- HTTPS Response: $HttpsStatus" -ForegroundColor White
Write-Host ""
Write-Host "I have uploaded files to public_html and verified document root settings." -ForegroundColor White
Write-Host "This appears to be a server-level configuration issue." -ForegroundColor White
Write-Host ""
Write-Host "Please check:" -ForegroundColor White
Write-Host "1. Virtual host configuration for this domain" -ForegroundColor White
Write-Host "2. Any server-level redirects or parking overrides" -ForegroundColor White
Write-Host "3. Domain mapping to hosting space" -ForegroundColor White
Write-Host ""
Write-Host "Thank you for your assistance." -ForegroundColor White

Write-Host ""
Write-Host "💡 TIP: Copy the above message template and send it to Hostinger support" -ForegroundColor Green

# Pause to keep window open
Write-Host ""
Write-Host "Press any key to continue..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")