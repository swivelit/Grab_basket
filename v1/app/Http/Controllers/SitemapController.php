<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate XML sitemap for SEO
     */
    public function index()
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        // Homepage
        $sitemap .= $this->addUrl(config('app.url'), '1.0', 'daily', now()->toAtomString());
        
        // Categories
        $categories = Category::where('status', 'active')->get();
        foreach ($categories as $category) {
            $sitemap .= $this->addUrl(
                config('app.url') . '/buyer/category/' . $category->id,
                '0.8',
                'weekly',
                $category->updated_at->toAtomString()
            );
        }
        
        // Subcategories
        $subcategories = Subcategory::where('status', 'active')->get();
        foreach ($subcategories as $subcategory) {
            $sitemap .= $this->addUrl(
                config('app.url') . '/buyer/subcategory/' . $subcategory->id,
                '0.7',
                'weekly',
                $subcategory->updated_at->toAtomString()
            );
        }
        
        // Products (limit to 1000 most recent)
        $products = Product::where('status', 'active')
            ->orderBy('updated_at', 'desc')
            ->limit(1000)
            ->get();
            
        foreach ($products as $product) {
            $sitemap .= $this->addUrl(
                config('app.url') . '/products/' . $product->id,
                '0.6',
                'weekly',
                $product->updated_at->toAtomString()
            );
        }
        
        // Static pages
        $staticPages = [
            ['url' => '/buyer/dashboard', 'priority' => '0.7', 'changefreq' => 'daily'],
            ['url' => '/about', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => '/contact', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => '/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['url' => '/privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];
        
        foreach ($staticPages as $page) {
            $sitemap .= $this->addUrl(
                config('app.url') . $page['url'],
                $page['priority'],
                $page['changefreq'],
                now()->toAtomString()
            );
        }
        
        $sitemap .= '</urlset>';
        
        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml');
    }
    
    /**
     * Add URL to sitemap
     */
    private function addUrl($loc, $priority, $changefreq, $lastmod)
    {
        return '<url>' .
            '<loc>' . htmlspecialchars($loc) . '</loc>' .
            '<lastmod>' . $lastmod . '</lastmod>' .
            '<changefreq>' . $changefreq . '</changefreq>' .
            '<priority>' . $priority . '</priority>' .
            '</url>';
    }
}
