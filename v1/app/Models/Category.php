<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'unique_id',
        'image',
        'gender',
        'emoji',
    ];
    
    // Each category has many subcategories
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }
    
    // Each category has many products
    public function products()
    {
        return $this->hasMany(Product::class);
    }
   public function tenMinProducts()
{
    return $this->hasMany(\App\Models\TenMinDeliveryProduct::class, 'category_id');
}

}
