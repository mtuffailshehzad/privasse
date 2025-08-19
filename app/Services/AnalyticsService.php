<?php

namespace App\Services;

use App\Models\User;
use App\Models\Business;
use App\Models\Venue;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\VenueVisit;
use App\Models\OfferRedemption;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    public function getDashboardChartData(): array
    {
        return Cache::remember('admin_dashboard_charts', 3600, function () {
            return [
                'user_growth' => $this->getUserGrowthData(),
                'revenue' => $this->getRevenueData(),
                'venue_performance' => $this->getVenuePerformanceData(),
                'offer_redemptions' => $this->getOfferRedemptionData(),
            ];
        });
    }

    public function getUserGrowthData(): array
    {
        $data = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'data' => $data->pluck('count')->toArray()
        ];
    }

    public function getRevenueData(): array
    {
        $data = Payment::selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'data' => $data->pluck('total')->toArray()
        ];
    }

    public function getVenuePerformanceData(): array
    {
        $topVenues = Venue::select('venues.name', DB::raw('COUNT(venue_visits.id) as visit_count'))
            ->leftJoin('venue_visits', 'venues.id', '=', 'venue_visits.venue_id')
            ->where('venue_visits.created_at', '>=', now()->subDays(30))
            ->groupBy('venues.id', 'venues.name')
            ->orderByDesc('visit_count')
            ->limit(10)
            ->get();

        return [
            'labels' => $topVenues->pluck('name')->toArray(),
            'data' => $topVenues->pluck('visit_count')->toArray()
        ];
    }

    public function getOfferRedemptionData(): array
    {
        $data = OfferRedemption::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'data' => $data->pluck('count')->toArray()
        ];
    }

    public function getUserAnalytics(array $filters = []): array
    {
        $query = User::query();

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $totalUsers = $query->count();
        $activeUsers = $query->where('is_active', true)->count();
        $subscribedUsers = $query->where('subscription_status', 'active')->count();

        // User engagement metrics
        $engagementData = [
            'daily_active_users' => $this->getDailyActiveUsers($filters),
            'retention_rate' => $this->getUserRetentionRate($filters),
            'churn_rate' => $this->getUserChurnRate($filters),
        ];

        // Subscription metrics
        $subscriptionData = [
            'conversion_rate' => $totalUsers > 0 ? ($subscribedUsers / $totalUsers) * 100 : 0,
            'subscription_breakdown' => $this->getSubscriptionBreakdown($filters),
            'revenue_per_user' => $this->getRevenuePerUser($filters),
        ];

        return [
            'overview' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'subscribed_users' => $subscribedUsers,
                'growth_rate' => $this->getUserGrowthRate($filters),
            ],
            'engagement' => $engagementData,
            'subscriptions' => $subscriptionData,
        ];
    }

    public function getBusinessAnalytics(array $filters = []): array
    {
        $query = Business::query();

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $totalBusinesses = $query->count();
        $verifiedBusinesses = $query->where('verification_status', 'verified')->count();
        $activeSubscriptions = $query->where('subscription_status', 'active')->count();

        // Performance metrics
        $performanceData = [
            'top_performing_businesses' => $this->getTopPerformingBusinesses($filters),
            'average_revenue_per_business' => $this->getAverageRevenuePerBusiness($filters),
            'venue_distribution' => $this->getVenueDistribution($filters),
        ];

        return [
            'overview' => [
                'total_businesses' => $totalBusinesses,
                'verified_businesses' => $verifiedBusinesses,
                'active_subscriptions' => $activeSubscriptions,
                'verification_rate' => $totalBusinesses > 0 ? ($verifiedBusinesses / $totalBusinesses) * 100 : 0,
            ],
            'performance' => $performanceData,
        ];
    }

    public function getPlatformRevenue(array $filters = []): array
    {
        $query = Payment::where('status', 'completed');

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $totalRevenue = $query->sum('amount');
        $userRevenue = $query->whereNotNull('user_id')->sum('amount');
        $businessRevenue = $query->whereNotNull('business_id')->sum('amount');

        // Monthly revenue trend
        $monthlyRevenue = Payment::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return [
            'overview' => [
                'total_revenue' => $totalRevenue,
                'user_revenue' => $userRevenue,
                'business_revenue' => $businessRevenue,
                'average_transaction' => $query->avg('amount'),
            ],
            'trends' => [
                'monthly_revenue' => $monthlyRevenue->map(function($item) {
                    return [
                        'period' => Carbon::create($item->year, $item->month)->format('M Y'),
                        'amount' => $item->total
                    ];
                })->toArray(),
                'growth_rate' => $this->getRevenueGrowthRate($filters),
            ],
        ];
    }

    protected function getDailyActiveUsers(array $filters): int
    {
        return User::where('last_login_at', '>=', now()->subDay())->count();
    }

    protected function getUserRetentionRate(array $filters): float
    {
        $totalUsers = User::count();
        $activeUsers = User::where('last_login_at', '>=', now()->subDays(30))->count();
        
        return $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0;
    }

    protected function getUserChurnRate(array $filters): float
    {
        $totalSubscribed = User::where('subscription_status', 'active')->count();
        $cancelled = User::where('subscription_status', 'cancelled')
            ->where('updated_at', '>=', now()->subMonth())
            ->count();
        
        return $totalSubscribed > 0 ? ($cancelled / $totalSubscribed) * 100 : 0;
    }

    protected function getSubscriptionBreakdown(array $filters): array
    {
        return User::selectRaw('subscription_type, COUNT(*) as count')
            ->whereNotNull('subscription_type')
            ->where('subscription_status', 'active')
            ->groupBy('subscription_type')
            ->get()
            ->pluck('count', 'subscription_type')
            ->toArray();
    }

    protected function getRevenuePerUser(array $filters): float
    {
        $totalUsers = User::count();
        $totalRevenue = Payment::where('status', 'completed')
            ->whereNotNull('user_id')
            ->sum('amount');
        
        return $totalUsers > 0 ? $totalRevenue / $totalUsers : 0;
    }

    protected function getUserGrowthRate(array $filters): float
    {
        $currentMonth = User::whereMonth('created_at', now()->month)->count();
        $previousMonth = User::whereMonth('created_at', now()->subMonth()->month)->count();
        
        return $previousMonth > 0 ? (($currentMonth - $previousMonth) / $previousMonth) * 100 : 0;
    }

    protected function getTopPerformingBusinesses(array $filters): array
    {
        return Business::select('businesses.name', DB::raw('COUNT(venue_visits.id) as total_visits'))
            ->join('venues', 'businesses.id', '=', 'venues.business_id')
            ->leftJoin('venue_visits', 'venues.id', '=', 'venue_visits.venue_id')
            ->groupBy('businesses.id', 'businesses.name')
            ->orderByDesc('total_visits')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getAverageRevenuePerBusiness(array $filters): float
    {
        $totalBusinesses = Business::where('subscription_status', 'active')->count();
        $totalRevenue = Payment::where('status', 'completed')
            ->whereNotNull('business_id')
            ->sum('amount');
        
        return $totalBusinesses > 0 ? $totalRevenue / $totalBusinesses : 0;
    }

    protected function getVenueDistribution(array $filters): array
    {
        return Venue::selectRaw('emirate, COUNT(*) as count')
            ->where('status', 'approved')
            ->groupBy('emirate')
            ->get()
            ->pluck('count', 'emirate')
            ->toArray();
    }

    protected function getRevenueGrowthRate(array $filters): float
    {
        $currentMonth = Payment::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        
        $previousMonth = Payment::where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->sum('amount');
        
        return $previousMonth > 0 ? (($currentMonth - $previousMonth) / $previousMonth) * 100 : 0;
    }
}