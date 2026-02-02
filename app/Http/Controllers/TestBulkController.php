<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\Product;

class TestBulkController extends Controller
{
    public function showBulkTest()
    {
        try {
            // Simple test without complex logic
            $categories = Category::all();
            
            return view('seller.bulk-image-test', compact('categories'));
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Controller Error: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }
}