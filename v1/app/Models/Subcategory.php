<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = [
        'name',
        'unique_id',
        'category_id',
        'description',
    ];
    
    // Each subcategory belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    // Each subcategory has many products
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}