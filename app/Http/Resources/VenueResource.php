<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
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
            'distance' => $this->when(isset($this->distance), $this->distance),
            
            // Relationships
            'business' => new BusinessResource($this->whenLoaded('business')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'subcategory' => new CategoryResource($this->whenLoaded('subcategory')),
            
            // Media
            'featured_image' => $this->getFirstMediaUrl('featured_image'),
            'gallery' => $this->getMedia('gallery')->map(function($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumb' => $media->getUrl('thumb'),
                    'name' => $media->name,
                ];
            }),
            
            // User-specific data (when authenticated)
            'is_favorite' => $this->when(
                auth()->check(),
                function() {
                    return auth()->user()->favorites()->where('venue_id', $this->id)->exists();
                }
            ),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}