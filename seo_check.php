<?php
/**
 * SEO & Geolocation Diagnostic Script
 * Access: https://grabbaskets.com/seo_check.php
 */

echo "<!DOCTYPE html><html lang='en'><head><title>SEO & Location Check</title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; }
    .box { background: white; padding: 25px; border-radius: 10px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    h1 { color: #333; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
    h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-bottom: 20px; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 13px; }
    .badge { padding: 5px 12px; border-radius: 15px; display: inline-block; margin: 5px; font-size: 13px; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-info { background: #d1ecf1; color: #0c5460; }
    .section-score { font-size: 48px; font-weight: bold; text-align: center; margin: 20px 0; }
    .score-good { color: #28a745; }
    .score-fair { color: #ffc107; }
    .score-poor { color: #dc3545; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    table th { background: #f8f9fa; font-weight: 600; }
    .action-btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
    .action-btn:hover { background: #5568d3; }
</style>
</head><body><div class='container'>";

echo "<h1>🚀 SEO & Geolocation Diagnostic Tool</h1>";

$seoScore = 0;
$totalChecks = 0;

// 1. HTTPS Check
echo "<div class='box'><h2>🔒 1. HTTPS & Security</h2>";
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

if ($isHttps) {
    echo "<p class='success'>✅ HTTPS is enabled</p>";
    echo "<p>Current URL: <code>{$currentUrl}</code></p>";
    $seoScore += 15;
    echo "<div class='badge badge-success'>Geolocation: Will work ✅</div>";
} else {
    echo "<p class='error'>❌ HTTPS is NOT enabled</p>";
    echo "<p>Current URL: <code>{$currentUrl}</code></p>";
    echo "<div class='badge badge-danger'>⚠️ Geolocation requires HTTPS</div>";
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;'>
        <strong>🔧 How to fix:</strong><br>
        1. Enable HTTPS in your hosting panel<br>
        2. Force HTTPS redirect in .htaccess<br>
        3. Update APP_URL in .env to https://grabbaskets.com
    </div>";
}
$totalChecks += 15;
echo "</div>";

// 2. Meta Tags Check
echo "<div class='box'><h2>📝 2. SEO Meta Tags</h2>";
$indexPath = __DIR__ . '/resources/views/index.blade.php';
$metaTags = [
    'meta name="description"' => false,
    'meta name="keywords"' => false,
    'meta property="og:title"' => false,
    'meta property="og:description"' => false,
    'meta property="og:image"' => false,
    'meta name="twitter:card"' => false,
    'canonical' => false,
    'structured data' => false
];

if (file_exists($indexPath)) {
    $content = file_get_contents($indexPath);
    
    foreach ($metaTags as $tag => &$found) {
        if (stripos($content, $tag) !== false) {
            $found = true;
            echo "<p class='success'>✅ {$tag}</p>";
            $seoScore += 5;
        } else {
            echo "<p class='error'>❌ Missing: {$tag}</p>";
        }
    }
} else {
    echo "<p class='error'>❌ index.blade.php not found</p>";
}

$totalChecks += 40; // 8 tags × 5 points
echo "</div>";

// 3. robots.txt Check
echo "<div class='box'><h2>🤖 3. Robots.txt</h2>";
$robotsPath = __DIR__ . '/public/robots.txt';
if (file_exists($robotsPath)) {
    $robotsContent = file_get_contents($robotsPath);
    $robotsLines = explode("\n", $robotsContent);
    
    echo "<p class='success'>✅ robots.txt exists</p>";
    $seoScore += 5;
    
    if (stripos($robotsContent, 'Sitemap:') !== false) {
        echo "<p class='success'>✅ Sitemap URL declared</p>";
        $seoScore += 5;
    } else {
        echo "<p class='warning'>⚠️ Sitemap URL not declared</p>";
    }
    
    if (stripos($robotsContent, 'Allow:') !== false || stripos($robotsContent, 'Disallow:') !== false) {
        echo "<p class='success'>✅ Crawl rules defined</p>";
        $seoScore += 5;
    }
    
    echo "<details><summary>View robots.txt</summary><pre>" . htmlspecialchars($robotsContent) . "</pre></details>";
} else {
    echo "<p class='error'>❌ robots.txt not found</p>";
}
$totalChecks += 15;
echo "</div>";

// 4. Sitemap Check
echo "<div class='box'><h2>🗺️ 4. XML Sitemap</h2>";
$sitemapUrl = $currentUrl . '/sitemap.xml';
$ch = curl_init($sitemapUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false
]);
$sitemapResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Sitemap URL:</strong> <a href='{$sitemapUrl}' target='_blank'>{$sitemapUrl}</a></p>";

if ($httpCode === 200 && !empty($sitemapResponse)) {
    echo "<p class='success'>✅ Sitemap is accessible (HTTP {$httpCode})</p>";
    $seoScore += 10;
    
    // Count URLs in sitemap
    $urlCount = substr_count($sitemapResponse, '<url>');
    echo "<p><strong>URLs in sitemap:</strong> {$urlCount}</p>";
    
    if ($urlCount > 0) {
        echo "<p class='success'>✅ Sitemap contains URLs</p>";
        $seoScore += 5;
    }
} else {
    echo "<p class='error'>❌ Sitemap not accessible (HTTP {$httpCode})</p>";
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;'>
        <strong>🔧 How to fix:</strong><br>
        1. Ensure SitemapController exists<br>
        2. Route added: Route::get('/sitemap.xml', [SitemapController::class, 'index'])<br>
        3. Clear route cache: php artisan route:clear
    </div>";
}
$totalChecks += 15;
echo "</div>";

// 5. Google Maps API Check
echo "<div class='box'><h2>📍 5. Google Maps API (Location Detection)</h2>";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    
    if (preg_match('/GOOGLE_MAPS_API_KEY=(.+)/', $envContent, $match)) {
        $apiKey = trim($match[1]);
        echo "<p class='success'>✅ Google Maps API key configured</p>";
        echo "<p><strong>Key:</strong> " . substr($apiKey, 0, 20) . "...</p>";
        $seoScore += 5;
        
        // Test API key
        $testUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=India&key={$apiKey}";
        $ch = curl_init($testUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $apiResponse = curl_exec($ch);
        curl_close($ch);
        
        $apiData = json_decode($apiResponse, true);
        if ($apiData && $apiData['status'] === 'OK') {
            echo "<p class='success'>✅ API key is valid and working</p>";
            echo "<div class='badge badge-success'>Location detection will work ✅</div>";
            $seoScore += 5;
        } elseif ($apiData && $apiData['status'] === 'REQUEST_DENIED') {
            echo "<p class='error'>❌ API key is invalid or restricted</p>";
            echo "<div class='badge badge-danger'>Location detection will fail ❌</div>";
        }
    } else {
        echo "<p class='error'>❌ Google Maps API key not found in .env</p>";
        echo "<div class='badge badge-danger'>Location detection will fail ❌</div>";
    }
} else {
    echo "<p class='error'>❌ .env file not found</p>";
}
$totalChecks += 10;
echo "</div>";

// 6. Page Load Speed
echo "<div class='box'><h2>⚡ 6. Performance</h2>";
echo "<p><strong>Image Optimization:</strong></p>";
$imagePath = __DIR__ . '/public/asset/images';
if (is_dir($imagePath)) {
    $images = glob($imagePath . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    $totalSize = 0;
    $largeImages = 0;
    
    foreach ($images as $img) {
        $size = filesize($img);
        $totalSize += $size;
        if ($size > 500000) { // 500KB
            $largeImages++;
        }
    }
    
    echo "<p>Total images: " . count($images) . "</p>";
    echo "<p>Total size: " . round($totalSize / 1024 / 1024, 2) . " MB</p>";
    
    if ($largeImages > 0) {
        echo "<p class='warning'>⚠️ {$largeImages} images over 500KB</p>";
        echo "<div class='badge badge-warning'>Consider image optimization</div>";
    } else {
        echo "<p class='success'>✅ Images are optimized</p>";
        $seoScore += 5;
    }
}
$totalChecks += 5;
echo "</div>";

// 7. Geolocation Permissions Test
echo "<div class='box'><h2>📍 7. Geolocation Test (Browser)</h2>";
echo "<p><strong>Test your browser's geolocation:</strong></p>";
echo "<button class='action-btn' onclick='testGeolocation()'>Test Location Detection</button>";
echo "<div id='geolocationResult' style='margin-top: 15px;'></div>";

echo "<script>
function testGeolocation() {
    const resultDiv = document.getElementById('geolocationResult');
    
    if (!navigator.geolocation) {
        resultDiv.innerHTML = '<div class=\"badge badge-danger\">❌ Geolocation not supported</div>';
        return;
    }
    
    resultDiv.innerHTML = '<p>⏳ Requesting location permission...</p>';
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            resultDiv.innerHTML = 
                '<div class=\"badge badge-success\">✅ Location detected successfully!</div>' +
                '<table>' +
                '<tr><th>Latitude</th><td>' + position.coords.latitude + '</td></tr>' +
                '<tr><th>Longitude</th><td>' + position.coords.longitude + '</td></tr>' +
                '<tr><th>Accuracy</th><td>' + position.coords.accuracy + ' meters</td></tr>' +
                '</table>';
        },
        function(error) {
            let message = '';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = '❌ Location access denied. Please allow location in browser settings.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = '❌ Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    message = '❌ Location request timed out.';
                    break;
                default:
                    message = '❌ Unknown error: ' + error.message;
            }
            resultDiv.innerHTML = '<div class=\"badge badge-danger\">' + message + '</div>';
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}
</script>";
echo "</div>";

// SEO Score Summary
$scorePercentage = round(($seoScore / $totalChecks) * 100);
$scoreClass = $scorePercentage >= 80 ? 'score-good' : ($scorePercentage >= 50 ? 'score-fair' : 'score-poor');

echo "<div class='box' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'>";
echo "<h2 style='color: white; border-bottom: 2px solid white;'>📊 Overall SEO Score</h2>";
echo "<div class='section-score {$scoreClass}' style='color: white;'>{$scorePercentage}%</div>";
echo "<p style='text-align: center; font-size: 18px;'>{$seoScore} / {$totalChecks} points</p>";

if ($scorePercentage >= 80) {
    echo "<p style='text-align: center;'>🎉 <strong>Excellent!</strong> Your site is well-optimized for SEO.</p>";
} elseif ($scorePercentage >= 50) {
    echo "<p style='text-align: center;'>👍 <strong>Good!</strong> Some improvements needed.</p>";
} else {
    echo "<p style='text-align: center;'>⚠️ <strong>Needs Work!</strong> Several SEO issues to address.</p>";
}
echo "</div>";

// Quick Actions
echo "<div class='box'><h2>🔧 Quick Actions</h2>";
echo "<a href='/' class='action-btn'>View Homepage</a>";
echo "<a href='/sitemap.xml' target='_blank' class='action-btn'>View Sitemap</a>";
echo "<a href='/robots.txt' target='_blank' class='action-btn'>View Robots.txt</a>";
echo "<a href='https://search.google.com/search-console' target='_blank' class='action-btn'>Google Search Console</a>";
echo "<a href='https://pagespeed.web.dev/' target='_blank' class='action-btn'>PageSpeed Insights</a>";
echo "</div>";

// Recommendations
echo "<div class='box'><h2>📋 SEO Recommendations</h2>";
echo "<h3>✅ Completed:</h3><ul>";
if (stripos($metaTags['meta name="description"], true) !== false) echo "<li>Meta description added</li>";
if ($isHttps) echo "<li>HTTPS enabled</li>";
if (file_exists($robotsPath)) echo "<li>robots.txt configured</li>";
echo "</ul>";

echo "<h3>🔧 To Do:</h3><ul>";
if (!$isHttps) {
    echo "<li><strong>Enable HTTPS</strong> - Required for geolocation</li>";
}
if ($httpCode !== 200) {
    echo "<li>Fix sitemap generation</li>";
}
echo "<li>Submit sitemap to Google Search Console</li>";
echo "<li>Optimize images (compress, use WebP format)</li>";
echo "<li>Add schema.org structured data for products</li>";
echo "<li>Implement lazy loading for images</li>";
echo "<li>Enable browser caching</li>";
echo "</ul>";
echo "</div>";

echo "<p style='text-align: center; color: #666; margin-top: 30px;'>
    Generated at " . date('Y-m-d H:i:s') . " | 
    <a href='javascript:location.reload()' style='color: #667eea;'>🔄 Refresh</a>
</p>";

echo "</div></body></html>";
