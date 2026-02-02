<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Product;

Route::get('/test-product/{id}', function ($id) {
    try {
        $product = Product::findOrFail($id);
        
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'image' => $product->image,
            'image_url' => $product->image_url,
        ];
        
        return response()->json($data);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/test-images', function () {
    try {
        $products = Product::whereRaw("image LIKE 'images/SRM%'")->take(5)->get();
        
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->id,
                'name' => $product->name,
                'image' => $product->image,
                'image_url' => $product->image_url,
            ];
        }
        
        return response()->json($data);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});