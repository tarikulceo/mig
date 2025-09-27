<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use App\Traits\PreventDemoModeChanges; // Temporarily disabled for sales representatives

class SalesRepresentative extends Model
{
    use SoftDeletes; // Removed PreventDemoModeChanges temporarily

    protected $fillable = [
        'user_id',
        'employee_id',
        'designation',
        'hire_date',
        'territory_id',
        'sales_target_monthly',
        'sales_target_quarterly',
        'sales_target_yearly',
        'commission_rate',
        'base_salary',
        'manager_id',
        'status',
        'phone',
        'address',
        'profile_image',
        'notes',
        // GPS and Mobile Features
        'current_latitude',
        'current_longitude',
        'last_location_update',
        'gps_enabled',
        'device_info',
        'fcm_token',
        // Performance Tracking
        'total_visits',
        'successful_visits',
        'total_sales',
        'total_commission_earned',
        'last_active_date'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'last_active_date' => 'date',
        'last_location_update' => 'datetime',
        'sales_target_monthly' => 'decimal:2',
        'sales_target_quarterly' => 'decimal:2',
        'sales_target_yearly' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'base_salary' => 'decimal:2',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'total_sales' => 'decimal:2',
        'total_commission_earned' => 'decimal:2',
        'status' => 'boolean',
        'gps_enabled' => 'boolean',
        'device_info' => 'array'
    ];

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function territory()
    {
        return $this->belongsTo(SalesTerritory::class, 'territory_id');
    }

    public function manager()
    {
        return $this->belongsTo(SalesRepresentative::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(SalesRepresentative::class, 'manager_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'sales_rep_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'sales_rep_id');
    }

    public function commissions()
    {
        return $this->hasMany(SalesCommission::class, 'sales_rep_id');
    }

    public function retailStores()
    {
        return $this->hasMany(RetailStore::class, 'sales_rep_id');
    }

    public function storeOrders()
    {
        return $this->hasMany(StoreOrder::class, 'sales_rep_id');
    }

    public function storeVisits()
    {
        return $this->hasMany(StoreVisit::class, 'sales_rep_id');
    }

    public function targets()
    {
        return $this->hasMany(SalesTarget::class, 'sales_rep_id');
    }

    public function activities()
    {
        return $this->hasMany(SalesActivity::class, 'sales_rep_id');
    }

    // Calculate monthly sales performance
    public function getMonthlySalesAttribute()
    {
        return $this->orders()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('delivery_status', 'delivered')
            ->sum('grand_total');
    }

    // Calculate quarterly sales performance
    public function getQuarterlySalesAttribute()
    {
        $quarter = ceil(now()->month / 3);
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        return $this->orders()
            ->whereMonth('created_at', '>=', $startMonth)
            ->whereMonth('created_at', '<=', $endMonth)
            ->whereYear('created_at', now()->year)
            ->where('delivery_status', 'delivered')
            ->sum('grand_total');
    }

    // Calculate yearly sales performance
    public function getYearlySalesAttribute()
    {
        return $this->orders()
            ->whereYear('created_at', now()->year)
            ->where('delivery_status', 'delivered')
            ->sum('grand_total');
    }

    // Calculate total commission earned
    public function getTotalCommissionAttribute()
    {
        return $this->commissions()->sum('commission_amount');
    }

    // Get performance percentage for monthly target
    public function getMonthlyPerformancePercentageAttribute()
    {
        if ($this->sales_target_monthly > 0) {
            return round(($this->monthly_sales / $this->sales_target_monthly) * 100, 2);
        }
        return 0;
    }

    // Get performance percentage for quarterly target
    public function getQuarterlyPerformancePercentageAttribute()
    {
        if ($this->sales_target_quarterly > 0) {
            return round(($this->quarterly_sales / $this->sales_target_quarterly) * 100, 2);
        }
        return 0;
    }

    // Get performance percentage for yearly target
    public function getYearlyPerformancePercentageAttribute()
    {
        if ($this->sales_target_yearly > 0) {
            return round(($this->yearly_sales / $this->sales_target_yearly) * 100, 2);
        }
        return 0;
    }

    // Check if rep is active
    public function isActive()
    {
        return $this->status == 1;
    }

    // Get full name from user
    public function getFullNameAttribute()
    {
        return $this->user ? $this->user->name : '';
    }

    // Get email from user
    public function getEmailAttribute()
    {
        return $this->user ? $this->user->email : '';
    }

    // Model Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeByTerritory($query, $territoryId)
    {
        return $query->where('territory_id', $territoryId);
    }

    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('current_latitude')->whereNotNull('current_longitude');
    }

    public function scopeOnline($query)
    {
        return $query->where('last_location_update', '>=', now()->subMinutes(30));
    }

    // GPS and Location Methods
    public function getDistanceFromStore($store)
    {
        if (!$this->current_latitude || !$this->current_longitude || !$store->latitude || !$store->longitude) {
            return null;
        }

        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->current_latitude);
        $lonFrom = deg2rad($this->current_longitude);
        $latTo = deg2rad($store->latitude);
        $lonTo = deg2rad($store->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    public function isNearStore($store, $radiusKm = 1)
    {
        $distance = $this->getDistanceFromStore($store);
        return $distance && $distance <= $radiusKm;
    }

    public function updateLocationFromRequest($latitude, $longitude, $deviceInfo = null)
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'last_location_update' => now(),
            'device_info' => $deviceInfo,
            'gps_enabled' => true,
            'last_active_date' => now()->toDateString()
        ]);
    }

    // Performance Analytics
    public function getPerformanceMetrics($period = 'monthly')
    {
        $startDate = $this->getStartDateForPeriod($period);
        $endDate = now();

        return [
            'visits' => $this->storeVisits()
                ->whereBetween('visit_date', [$startDate, $endDate])
                ->count(),
            'completed_visits' => $this->storeVisits()
                ->where('visit_status', 'completed')
                ->whereBetween('visit_date', [$startDate, $endDate])
                ->count(),
            'orders' => $this->storeOrders()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'sales_amount' => $this->storeOrders()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('grand_total'),
            'commission_earned' => $this->commissions()
                ->where('status', 'approved')
                ->whereBetween('commission_date', [$startDate, $endDate])
                ->sum('commission_amount'),
            'new_customers' => $this->customers()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count()
        ];
    }

    public function getTodaySchedule()
    {
        return $this->storeVisits()
            ->with(['retailStore'])
            ->whereDate('visit_date', today())
            ->orderBy('scheduled_time')
            ->get();
    }

    public function getUpcomingVisits($days = 7)
    {
        return $this->storeVisits()
            ->with(['retailStore'])
            ->where('visit_status', 'pending')
            ->whereBetween('visit_date', [now(), now()->addDays($days)])
            ->orderBy('visit_date')
            ->orderBy('scheduled_time')
            ->get();
    }

    // Commission Calculations
    public function calculateCommission($order)
    {
        $commissionAmount = 0;

        // Base commission on total order
        if ($this->commission_rate > 0) {
            $commissionAmount += ($order->grand_total * $this->commission_rate) / 100;
        }

        // Product-specific commissions could be added here
        
        return $commissionAmount;
    }

    public function getMonthlyCommissionSummary($month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        return [
            'total' => $this->commissions()
                ->whereMonth('commission_date', $month)
                ->whereYear('commission_date', $year)
                ->sum('commission_amount'),
            'approved' => $this->commissions()
                ->where('status', 'approved')
                ->whereMonth('commission_date', $month)
                ->whereYear('commission_date', $year)
                ->sum('commission_amount'),
            'pending' => $this->commissions()
                ->where('status', 'pending')
                ->whereMonth('commission_date', $month)
                ->whereYear('commission_date', $year)
                ->sum('commission_amount'),
            'paid' => $this->commissions()
                ->where('status', 'paid')
                ->whereMonth('commission_date', $month)
                ->whereYear('commission_date', $year)
                ->sum('commission_amount')
        ];
    }

    // Target Achievement
    public function getTargetAchievement($targetType = 'sales', $period = 'monthly')
    {
        $target = $this->targets()
            ->where('target_type', $targetType)
            ->where('period', $period)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        if (!$target) {
            return null;
        }

        $achieved = $this->getAchievedValue($targetType, $target->start_date, $target->end_date);
        $percentage = $target->target_value > 0 ? ($achieved / $target->target_value) * 100 : 0;

        return [
            'target' => $target->target_value,
            'achieved' => $achieved,
            'percentage' => round($percentage, 2),
            'remaining' => max(0, $target->target_value - $achieved),
            'status' => $percentage >= 100 ? 'completed' : ($percentage >= 80 ? 'on_track' : 'behind')
        ];
    }

    private function getAchievedValue($targetType, $startDate, $endDate)
    {
        switch ($targetType) {
            case 'sales':
                return $this->storeOrders()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('grand_total');
            case 'orders':
                return $this->storeOrders()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();
            case 'visits':
                return $this->storeVisits()
                    ->where('visit_status', 'completed')
                    ->whereBetween('visit_date', [$startDate, $endDate])
                    ->count();
            case 'customers':
                return $this->customers()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();
            default:
                return 0;
        }
    }

    private function getStartDateForPeriod($period)
    {
        switch ($period) {
            case 'daily':
                return now()->startOfDay();
            case 'weekly':
                return now()->startOfWeek();
            case 'monthly':
                return now()->startOfMonth();
            case 'quarterly':
                return now()->startOfQuarter();
            case 'yearly':
                return now()->startOfYear();
            default:
                return now()->startOfMonth();
        }
    }

    // Notification Methods
    public function sendPushNotification($title, $body, $data = [])
    {
        if (!$this->fcm_token) {
            return false;
        }

        // This would integrate with Firebase Cloud Messaging
        // Implementation depends on your push notification service
        
        return true;
    }

    public function canReceiveNotifications()
    {
        return !empty($this->fcm_token) && $this->isActive();
    }
}
