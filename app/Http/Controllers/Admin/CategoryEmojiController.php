<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryEmojiController extends Controller
{
    /**
     * Display a listing of categories with their emojis.
     */
    public function index()
    {
        $categories = Category::all();
        return view('admin.category-emojis.index', compact('categories'));
    }

    /**
     * Update the emoji for a specific category.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $category->update([
            'emoji' => $request->emoji
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Emoji updated successfully!'
        ]);
    }

    /**
     * Bulk update emojis for multiple categories.
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'emojis' => 'required|array',
            'emojis.*.id' => 'required|exists:categories,id',
            'emojis.*.emoji' => 'required|string|max:10',
        ]);

        foreach ($request->emojis as $emojiData) {
            Category::where('id', $emojiData['id'])
                ->update(['emoji' => $emojiData['emoji']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'All emojis updated successfully!'
        ]);
    }

    /**
     * Get suggested emojis for a category name.
     */
    public function getSuggestions(Request $request)
    {
        $categoryName = strtoupper(trim($request->category_name));
        
        $suggestions = $this->getEmojiSuggestions($categoryName);
        
        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Get emoji suggestions based on category name.
     */
    private function getEmojiSuggestions($categoryName)
    {
        $emojiMap = [
            'ELECTRONICS' => ['🖥️', '💻', '📱', '⚡', '🔌'],
            'MEN\'S FASHION' => ['👔', '👨‍💼', '🤵', '👕', '👖'],
            'WOMEN\'S FASHION' => ['👗', '👠', '💃', '👛', '💄'],
            'HOME & KITCHEN' => ['🍽️', '🏠', '🍳', '🔪', '🍴'],
            'BEAUTY & PERSONAL CARE' => ['💄', '💅', '🧴', '🪞', '✨'],
            'SPORTS & FITNESS' => ['🏃‍♂️', '⚽', '🏋️‍♂️', '🚴‍♂️', '🏆'],
            'BOOKS & EDUCATION' => ['📚', '📖', '🎓', '✏️', '📝'],
            'KIDS & TOYS' => ['🧸', '🎮', '🎯', '🎪', '🎠'],
            'AUTOMOTIVE' => ['🚗', '🚙', '🔧', '⛽', '🛞'],
            'HEALTH & WELLNESS' => ['🏥', '💊', '🩺', '🧘‍♂️', '💚'],
            'JEWELRY & ACCESSORIES' => ['💍', '💎', '⌚', '👑', '✨'],
            'GROCERY & FOOD' => ['🥬', '🍎', '🛒', '🥖', '🍇'],
            'FURNITURE' => ['🛋️', '🪑', '🛏️', '🚪', '🏠'],
            'GARDEN & OUTDOOR' => ['🌻', '🌸', '🌱', '🌳', '🏡'],
            'PET SUPPLIES' => ['🐕', '🐱', '🐾', '🦴', '🏠'],
            'BABY PRODUCTS' => ['👶', '🍼', '🧷', '🎪', '💕'],
            'CLOTHING' => ['👕', '👗', '👖', '🧥', '👟'],
            'BOOKS' => ['📖', '📚', '📑', '✍️', '🎓'],
        ];

        // Direct mapping
        if (isset($emojiMap[$categoryName])) {
            return $emojiMap[$categoryName];
        }

        // Partial matching
        $suggestions = ['🛍️']; // Default
        
        foreach ($emojiMap as $key => $emojis) {
            if (str_contains($categoryName, $key) || str_contains($key, $categoryName)) {
                $suggestions = array_merge($suggestions, $emojis);
                break;
            }
        }

        // Enhanced word matching
        if (str_contains($categoryName, 'ELECTRONIC')) $suggestions = array_merge($suggestions, ['⚡', '🔌', '💻']);
        elseif (str_contains($categoryName, 'FASHION') || str_contains($categoryName, 'CLOTH')) $suggestions = array_merge($suggestions, ['👗', '👔', '👕']);
        elseif (str_contains($categoryName, 'BEAUTY') || str_contains($categoryName, 'CARE')) $suggestions = array_merge($suggestions, ['💅', '💄', '✨']);
        elseif (str_contains($categoryName, 'SPORT') || str_contains($categoryName, 'FITNESS')) $suggestions = array_merge($suggestions, ['⚽', '🏃‍♂️', '🏋️‍♂️']);

        return array_unique($suggestions);
    }
}
