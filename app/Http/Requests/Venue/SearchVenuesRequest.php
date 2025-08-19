<?php

namespace App\Http\Requests\Venue;

use Illuminate\Foundation\Http\FormRequest;

class SearchVenuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'emirate' => ['nullable', 'in:Abu Dhabi,Dubai,Sharjah,Ajman,Umm Al Quwain,Ras Al Khaimah,Fujairah'],
            'city' => ['nullable', 'string', 'max:100'],
            'price_range' => ['nullable', 'in:$,$$,$$$,$$$$'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],
            'women_only' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'min_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:50'],
            'sort_by' => ['nullable', 'in:relevance,rating,reviews,visits,newest,distance'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Selected category does not exist.',
            'subcategory_id.exists' => 'Selected subcategory does not exist.',
            'emirate.in' => 'Please select a valid emirate.',
            'price_range.in' => 'Please select a valid price range.',
            'min_rating.between' => 'Rating must be between 1 and 5.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'radius.between' => 'Radius must be between 1 and 50 kilometers.',
            'sort_by.in' => 'Invalid sort option.',
            'per_page.max' => 'Maximum 50 results per page allowed.',
        ];
    }

    public function getAppliedFilters(): array
    {
        $filters = [];
        
        if ($this->filled('search')) {
            $filters['search'] = $this->search;
        }
        
        if ($this->filled('category_id')) {
            $filters['category'] = \App\Models\Category::find($this->category_id)?->name;
        }
        
        if ($this->filled('emirate')) {
            $filters['emirate'] = $this->emirate;
        }
        
        if ($this->filled('price_range')) {
            $filters['price_range'] = $this->price_range;
        }
        
        if ($this->filled('women_only') && $this->women_only) {
            $filters['women_only'] = true;
        }
        
        if ($this->filled('featured') && $this->featured) {
            $filters['featured'] = true;
        }
        
        if ($this->filled('min_rating')) {
            $filters['min_rating'] = $this->min_rating;
        }
        
        return $filters;
    }
}