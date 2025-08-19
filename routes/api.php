<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VenueController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('send-otp', [AuthController::class, 'sendOtp']);
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
        
        // Social login routes
        Route::get('social/{provider}', [AuthController::class, 'redirectToProvider']);
        Route::get('social/{provider}/callback', [AuthController::class, 'handleProviderCallback']);
    });

    // Public venue routes
    Route::prefix('venues')->group(function () {
        Route::get('/', [VenueController::class, 'index']);
        Route::get('featured', [VenueController::class, 'featured']);
        Route::get('women-only', [VenueController::class, 'womenOnly']);
        Route::get('nearby', [VenueController::class, 'nearby']);
        Route::get('categories', [VenueController::class, 'categories']);
        Route::get('{venue}', [VenueController::class, 'show']);
    });

    // Public offer routes
    Route::prefix('offers')->group(function () {
        Route::get('/', [OfferController::class, 'index']);
        Route::get('featured', [OfferController::class, 'featured']);
        Route::get('{offer}', [OfferController::class, 'show']);
    });

    // Business registration
    Route::post('business/register', [BusinessController::class, 'register']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh-token', [AuthController::class, 'refreshToken']);
        Route::get('profile', [AuthController::class, 'profile']);
    });

    // User routes
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::post('avatar', [UserController::class, 'uploadAvatar']);
        Route::delete('avatar', [UserController::class, 'deleteAvatar']);
        Route::get('preferences', [UserController::class, 'getPreferences']);
        Route::put('preferences', [UserController::class, 'updatePreferences']);
        Route::get('subscription', [UserController::class, 'getSubscription']);
        Route::get('visits', [UserController::class, 'getVisits']);
        Route::get('favorites', [VenueController::class, 'favorites']);
        Route::post('venues/{venue}/favorite', [VenueController::class, 'toggleFavorite']);
        Route::delete('account', [UserController::class, 'deleteAccount']);
    });

    // Venue routes
    Route::prefix('venues')->group(function () {
        Route::post('{venue}/visit', [VenueController::class, 'recordVisit']);
        Route::post('{venue}/review', [ReviewController::class, 'store']);
        Route::put('reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);
    });

    // Offer routes
    Route::prefix('offers')->group(function () {
        Route::post('{offer}/redeem', [OfferController::class, 'redeem']);
        Route::get('my-redemptions', [OfferController::class, 'myRedemptions']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::post('subscribe', [PaymentController::class, 'subscribe']);
        Route::post('cancel-subscription', [PaymentController::class, 'cancelSubscription']);
        Route::get('history', [PaymentController::class, 'paymentHistory']);
        Route::post('refund', [PaymentController::class, 'requestRefund']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('{notification}', [NotificationController::class, 'destroy']);
        Route::put('settings', [NotificationController::class, 'updateSettings']);
    });
});

// Business API routes
Route::prefix('v1/business')->middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [BusinessController::class, 'dashboard']);
    Route::get('profile', [BusinessController::class, 'profile']);
    Route::put('profile', [BusinessController::class, 'updateProfile']);
    Route::post('documents', [BusinessController::class, 'uploadDocuments']);
    
    // Venue management
    Route::prefix('venues')->group(function () {
        Route::get('/', [BusinessController::class, 'getVenues']);
        Route::post('/', [BusinessController::class, 'createVenue']);
        Route::get('{venue}', [BusinessController::class, 'getVenue']);
        Route::put('{venue}', [BusinessController::class, 'updateVenue']);
        Route::delete('{venue}', [BusinessController::class, 'deleteVenue']);
        Route::post('{venue}/media', [BusinessController::class, 'uploadVenueMedia']);
    });
    
    // Offer management
    Route::prefix('offers')->group(function () {
        Route::get('/', [BusinessController::class, 'getOffers']);
        Route::post('/', [BusinessController::class, 'createOffer']);
        Route::get('{offer}', [BusinessController::class, 'getOffer']);
        Route::put('{offer}', [BusinessController::class, 'updateOffer']);
        Route::delete('{offer}', [BusinessController::class, 'deleteOffer']);
        Route::get('{offer}/redemptions', [BusinessController::class, 'getOfferRedemptions']);
    });
    
    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('overview', [AnalyticsController::class, 'businessOverview']);
        Route::get('venues', [AnalyticsController::class, 'venueAnalytics']);
        Route::get('offers', [AnalyticsController::class, 'offerAnalytics']);
        Route::get('revenue', [AnalyticsController::class, 'revenueAnalytics']);
        Route::get('customers', [AnalyticsController::class, 'customerAnalytics']);
    });
    
    // Subscription management
    Route::prefix('subscription')->group(function () {
        Route::get('/', [BusinessController::class, 'getSubscription']);
        Route::post('upgrade', [BusinessController::class, 'upgradeSubscription']);
        Route::post('cancel', [BusinessController::class, 'cancelSubscription']);
    });
});

// Admin API routes (protected by admin middleware)
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('dashboard', [AdminController::class, 'dashboard']);
    
    // User management
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminController::class, 'getUsers']);
        Route::get('{user}', [AdminController::class, 'getUser']);
        Route::put('{user}', [AdminController::class, 'updateUser']);
        Route::post('{user}/suspend', [AdminController::class, 'suspendUser']);
        Route::post('{user}/activate', [AdminController::class, 'activateUser']);
        Route::delete('{user}', [AdminController::class, 'deleteUser']);
    });
    
    // Business management
    Route::prefix('businesses')->group(function () {
        Route::get('/', [AdminController::class, 'getBusinesses']);
        Route::get('{business}', [AdminController::class, 'getBusiness']);
        Route::post('{business}/verify', [AdminController::class, 'verifyBusiness']);
        Route::post('{business}/reject', [AdminController::class, 'rejectBusiness']);
        Route::put('{business}', [AdminController::class, 'updateBusiness']);
        Route::delete('{business}', [AdminController::class, 'deleteBusiness']);
    });
    
    // Content moderation
    Route::prefix('moderation')->group(function () {
        Route::get('venues', [AdminController::class, 'getPendingVenues']);
        Route::post('venues/{venue}/approve', [AdminController::class, 'approveVenue']);
        Route::post('venues/{venue}/reject', [AdminController::class, 'rejectVenue']);
        Route::get('offers', [AdminController::class, 'getPendingOffers']);
        Route::post('offers/{offer}/approve', [AdminController::class, 'approveOffer']);
        Route::post('offers/{offer}/reject', [AdminController::class, 'rejectOffer']);
        Route::get('reviews', [AdminController::class, 'getPendingReviews']);
        Route::post('reviews/{review}/approve', [AdminController::class, 'approveReview']);
        Route::post('reviews/{review}/reject', [AdminController::class, 'rejectReview']);
    });
    
    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('overview', [AnalyticsController::class, 'adminOverview']);
        Route::get('users', [AnalyticsController::class, 'userAnalytics']);
        Route::get('businesses', [AnalyticsController::class, 'businessAnalytics']);
        Route::get('revenue', [AnalyticsController::class, 'platformRevenue']);
        Route::get('engagement', [AnalyticsController::class, 'engagementAnalytics']);
    });
    
    // System management
    Route::prefix('system')->group(function () {
        Route::get('settings', [AdminController::class, 'getSettings']);
        Route::put('settings', [AdminController::class, 'updateSettings']);
        Route::get('logs', [AdminController::class, 'getLogs']);
        Route::post('backup', [AdminController::class, 'createBackup']);
        Route::get('health', [AdminController::class, 'systemHealth']);
    });
});

// Webhook routes
Route::prefix('webhooks')->group(function () {
    Route::post('stripe', [PaymentController::class, 'stripeWebhook']);
    Route::post('telr', [PaymentController::class, 'telrWebhook']);
});

// Health check
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0')
    ]);
});