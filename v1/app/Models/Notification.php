<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'id',
        'notifiable_type',
        'notifiable_id',
        'type',
        'title',
        'message',
        'data',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification (backward compatibility)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifiable entity (for Laravel notification system)
     */
    public function notifiable()
    {
        if ($this->notifiable_type && $this->notifiable_id) {
            return $this->morphTo();
        }
        return $this->user();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Check if notification is read
     */
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    /**
     * Accessor for title
     */
    public function getTitleAttribute($value)
    {
        // Return title column if exists, otherwise get from data
        return $value ?? $this->data['title'] ?? $this->type;
    }

    /**
     * Accessor for message
     */
    public function getMessageAttribute($value)
    {
        // Return message column if exists, otherwise get from data
        return $value ?? $this->data['message'] ?? '';
    }
}