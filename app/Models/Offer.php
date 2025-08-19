<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class Offer extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'business_id',
        'venue_id',
        'title',
        'title_ar',
        'description',
        'description_ar',
        'type',
        'discount_type',
        'discount_value',
        'original_price',
        'discounted_price',
        'terms_conditions',
        'terms_conditions_ar',
        'start_date',
        'end_date',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'is_active',
        'is_featured',
        'visibility_rules',
        'qr_code',
        'status',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'discount_value' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'visibility_rules' => 'array',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'is_active', 'discount_value'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('featured_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function redemptions()
    {
        return $this->hasMany(OfferRedemption::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('status', 'approved')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('usage_limit')
              ->orWhereRaw('used_count < usage_limit');
        });
    }

    // Helper methods
    public function generateQrCode()
    {
        $qrCode = new QrCode(route('offers.redeem', $this->id));
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        $this->qr_code = base64_encode($result->getString());
        $this->save();
        
        return $this->qr_code;
    }

    public function isRedeemable()
    {
        return $this->is_active && 
               $this->status === 'approved' &&
               $this->start_date <= now() &&
               $this->end_date >= now() &&
               ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function canUserRedeem(User $user)
    {
        if (!$this->isRedeemable()) {
            return false;
        }

        if ($this->usage_limit_per_user) {
            $userRedemptions = $this->redemptions()
                ->where('user_id', $user->id)
                ->count();
            
            return $userRedemptions < $this->usage_limit_per_user;
        }

        return true;
    }

    public function redeem(User $user)
    {
        if (!$this->canUserRedeem($user)) {
            throw new \Exception('Offer cannot be redeemed');
        }

        $redemption = $this->redemptions()->create([
            'user_id' => $user->id,
            'redeemed_at' => now(),
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ]
        ]);

        $this->increment('used_count');

        return $redemption;
    }
}