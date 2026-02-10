<?php

// Emergency cache clearing script for production
echo "=== CLEARING PRODUCTION CACHES ===" . PHP_EOL;

try {
    // Clear route cache
    echo "Clearing route cache..." . PHP_EOL;
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    echo "✅ Route cache cleared" . PHP_EOL;
    
    // Clear config cache  
    echo "Clearing config cache..." . PHP_EOL;
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    echo "✅ Config cache cleared" . PHP_EOL;
    
    // Clear application cache
    echo "Clearing application cache..." . PHP_EOL;
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    echo "✅ Application cache cleared" . PHP_EOL;
    
    // Clear view cache
    echo "Clearing view cache..." . PHP_EOL;
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "✅ View cache cleared" . PHP_EOL;
    
    // Re-cache routes
    echo "Caching routes..." . PHP_EOL;
    \Illuminate\Support\Facades\Artisan::call('route:cache');
    echo "✅ Routes cached" . PHP_EOL;
    
    // Re-cache config
    echo "Caching config..." . PHP_EOL;
    \Illuminate\Support\Facades\Artisan::call('config:cache');
    echo "✅ Config cached" . PHP_EOL;
    
    echo PHP_EOL . "✅ All caches cleared and rebuilt successfully!" . PHP_EOL;
    echo "The serve-image route should now be active." . PHP_EOL;
    
} catch (\Exception $e) {
    echo "❌ Error clearing caches: " . $e->getMessage() . PHP_EOL;
}
?>