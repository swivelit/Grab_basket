#!/bin/bash

# DNS Diagnostic Script for Hostinger Issues
# Run this on your local computer to check DNS status

echo "🔍 DNS DIAGNOSTIC FOR HOSTINGER PARKED DOMAIN ISSUE"
echo "=================================================="

# Get domain from user
read -p "Enter your domain name (without http://): " DOMAIN

if [ -z "$DOMAIN" ]; then
    echo "❌ No domain provided"
    exit 1
fi

echo ""
echo "🌐 Checking DNS for: $DOMAIN"
echo "=============================="

# Check A record
echo "📍 A Record Check:"
A_RECORD=$(dig +short A $DOMAIN)
if [ -z "$A_RECORD" ]; then
    echo "❌ No A record found - DNS not configured!"
else
    echo "✅ A record: $A_RECORD"
fi

# Check if it's pointing to Hostinger
echo ""
echo "🏢 Hostinger Server Check:"
HOSTINGER_IPS=("31.220.109" "195.35.37" "185.201.8" "185.201.9")
IS_HOSTINGER=false

for ip_prefix in "${HOSTINGER_IPS[@]}"; do
    if [[ $A_RECORD == $ip_prefix* ]]; then
        echo "✅ IP points to Hostinger servers"
        IS_HOSTINGER=true
        break
    fi
done

if [ "$IS_HOSTINGER" = false ]; then
    echo "⚠️  IP does not appear to be Hostinger servers"
    echo "   Current IP: $A_RECORD"
    echo "   Expected Hostinger IP ranges: 31.220.109.x, 195.35.37.x, 185.201.8.x, 185.201.9.x"
fi

# Check propagation
echo ""
echo "🌍 Global DNS Propagation:"
echo "Check manually at: https://whatsmydns.net/#A/$DOMAIN"

# Check CNAME
echo ""
echo "📝 CNAME Check:"
CNAME=$(dig +short CNAME $DOMAIN)
if [ -z "$CNAME" ]; then
    echo "ℹ️  No CNAME record (this is normal for root domains)"
else
    echo "📍 CNAME: $CNAME"
fi

# Test HTTP connection
echo ""
echo "🌐 HTTP Connection Test:"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://$DOMAIN --max-time 10)
if [ "$HTTP_STATUS" = "000" ]; then
    echo "❌ Cannot connect to domain"
elif [ "$HTTP_STATUS" = "200" ]; then
    echo "✅ HTTP connection successful (200)"
else
    echo "⚠️  HTTP status: $HTTP_STATUS"
fi

# Test HTTPS connection
echo ""
echo "🔒 HTTPS Connection Test:"
HTTPS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://$DOMAIN --max-time 10)
if [ "$HTTPS_STATUS" = "000" ]; then
    echo "❌ Cannot connect via HTTPS"
elif [ "$HTTPS_STATUS" = "200" ]; then
    echo "✅ HTTPS connection successful (200)"
else
    echo "⚠️  HTTPS status: $HTTPS_STATUS"
fi

echo ""
echo "📋 SUMMARY FOR HOSTINGER SUPPORT:"
echo "=================================="
echo "Domain: $DOMAIN"
echo "A Record: $A_RECORD"
echo "Points to Hostinger: $IS_HOSTINGER"
echo "HTTP Status: $HTTP_STATUS"
echo "HTTPS Status: $HTTPS_STATUS"

echo ""
echo "🎯 NEXT STEPS:"
if [ "$IS_HOSTINGER" = false ] || [ -z "$A_RECORD" ]; then
    echo "❌ DNS ISSUE: Domain not pointing to Hostinger servers"
    echo "   Action: Update DNS A record to point to Hostinger IP"
    echo "   Contact: Your domain registrar or Hostinger support"
elif [ "$HTTP_STATUS" != "200" ]; then
    echo "❌ SERVER ISSUE: DNS correct but server not responding properly"
    echo "   Action: Contact Hostinger support - server configuration issue"
    echo "   Mention: DNS points correctly but getting parked domain page"
else
    echo "✅ DNS and connection appear normal"
    echo "   Action: Check for caching issues or contact Hostinger support"
fi

echo ""
echo "💡 TIP: Save this output and include it in your Hostinger support ticket"