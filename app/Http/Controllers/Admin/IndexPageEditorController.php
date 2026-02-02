<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class IndexPageEditorController extends Controller
{
    /**
     * Show the index page editor
     */
    public function index()
    {
        // Check admin authentication
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        // Get current index page settings from config or database
        $settings = $this->getCurrentSettings();

        return view('admin.index-editor.index', compact('settings'));
    }

    /**
     * Get current index page settings
     */
    private function getCurrentSettings()
    {
        $configPath = config_path('index-page.php');
        
        if (File::exists($configPath)) {
            return include $configPath;
        }

        // Default settings
        return [
            'hero_title' => 'Welcome to GrabBaskets',
            'hero_subtitle' => 'Your one-stop shop for all your needs',
            'show_categories' => true,
            'show_featured_products' => true,
            'show_trending' => true,
            'featured_section_title' => 'Featured Products',
            'trending_section_title' => 'Trending Now',
            'products_per_row' => 4,
            'show_banners' => true,
            'show_newsletter' => true,
            'newsletter_title' => 'Subscribe to Our Newsletter',
            'newsletter_subtitle' => 'Get updates on new products and special offers',
            'theme_color' => '#FF6B00',
            'secondary_color' => '#FFD700',
        ];
    }

    /**
     * Update index page settings
     */
    public function update(Request $request)
    {
        // Check admin authentication
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $validated = $request->validate([
            'hero_title' => 'nullable|string|max:200',
            'hero_subtitle' => 'nullable|string|max:500',
            'show_categories' => 'boolean',
            'show_featured_products' => 'boolean',
            'show_trending' => 'boolean',
            'featured_section_title' => 'nullable|string|max:100',
            'trending_section_title' => 'nullable|string|max:100',
            'products_per_row' => 'integer|min:2|max:6',
            'show_banners' => 'boolean',
            'show_newsletter' => 'boolean',
            'newsletter_title' => 'nullable|string|max:200',
            'newsletter_subtitle' => 'nullable|string|max:500',
            'theme_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
        ]);

        // Save settings to config file
        $configPath = config_path('index-page.php');
        $configContent = "<?php\n\nreturn " . var_export($validated, true) . ";\n";
        
        File::put($configPath, $configContent);

        // Clear config cache
        Artisan::call('config:clear');

        Log::info('Index page settings updated by admin', [
            'admin_id' => session('admin_id'),
            'settings' => $validated
        ]);

        return redirect()->route('admin.index-editor.index')
            ->with('success', 'Index page settings updated successfully!');
    }

    /**
     * Preview index page with current settings
     */
    public function preview()
    {
        // Check admin authentication
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        // Redirect to homepage in new tab
        return redirect('/');
    }
}
