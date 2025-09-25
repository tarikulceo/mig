<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class SalesTarget extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'sales_rep_id',
        'target_period',
        'target_type',
        'target_value',
        'achieved_value',
        'start_date',
        'end_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    const TARGET_PERIODS = [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
        'custom' => 'Custom Period'
    ];

    const TARGET_TYPES = [
        'revenue' => 'Revenue Target',
        'orders' => 'Orders Count',
        'customers' => 'New Customers',
        'products' => 'Products Sold'
    ];

    const TARGET_STATUS = [
        'active' => 'Active',
        'achieved' => 'Achieved',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled'
    ];

    public function salesRepresentative()
    {
        return $this->belongsTo(SalesRepresentative::class, 'sales_rep_id');
    }

    // Calculate achievement percentage
    public function getAchievementPercentageAttribute()
    {
        if ($this->target_value > 0) {
            return round(($this->achieved_value / $this->target_value) * 100, 2);
        }
        return 0;
    }

    // Check if target is achieved
    public function isAchieved()
    {
        return $this->achieved_value >= $this->target_value;
    }

    // Check if target is active
    public function isActive()
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    // Get target period name
    public function getTargetPeriodNameAttribute()
    {
        return self::TARGET_PERIODS[$this->target_period] ?? $this->target_period;
    }

    // Get target type name
    public function getTargetTypeNameAttribute()
    {
        return self::TARGET_TYPES[$this->target_type] ?? $this->target_type;
    }

    // Get status name
    public function getStatusNameAttribute()
    {
        return self::TARGET_STATUS[$this->status] ?? $this->status;
    }

    // Calculate remaining days
    public function getRemainingDaysAttribute()
    {
        if ($this->end_date < now()) {
            return 0;
        }
        return now()->diffInDays($this->end_date);
    }
}
