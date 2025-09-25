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
        'notes'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'sales_target_monthly' => 'decimal:2',
        'sales_target_quarterly' => 'decimal:2',
        'sales_target_yearly' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'base_salary' => 'decimal:2',
        'status' => 'boolean'
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
}
