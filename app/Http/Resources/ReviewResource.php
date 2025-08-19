<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'visit_date' => $this->visit_date,
            'is_verified' => $this->is_verified,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'helpful_count' => $this->helpful_count,
            
            // User information
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->first_name . ' ' . substr($this->user->last_name, 0, 1) . '.',
                'avatar' => $this->user->getFirstMediaUrl('avatar'),
                'subscription_type' => $this->user->subscription_type,
            ],
            
            // Venue information (when not loaded from venue context)
            'venue' => $this->when(
                !$request->routeIs('venues.show') && $this->relationLoaded('venue'),
                new VenueResource($this->venue)
            ),
            
            // Photos
            'photos' => $this->getMedia('photos')->map(function($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumb' => $media->getUrl('thumb'),
                ];
            }),
            
            // User interaction (when authenticated)
            'is_helpful_by_user' => $this->when(
                auth()->check(),
                function() {
                    return $this->isHelpfulBy(auth()->user());
                }
            ),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}