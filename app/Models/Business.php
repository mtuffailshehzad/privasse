<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Business extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'description_ar',
        'email',
        'phone',
        'website',
        'trade_license_number',
        'trade_license_expiry',
        'owner_name',
        'owner_email',
        'owner_phone',
        'status',
        'verification_status',
        'verification_notes',
        'subscription_type',
        'subscription_status',
        'subscription_expires_at',
        'commission_rate',
        'is_featured',
        'is_women_only',
        'settings',
        'verified_at',
        'rejected_at',
    ];

    protected $casts = [
        'trade_license_expiry' => 'date',
        'subscription_expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
        'commission_rate' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_women_only' => 'boolean',
        'settings' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'verification_status', 'subscription_type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('trade_license')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);

        $this->addMediaCollection('additional_documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);

        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10);
    }

    // Relationships
    public function venues()
    {
        return $this->hasMany(Venue::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(BusinessSubscription::class);
    }

    public function analytics()
    {
        return $this->hasMany(BusinessAnalytic::class);
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWomenOnly($query)
    {
        return $query->where('is_women_only', true);
    }

    // Helper methods
    public function isVerified()
    {
        return $this->verification_status === 'verified';
    }

    public function isSubscriptionActive()
    {
        return $this->subscription_status === 'active' && 
               $this->subscription_expires_at && 
               $this->subscription_expires_at->isFuture();
    }
}