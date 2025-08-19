<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'offer_id',
        'redeemed_at',
        'verification_code',
        'status',
        'metadata',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('redeemed_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function generateVerificationCode()
    {
        $this->verification_code = strtoupper(substr(md5(uniqid()), 0, 8));
        $this->save();
        
        return $this->verification_code;
    }

    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
    }
}