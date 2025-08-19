<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfferRequest;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class OfferController extends Controller
{
    protected $offerService;

    public function __construct(OfferService $offerService)
    {
        $this->offerService = $offerService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $offers = $this->offerService->getAllOffers($request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching offers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch offers',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Offer $offer): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => new OfferResource($offer)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching offer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch offer',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(OfferRequest $request): JsonResponse
    {
        try {
            if (!Gate::allows('create-offer')) {
                throw ValidationException::withMessages(['You do not have permission to create offers.']);
            }

            $offer = $this->offerService->createOffer($request->validated(), Auth::user());
            return response()->json([
                'success' => true,
                'data' => new OfferResource($offer)
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error creating offer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create offer',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(OfferRequest $request, Offer $offer): JsonResponse
    {
        try {
            if (!Gate::allows('update-offer', $offer)) {
                throw ValidationException::withMessages(['You do not have permission to update this offer.']);
            }

            $updatedOffer = $this->offerService->updateOffer($offer, $request->validated());
            return response()->json([
                'success' => true,
                'data' => new OfferResource($updatedOffer)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error updating offer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update offer',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Offer $offer): JsonResponse
    {
        try {
            if (!Gate::allows('delete-offer', $offer)) {
                throw ValidationException::withMessages(['You do not have permission to delete this offer.']);
            }

            $this->offerService->deleteOffer($offer);
            return response()->json([
                'success' => true,
                'message' => 'Offer deleted successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error deleting offer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete offer',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function toggleFavorite(Request $request, Offer $offer): JsonResponse
    {
        try {
            $user = Auth::user();
            if ($user->favorites()->where('offer_id', $offer->id)->exists()) {
                $user->favorites()->detach($offer->id);
                $isFavorite = false;
                $message = 'Offer removed from favorites';
            } else {
                $user->favorites()->attach($offer->id);
                $isFavorite = true;
                $message = 'Offer added to favorites';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'is_favorite' => $isFavorite
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling favorite status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update favorite status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffers(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffers($user, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavorites(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('query');
            $offers = $this->offerService->searchOffers($query, $request->all());

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching offers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to search offers',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function filter(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            $offers = $this->offerService->filterOffers($filters);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error filtering offers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to filter offers',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $stats = $this->offerService->getOfferStats();
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching offer stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch offer stats',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function report(Request $request, Offer $offer): JsonResponse
    {
        try {
            $report = $this->offerService->reportOffer($offer, $request->all());
            return response()->json([
                'success' => true,
                'data' => $report
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error reporting offer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to report offer',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userStats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $stats = $this->offerService->getUserOfferStats($user);
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offer stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offer stats',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesStats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $stats = $this->offerService->getUserFavoritesStats($user);
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites stats',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersStats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $stats = $this->offerService->getUserOffersStats($user);
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers stats',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesOffers(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites offers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites offers',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersByCategory(Request $request, $categoryId): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffersByCategory($user, $categoryId, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers by category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers by category',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesByCategory(Request $request, $categoryId): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->whereHas('offer.category', function ($query) use ($categoryId) {
                    $query->where('id', $categoryId);
                })
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites by category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites by category',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersByBusiness(Request $request, $businessId): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffersByBusiness($user, $businessId, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers by business: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers by business',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesByBusiness(Request $request, $businessId): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->whereHas('offer.business', function ($query) use ($businessId) {
                    $query->where('id', $businessId);
                })
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites by business: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites by business',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersByDate(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffersByDate($user, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers by date: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers by date',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesByDate(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites by date: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites by date',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersByPrice(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffersByPrice($user, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers by price: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers by price',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesByPrice(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites by price: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites by price',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersByRating(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffersByRating($user, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers by rating: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers by rating',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesByRating(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites by rating: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites by rating',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersByPopularity(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffersByPopularity($user, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers by popularity: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers by popularity',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesByPopularity(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites by popularity: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites by popularity',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersByDistance(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffersByDistance($user, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers by distance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers by distance',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesByDistance(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites by distance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites by distance',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersByType(Request $request, $type): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffersByType($user, $type, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers by type: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers by type',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesByType(Request $request, $type): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->whereHas('offer', function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites by type: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites by type',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersByStatus(Request $request, $status): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffersByStatus($user, $status, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers by status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers by status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesByStatus(Request $request, $status): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->whereHas('offer', function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites by status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites by status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userOffersByDateRange(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $offers = $this->offerService->getUserOffersByDateRange($user, $request->all());
            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($offers)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers by date range: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user offers by date range',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userFavoritesByDateRange(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $favorites = $user->favorites()
                ->with(['offer', 'offer.business', 'offer.category'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OfferResource::collection($favorites->items()),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'last_page' => $favorites->lastPage(),
                    'total' => $favorites->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user favorites by date range: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user favorites by date range',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
