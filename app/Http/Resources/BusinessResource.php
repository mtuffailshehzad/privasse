<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'owner_name' => $this->owner_name,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
            'subscription_type' => $this->subscription_type,
            'subscription_status' => $this->subscription_status,
            'subscription_expires_at' => $this->subscription_expires_at,
            'is_featured' => $this->is_featured,
            'is_women_only' => $this->is_women_only,
            'verified_at' => $this->verified_at,
            
            // Logo
            'logo' => $this->getFirstMediaUrl('logo'),
            
            // Venue count
            'venues_count' => $this->when(
                $this->relationLoaded('venues'),
                $this->venues->count()
            ),
            
            // Hide sensitive information from public API
            'trade_license_number' => $this->when(
                $request->user() && ($request->user()->hasRole('admin') || $request->user()->id === $this->owner_id),
                $this->trade_license_number
            ),
            'trade_license_expiry' => $this->when(
                $request->user() && ($request->user()->hasRole('admin') || $request->user()->id === $this->owner_id),
                $this->trade_license_expiry
            ),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}