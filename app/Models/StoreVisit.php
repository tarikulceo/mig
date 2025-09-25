<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class StoreVisit extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'retail_store_id',
        'sales_rep_id',
        'visit_date',
        'check_in_time',
        'check_out_time',
        'purpose',
        'notes',
        'photos',
        'order_amount',
        'visit_status',
        'products_discussed',
        'feedback',
        'next_action',
        'next_visit_date'
    ];

    protected $casts = [
        'visit_date' => 'datetime',
        'next_visit_date' => 'datetime',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'photos' => 'array',
        'products_discussed' => 'array',
        'order_amount' => 'decimal:2'
    ];

    // Relationships
    public function retailStore()
    {
        return $this->belongsTo(RetailStore::class);
    }

    public function salesRepresentative()
    {
        return $this->belongsTo(SalesRepresentative::class, 'sales_rep_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('visit_status', 'completed');
    }

    public function scopeScheduled($query)
    {
        return $query->where('visit_status', 'scheduled');
    }

    public function scopeForSalesRep($query, $salesRepId)
    {
        return $query->where('sales_rep_id', $salesRepId);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('visit_date', now()->month)
                    ->whereYear('visit_date', now()->year);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'scheduled' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger'
        ];
        return $badges[$this->visit_status] ?? 'secondary';
    }

    public function getDurationAttribute()
    {
        if ($this->check_in_time && $this->check_out_time) {
            $checkIn = \Carbon\Carbon::parse($this->check_in_time);
            $checkOut = \Carbon\Carbon::parse($this->check_out_time);
            return $checkIn->diffInMinutes($checkOut);
        }
        return null;
    }
}
