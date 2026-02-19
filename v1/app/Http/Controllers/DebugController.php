<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\Seller;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function testProduct($id)
    {
        try {
            // Step 1: Test product retrieval
            $product = Product::with(['category','subcategory'])->findOrFail($id);
            
            // Step 2: Test related data
            $seller = Seller::where('id', $product->seller_id)->first();
            $reviews = Review::where('product_id', $product->id)->with('user')->latest()->get();
            $otherProducts = Product::where('seller_id', $product->seller_id)
                ->where('id', '!=', $product->id)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->latest()->take(8)->get();
            
            // Step 3: Test image URL generation
            $imageUrl = $product->image_url;
            
            // Return JSON instead of view to avoid template issues
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image,
                    'image_url' => $imageUrl,
                    'category' => $product->category ? $product->category->name : null,
                    'subcategory' => $product->subcategory ? $product->subcategory->name : null,
                ],
                'seller' => $seller ? $seller->name : null,
                'reviews_count' => $reviews->count(),
                'other_products_count' => $otherProducts->count(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ], 500);
        }
    }
    
    public function testView($id)
    {
        try {
            $product = Product::with(['category','subcategory'])->findOrFail($id);
            $seller = Seller::where('id', $product->seller_id)->first();
            $reviews = Review::where('product_id', $product->id)->with('user')->latest()->get();
            $otherProducts = Product::where('seller_id', $product->seller_id)
                ->where('id', '!=', $product->id)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->latest()->take(8)->get();
            
            // Try to render the actual view
            return view('buyer.product-details', compact('product', 'seller', 'reviews', 'otherProducts'));
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ], 500);
        }
    }
}