<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'address' => $this->address,
            'address_ar' => $this->address_ar,
            'city' => $this->city,
            'emirate' => $this->emirate,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'instagram' => $this->instagram,
            'facebook' => $this->facebook,
            'operating_hours' => $this->operating_hours,
            'amenities' => $this->amenities,
            'price_range' => $this->price_range,
            'dress_code' => $this->dress_code,
            'age_restriction' => $this->age_restriction,
            'is_women_only' => $this->is_women_only,
            'is_featured' => $this->is_featured,
            'average_rating' => $this->average_rating,
            'total_reviews' => $this->total_reviews,
            'total_visits' => $this->total_visits,
            
            // Relationships
            'business' => new BusinessResource($this->whenLoaded('business')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'subcategory' => new CategoryResource($this->whenLoaded('subcategory')),
            
            // Reviews
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'rating_breakdown' => $this->when(
                $this->relationLoaded('reviews'),
                function() {
                    return $this->reviews->groupBy('rating')->map->count();
                }
            ),
            
            // Active offers
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
            
            // Media
            'featured_image' => $this->getFirstMediaUrl('featured_image'),
            'gallery' => $this->getMedia('gallery')->map(function($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'large' => $media->getUrl('large'),
                    'thumb' => $media->getUrl('thumb'),
                    'name' => $media->name,
                    'alt' => $media->getCustomProperty('alt'),
                ];
            }),
            'menu' => $this->getMedia('menu')->map(function($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'name' => $media->name,
                    'type' => $media->mime_type,
                ];
            }),
            
            // User-specific data (when authenticated)
            'is_favorite' => $this->when(
                auth()->check(),
                function() {
                    return auth()->user()->favorites()->where('venue_id', $this->id)->exists();
                }
            ),
            'user_has_visited' => $this->when(
                auth()->check(),
                function() {
                    return auth()->user()->visits()->where('venue_id', $this->id)->exists();
                }
            ),
            'user_review' => $this->when(
                auth()->check() && $this->relationLoaded('reviews'),
                function() {
                    return new ReviewResource(
                        $this->reviews->where('user_id', auth()->id())->first()
                    );
                }
            ),
            
            // Similar venues
            'similar_venues' => $this->when(
                $this->relationLoaded('category'),
                function() {
                    return VenueResource::collection(
                        $this->category->venues()
                            ->active()
                            ->where('id', '!=', $this->id)
                            ->orderByDesc('average_rating')
                            ->limit(5)
                            ->get()
                    );
                }
            ),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}