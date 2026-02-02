<?php

// Simple test to check what's causing the 500 error in cloud environment
echo "🔍 CLOUD ENVIRONMENT 500 ERROR DIAGNOSTIC\n";
echo "=========================================\n\n";

try {
    echo "1. ✅ PHP is working\n";
    
    // Test basic Laravel loading
    if (file_exists('vendor/autoload.php')) {
        echo "2. ✅ Composer autoload exists\n";
        require_once 'vendor/autoload.php';
        
        if (file_exists('bootstrap/app.php')) {
            echo "3. ✅ Bootstrap file exists\n";
            
            try {
                $app = require_once 'bootstrap/app.php';
                echo "4. ✅ Laravel app loaded\n";
                
                try {
                    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
                    echo "5. ✅ Laravel kernel bootstrapped\n";
                    
                    // Test database connection
                    try {
                        $pdo = new PDO(
                            'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE'),
                            env('DB_USERNAME'),
                            env('DB_PASSWORD')
                        );
                        echo "6. ✅ Database connection working\n";
                    } catch (Exception $e) {
                        echo "6. ❌ Database connection failed: " . $e->getMessage() . "\n";
                    }
                    
                    // Test storage configuration
                    try {
                        $defaultDisk = config('filesystems.default');
                        echo "7. ✅ Filesystem config loaded (default: $defaultDisk)\n";
                    } catch (Exception $e) {
                        echo "7. ❌ Filesystem config failed: " . $e->getMessage() . "\n";
                    }
                    
                    // Test if models work
                    try {
                        $productCount = \App\Models\Product::count();
                        echo "8. ✅ Product model working ($productCount products)\n";
                    } catch (Exception $e) {
                        echo "8. ❌ Product model failed: " . $e->getMessage() . "\n";
                    }
                    
                    // Test specific product
                    try {
                        $product = \App\Models\Product::find(56);
                        if ($product) {
                            echo "9. ✅ Test product found: {$product->name}\n";
                            
                            // Test the image_url attribute that might be causing issues
                            try {
                                $imageUrl = $product->image_url;
                                echo "10. ✅ Image URL attribute working: " . ($imageUrl ? 'URL generated' : 'NULL') . "\n";
                            } catch (Exception $e) {
                                echo "10. ❌ Image URL attribute failed: " . $e->getMessage() . "\n";
                                echo "    This might be the cause of the 500 error!\n";
                            }
                        } else {
                            echo "9. ⚠️  Test product (ID 56) not found\n";
                        }
                    } catch (Exception $e) {
                        echo "9. ❌ Product retrieval failed: " . $e->getMessage() . "\n";
                    }
                    
                } catch (Exception $e) {
                    echo "5. ❌ Laravel kernel bootstrap failed: " . $e->getMessage() . "\n";
                    echo "   File: " . $e->getFile() . "\n";
                    echo "   Line: " . $e->getLine() . "\n";
                }
            } catch (Exception $e) {
                echo "4. ❌ Laravel app loading failed: " . $e->getMessage() . "\n";
                echo "   File: " . $e->getFile() . "\n";
                echo "   Line: " . $e->getLine() . "\n";
            }
        } else {
            echo "3. ❌ Bootstrap file missing\n";
        }
    } else {
        echo "2. ❌ Composer autoload missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}

echo "\n🎯 Diagnostic complete!\n";
echo "Access this file via: https://grabbaskets.com/cloud-diagnostic.php\n";