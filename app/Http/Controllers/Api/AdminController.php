<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\BusinessResource;
use App\Models\User;
use App\Models\Business;
use App\Models\Venue;
use App\Models\Offer;
use App\Models\Payment;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AdminController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_subscriptions' => User::where('subscription_status', 'active')->count(),
                'total_venues' => Venue::count(),
                'pending_venues' => Venue::where('status', 'pending')->count(),
                'total_businesses' => Business::count(),
                'pending_businesses' => Business::where('verification_status', 'pending')->count(),
                'monthly_revenue' => Payment::where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount'),
                'total_offers' => Offer::count(),
                'active_offers' => Offer::active()->count(),
            ];

            $recentActivity = [
                'new_users_today' => User::whereDate('created_at', today())->count(),
                'new_businesses_today' => Business::whereDate('created_at', today())->count(),
                'offers_redeemed_today' => \App\Models\OfferRedemption::whereDate('created_at', today())->count(),
            ];

            $chartData = $this->analyticsService->getDashboardChartData();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_activity' => $recentActivity,
                    'chart_data' => $chartData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUsers(Request $request): JsonResponse
    {
        try {
            $query = User::with(['subscriptions', 'media']);

            // Apply filters
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->has('subscription_status')) {
                $query->where('subscription_status', $request->subscription_status);
            }

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $users = $query->latest()->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => UserResource::collection($users->items()),
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUser(User $user): JsonResponse
    {
        try {
            $user->load([
                'subscriptions',
                'visits.venue',
                'reviews.venue',
                'favorites',
                'offerRedemptions.offer',
                'media'
            ]);

            $analytics = [
                'total_visits' => $user->visits()->count(),
                'total_reviews' => $user->reviews()->count(),
                'total_favorites' => $user->favorites()->count(),
                'offers_redeemed' => $user->offerRedemptions()->count(),
                'last_activity' => $user->last_login_at,
                'account_age' => $user->created_at->diffInDays(now()),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => new UserResource($user),
                    'analytics' => $analytics
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        try {
            $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
                'subscription_status' => 'sometimes|in:active,inactive,expired,cancelled',
                'is_active' => 'sometimes|boolean',
            ]);

            $user->update($request->only([
                'first_name', 'last_name', 'email', 'phone', 
                'subscription_status', 'is_active'
            ]));

            // Log admin action
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties($request->only([
                    'first_name', 'last_name', 'email', 'phone', 
                    'subscription_status', 'is_active'
                ]))
                ->log('Admin updated user');

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => new UserResource($user->fresh())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function suspendUser(User $user): JsonResponse
    {
        try {
            $user->update(['is_active' => false]);
            
            // Revoke all tokens
            $user->tokens()->delete();

            // Log admin action
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('Admin suspended user');

            return response()->json([
                'success' => true,
                'message' => 'User suspended successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activateUser(User $user): JsonResponse
    {
        try {
            $user->update(['is_active' => true]);

            // Log admin action
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('Admin activated user');

            return response()->json([
                'success' => true,
                'message' => 'User activated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBusinesses(Request $request): JsonResponse
    {
        try {
            $query = Business::with(['venues', 'media']);

            // Apply filters
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('trade_license_number', 'like', "%{$search}%");
                });
            }

            if ($request->has('verification_status')) {
                $query->where('verification_status', $request->verification_status);
            }

            if ($request->has('subscription_status')) {
                $query->where('subscription_status', $request->subscription_status);
            }

            $businesses = $query->latest()->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'businesses' => BusinessResource::collection($businesses->items()),
                    'pagination' => [
                        'current_page' => $businesses->currentPage(),
                        'last_page' => $businesses->lastPage(),
                        'per_page' => $businesses->perPage(),
                        'total' => $businesses->total(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch businesses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyBusiness(Request $request, Business $business): JsonResponse
    {
        try {
            $request->validate([
                'notes' => 'nullable|string|max:1000'
            ]);

            $business->update([
                'verification_status' => 'verified',
                'verified_at' => now(),
                'verification_notes' => $request->notes
            ]);

            // Log admin action
            activity()
                ->performedOn($business)
                ->causedBy(auth()->user())
                ->withProperties(['notes' => $request->notes])
                ->log('Admin verified business');

            // Send notification to business
            $business->notify(new \App\Notifications\BusinessVerified());

            return response()->json([
                'success' => true,
                'message' => 'Business verified successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify business',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rejectBusiness(Request $request, Business $business): JsonResponse
    {
        try {
            $request->validate([
                'notes' => 'required|string|max:1000'
            ]);

            $business->update([
                'verification_status' => 'rejected',
                'rejected_at' => now(),
                'verification_notes' => $request->notes
            ]);

            // Log admin action
            activity()
                ->performedOn($business)
                ->causedBy(auth()->user())
                ->withProperties(['notes' => $request->notes])
                ->log('Admin rejected business');

            // Send notification to business
            $business->notify(new \App\Notifications\BusinessRejected($request->notes));

            return response()->json([
                'success' => true,
                'message' => 'Business rejected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject business',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingVenues(): JsonResponse
    {
        try {
            $venues = Venue::where('status', 'pending')
                ->with(['business', 'category', 'media'])
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'venues' => \App\Http\Resources\VenueResource::collection($venues->items()),
                    'pagination' => [
                        'current_page' => $venues->currentPage(),
                        'last_page' => $venues->lastPage(),
                        'per_page' => $venues->perPage(),
                        'total' => $venues->total(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending venues',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approveVenue(Request $request, Venue $venue): JsonResponse
    {
        try {
            $venue->update(['status' => 'approved']);

            // Log admin action
            activity()
                ->performedOn($venue)
                ->causedBy(auth()->user())
                ->log('Admin approved venue');

            return response()->json([
                'success' => true,
                'message' => 'Venue approved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve venue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rejectVenue(Request $request, Venue $venue): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:1000'
            ]);

            $venue->update([
                'status' => 'rejected',
                'metadata' => array_merge($venue->metadata ?? [], [
                    'rejection_reason' => $request->reason,
                    'rejected_at' => now(),
                    'rejected_by' => auth()->id()
                ])
            ]);

            // Log admin action
            activity()
                ->performedOn($venue)
                ->causedBy(auth()->user())
                ->withProperties(['reason' => $request->reason])
                ->log('Admin rejected venue');

            return response()->json([
                'success' => true,
                'message' => 'Venue rejected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject venue',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}