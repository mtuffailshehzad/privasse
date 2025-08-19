<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'date_of_birth',
        'nationality',
        'emirates_id',
        'preferred_language',
        'subscription_type',
        'subscription_status',
        'subscription_expires_at',
        'email_verified_at',
        'phone_verified_at',
        'is_active',
        'last_login_at',
        'preferences',
        'biometric_enabled',
        'two_factor_enabled',
        'marketing_consent',
        'data_processing_consent',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'emirates_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'subscription_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'preferences' => 'array',
        'is_active' => 'boolean',
        'biometric_enabled' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'marketing_consent' => 'boolean',
        'data_processing_consent' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'email', 'phone', 'subscription_type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isSubscriptionActive()
    {
        return $this->subscription_status === 'active' &&
               $this->subscription_expires_at &&
               $this->subscription_expires_at->isFuture();
    }

    // Relationships
    public function favorites()
    {
        return $this->belongsToMany(Venue::class, 'user_favorites')->withTimestamps();
    }

    public function visits()
    {
        return $this->hasMany(VenueVisit::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function offerRedemptions()
    {
        return $this->hasMany(OfferRedemption::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
    }
}
