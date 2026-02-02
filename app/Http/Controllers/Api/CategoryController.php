<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Get all categories with subcategories
     */
    public function index()
    {
        $categories = Cache::remember('api_categories', 3600, function () {
            return Category::with(['subcategories' => function ($query) {
                $query->select('id', 'name', 'category_id', 'emoji');
            }])
            ->select('id', 'name', 'emoji', 'image')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'emoji' => $category->emoji,
                    'image' => $category->image_url ?? null,
                    'subcategories' => $category->subcategories->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                            'emoji' => $sub->emoji,
                        ];
                    }),
                    'product_count' => $category->products()->whereNotNull('seller_id')->count(),
                ];
            });
        });

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Get subcategories for a specific category
     */
    public function getSubcategories($id)
    {
        $category = Category::with(['subcategories' => function ($query) {
            $query->select('id', 'name', 'category_id', 'emoji')
                  ->orderBy('name');
        }])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'category_id' => $category->id,
            'category_name' => $category->name,
            'subcategories' => $category->subcategories->map(function ($subcategory) {
                return [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'emoji' => $subcategory->emoji ?? '📦',
                ];
            }),
        ]);
    }

    /**
     * Get category details with products
     */
    public function show(Request $request, $id)
    {
        $category = Category::with(['subcategories'])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $productsQuery = $category->products()
            ->with(['productImages'])
            ->whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderByDesc('created_at');

        $products = $productsQuery->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'emoji' => $category->emoji,
                'image' => $category->image_url ?? null,
                'subcategories' => $category->subcategories,
            ],
            'products' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }
}