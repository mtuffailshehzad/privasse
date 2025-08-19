<?php

namespace App\Services;

use App\Models\Venue;
use App\Models\VenueVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VenueService
{
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Venue::query()->with(['business', 'category', 'media']);

        // Apply base filters
        $query->active();

        // Search by name or description
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('description_ar', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filter by subcategory
        if (!empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
        }

        // Filter by emirate
        if (!empty($filters['emirate'])) {
            $query->where('emirate', $filters['emirate']);
        }

        // Filter by city
        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        // Filter by price range
        if (!empty($filters['price_range'])) {
            $query->where('price_range', $filters['price_range']);
        }

        // Filter by amenities
        if (!empty($filters['amenities'])) {
            $amenities = is_array($filters['amenities']) ? $filters['amenities'] : [$filters['amenities']];
            foreach ($amenities as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        // Women-only filter
        if (!empty($filters['women_only'])) {
            $query->where('is_women_only', true);
        }

        // Featured filter
        if (!empty($filters['featured'])) {
            $query->where('is_featured', true);
        }

        // Rating filter
        if (!empty($filters['min_rating'])) {
            $query->where('average_rating', '>=', $filters['min_rating']);
        }

        // Location-based search
        if (!empty($filters['latitude']) && !empty($filters['longitude'])) {
            $radius = $filters['radius'] ?? 10; // Default 10km radius
            $query->nearby($filters['latitude'], $filters['longitude'], $radius);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'relevance';
        switch ($sortBy) {
            case 'rating':
                $query->orderByDesc('average_rating');
                break;
            case 'reviews':
                $query->orderByDesc('total_reviews');
                break;
            case 'visits':
                $query->orderByDesc('total_visits');
                break;
            case 'newest':
                $query->latest();
                break;
            case 'distance':
                // Already sorted by distance if location provided
                if (empty($filters['latitude']) || empty($filters['longitude'])) {
                    $query->latest();
                }
                break;
            default: // relevance
                $query->orderByDesc('is_featured')
                      ->orderByDesc('average_rating')
                      ->orderByDesc('total_reviews');
        }

        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    public function trackVisit(Venue $venue, User $user, array $metadata = []): VenueVisit
    {
        // Check if user already visited today
        $existingVisit = VenueVisit::where('user_id', $user->id)
            ->where('venue_id', $venue->id)
            ->whereDate('visited_at', today())
            ->first();

        if ($existingVisit) {
            return $existingVisit;
        }

        // Create new visit record
        $visit = VenueVisit::create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'visited_at' => now(),
            'source' => $metadata['source'] ?? 'app',
            'metadata' => $metadata
        ]);

        // Update venue visit count
        $venue->incrementVisits();

        // Log activity
        activity()
            ->performedOn($venue)
            ->causedBy($user)
            ->log('User visited venue');

        return $visit;
    }

    public function getPopularVenues(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember("popular_venues_{$limit}", 3600, function() use ($limit) {
            return Venue::active()
                ->with(['business', 'category', 'media'])
                ->orderByDesc('total_visits')
                ->orderByDesc('average_rating')
                ->limit($limit)
                ->get();
        });
    }

    public function getTrendingVenues(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember("trending_venues_{$limit}", 1800, function() use ($limit) {
            // Get venues with most visits in the last 7 days
            $venueIds = VenueVisit::select('venue_id', DB::raw('COUNT(*) as visit_count'))
                ->where('visited_at', '>=', now()->subDays(7))
                ->groupBy('venue_id')
                ->orderByDesc('visit_count')
                ->limit($limit)
                ->pluck('venue_id');

            return Venue::active()
                ->with(['business', 'category', 'media'])
                ->whereIn('id', $venueIds)
                ->get();
        });
    }

    public function getRecommendedVenues(User $user, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        // Get user's favorite categories based on visits and favorites
        $favoriteCategories = $this->getUserFavoriteCategories($user);
        
        // Get venues in those categories that user hasn't visited
        $visitedVenueIds = $user->visits()->pluck('venue_id');
        
        $query = Venue::active()
            ->with(['business', 'category', 'media'])
            ->whereNotIn('id', $visitedVenueIds);

        if (!empty($favoriteCategories)) {
            $query->whereIn('category_id', $favoriteCategories);
        }

        return $query->orderByDesc('average_rating')
                    ->orderByDesc('is_featured')
                    ->limit($limit)
                    ->get();
    }

    public function getVenueAnalytics(Venue $venue, array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();

        // Visit analytics
        $totalVisits = $venue->visits()
            ->whereBetween('visited_at', [$dateFrom, $dateTo])
            ->count();

        $uniqueVisitors = $venue->visits()
            ->whereBetween('visited_at', [$dateFrom, $dateTo])
            ->distinct('user_id')
            ->count();

        // Daily visits trend
        $dailyVisits = $venue->visits()
            ->selectRaw('DATE(visited_at) as date, COUNT(*) as count')
            ->whereBetween('visited_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Review analytics
        $reviewStats = [
            'total_reviews' => $venue->reviews()->count(),
            'average_rating' => $venue->average_rating,
            'rating_distribution' => $venue->reviews()
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray()
        ];

        // User demographics
        $userDemographics = $this->getVenueUserDemographics($venue, $dateFrom, $dateTo);

        return [
            'visits' => [
                'total' => $totalVisits,
                'unique_visitors' => $uniqueVisitors,
                'daily_trend' => $dailyVisits->toArray()
            ],
            'reviews' => $reviewStats,
            'demographics' => $userDemographics
        ];
    }

    protected function getUserFavoriteCategories(User $user): array
    {
        // Get categories from user's favorites
        $favoriteCategories = $user->favorites()
            ->pluck('category_id')
            ->toArray();

        // Get categories from user's most visited venues
        $visitedCategories = $user->visits()
            ->join('venues', 'venue_visits.venue_id', '=', 'venues.id')
            ->select('venues.category_id', DB::raw('COUNT(*) as visit_count'))
            ->groupBy('venues.category_id')
            ->orderByDesc('visit_count')
            ->limit(5)
            ->pluck('category_id')
            ->toArray();

        return array_unique(array_merge($favoriteCategories, $visitedCategories));
    }

    protected function getVenueUserDemographics(Venue $venue, $dateFrom, $dateTo): array
    {
        $visitors = $venue->visits()
            ->with('user')
            ->whereBetween('visited_at', [$dateFrom, $dateTo])
            ->get()
            ->pluck('user')
            ->unique('id');

        // Age groups
        $ageGroups = $visitors->groupBy(function($user) {
            if (!$user->date_of_birth) return 'Unknown';
            
            $age = $user->date_of_birth->age;
            if ($age < 25) return '18-24';
            if ($age < 35) return '25-34';
            if ($age < 45) return '35-44';
            if ($age < 55) return '45-54';
            return '55+';
        })->map->count();

        // Subscription types
        $subscriptionTypes = $visitors->groupBy('subscription_type')
            ->map->count();

        return [
            'age_groups' => $ageGroups->toArray(),
            'subscription_types' => $subscriptionTypes->toArray(),
            'total_unique_visitors' => $visitors->count()
        ];
    }
}