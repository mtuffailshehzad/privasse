<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'venue_id',
        'visited_at',
        'duration_minutes',
        'source',
        'metadata',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'duration_minutes' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    // Scopes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('visited_at', '>=', now()->subDays($days));
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }
}