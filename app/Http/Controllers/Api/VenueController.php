<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Venue\SearchVenuesRequest;
use App\Http\Resources\VenueResource;
use App\Http\Resources\VenueDetailResource;
use App\Models\Venue;
use App\Models\Category;
use App\Services\VenueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    protected $venueService;

    public function __construct(VenueService $venueService)
    {
        $this->venueService = $venueService;
    }

    public function index(SearchVenuesRequest $request): JsonResponse
    {
        try {
            $venues = $this->venueService->search($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => [
                    'venues' => VenueResource::collection($venues->items()),
                    'pagination' => [
                        'current_page' => $venues->currentPage(),
                        'last_page' => $venues->lastPage(),
                        'per_page' => $venues->perPage(),
                        'total' => $venues->total(),
                        'has_more' => $venues->hasMorePages()
                    ],
                    'filters' => $request->getAppliedFilters()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch venues',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Venue $venue): JsonResponse
    {
        try {
            $venue->load([
                'business',
                'category',
                'subcategory',
                'reviews' => function($query) {
                    $query->with('user')->latest()->limit(10);
                },
                'offers' => function($query) {
                    $query->active()->featured();
                },
                'media'
            ]);

            // Track venue view
            if (auth()->check()) {
                $this->venueService->trackVisit($venue, auth()->user());
            }

            return response()->json([
                'success' => true,
                'data' => new VenueDetailResource($venue)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch venue details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function featured(): JsonResponse
    {
        try {
            $venues = Venue::active()
                ->featured()
                ->with(['business', 'category', 'media'])
                ->orderBy('priority', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => VenueResource::collection($venues)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch featured venues',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function nearby(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'nullable|numeric|min:1|max:50'
            ]);

            $venues = Venue::active()
                ->nearby(
                    $request->latitude,
                    $request->longitude,
                    $request->radius ?? 10
                )
                ->with(['business', 'category', 'media'])
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => VenueResource::collection($venues)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch nearby venues',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function categories(): JsonResponse
    {
        try {
            $categories = Category::whereNull('parent_id')
                ->with(['children' => function($query) {
                    $query->withCount('venues');
                }])
                ->withCount('venues')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleFavorite(Request $request, Venue $venue): JsonResponse
    {
        try {
            $user = $request->user();
            
            if ($user->favorites()->where('venue_id', $venue->id)->exists()) {
                $user->favorites()->detach($venue->id);
                $isFavorite = false;
                $message = 'Venue removed from favorites';
            } else {
                $user->favorites()->attach($venue->id);
                $isFavorite = true;
                $message = 'Venue added to favorites';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'is_favorite' => $isFavorite
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update favorite status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function favorites(Request $request): JsonResponse
    {
        try {
            $favorites = $request->user()
                ->favorites()
                ->active()
                ->with(['business', 'category', 'media'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'venues' => VenueResource::collection($favorites->items()),
                    'pagination' => [
                        'current_page' => $favorites->currentPage(),
                        'last_page' => $favorites->lastPage(),
                        'per_page' => $favorites->perPage(),
                        'total' => $favorites->total(),
                        'has_more' => $favorites->hasMorePages()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch favorite venues',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function womenOnly(): JsonResponse
    {
        try {
            $venues = Venue::active()
                ->womenOnly()
                ->with(['business', 'category', 'media'])
                ->orderBy('average_rating', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'venues' => VenueResource::collection($venues->items()),
                    'pagination' => [
                        'current_page' => $venues->currentPage(),
                        'last_page' => $venues->lastPage(),
                        'per_page' => $venues->perPage(),
                        'total' => $venues->total(),
                        'has_more' => $venues->hasMorePages()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch women-only venues',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}