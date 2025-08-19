<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'amount',
        'currency',
        'starts_at',
        'expires_at',
        'cancelled_at',
        'stripe_subscription_id',
        'stripe_payment_intent_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active' && 
               $this->starts_at <= now() && 
               $this->expires_at >= now();
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function daysUntilExpiry()
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return now()->diffInDays($this->expires_at);
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);
    }

    public function renew($months = 1)
    {
        $this->update([
            'expires_at' => $this->expires_at->addMonths($months),
            'status' => 'active'
        ]);
    }
}