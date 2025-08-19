<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_ar' => $this->title_ar,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'type' => $this->type,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'original_price' => $this->original_price,
            'discounted_price' => $this->discounted_price,
            'terms_conditions' => $this->terms_conditions,
            'terms_conditions_ar' => $this->terms_conditions_ar,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'usage_limit' => $this->usage_limit,
            'usage_limit_per_user' => $this->usage_limit_per_user,
            'used_count' => $this->used_count,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'priority' => $this->priority,
            
            // Relationships
            'business' => new BusinessResource($this->whenLoaded('business')),
            'venue' => new VenueResource($this->whenLoaded('venue')),
            
            // Media
            'featured_image' => $this->getFirstMediaUrl('featured_image'),
            'images' => $this->getMedia('images')->map(function($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumb' => $media->getUrl('thumb'),
                    'name' => $media->name,
                ];
            }),
            
            // Availability
            'is_available' => $this->isRedeemable(),
            'remaining_uses' => $this->usage_limit ? ($this->usage_limit - $this->used_count) : null,
            'days_remaining' => $this->end_date->diffInDays(now(), false),
            
            // User-specific data (when authenticated)
            'user_can_redeem' => $this->when(
                auth()->check(),
                function() {
                    return $this->canUserRedeem(auth()->user());
                }
            ),
            'user_redemptions_count' => $this->when(
                auth()->check(),
                function() {
                    return $this->redemptions()->where('user_id', auth()->id())->count();
                }
            ),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}