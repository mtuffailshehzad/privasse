<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Review extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'venue_id',
        'rating',
        'title',
        'comment',
        'visit_date',
        'is_verified',
        'is_featured',
        'status',
        'admin_notes',
        'helpful_count',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'visit_date' => 'date',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'helpful_count' => 'integer',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['rating', 'status', 'is_featured'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function helpfulVotes()
    {
        return $this->hasMany(ReviewHelpfulVote::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    // Helper methods
    public function markAsHelpful(User $user)
    {
        if (!$this->helpfulVotes()->where('user_id', $user->id)->exists()) {
            $this->helpfulVotes()->create(['user_id' => $user->id]);
            $this->increment('helpful_count');
        }
    }

    public function unmarkAsHelpful(User $user)
    {
        $vote = $this->helpfulVotes()->where('user_id', $user->id)->first();
        if ($vote) {
            $vote->delete();
            $this->decrement('helpful_count');
        }
    }

    public function isHelpfulBy(User $user)
    {
        return $this->helpfulVotes()->where('user_id', $user->id)->exists();
    }
}