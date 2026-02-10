<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Address extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'address', 'city', 'state', 'pincode', 'type'
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
}
