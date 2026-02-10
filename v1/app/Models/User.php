<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'billing_address',
        'state',
        'city',
        'pincode',
        'role',
        'sex',
        'password',
        'dob',
        'wallet_point',
        'default_address',
        'profile_picture',
        'referral_code',
        'referrer_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->referral_code)) {
                $user->referral_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function buyerOrders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sellerOrders()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    /**
     * Get all orders for the user (as buyer or seller)
     */
    public function orders()
    {
        if ($this->role === 'seller') {
            return $this->sellerOrders();
        }
        return $this->buyerOrders();
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    /**
     * Get all wallet transactions for this user
     */
    public function walletTransactions()
    {
        return $this->hasMany(UserWalletTransaction::class, 'user_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get users who were referred by this user
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    /**
     * Get the user who referred this user
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }


    /**
     * Add points to user's wallet and create transaction record
     */
    public function addWalletPoints(int $amount, string $type, string $description, ?int $relatedUserId = null): void
    {
        // Create transaction record - wallet balance is automatically updated via UserWalletTransaction model booted event
        UserWalletTransaction::create([
            'user_id' => $this->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'related_user_id' => $relatedUserId,
        ]);
    }
}

