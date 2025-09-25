<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class SalesTerritory extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'name',
        'description',
        'region',
        'country_id',
        'state_id',
        'cities',
        'zip_codes',
        'is_active'
    ];

    protected $casts = [
        'cities' => 'array',
        'zip_codes' => 'array',
        'is_active' => 'boolean'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function salesRepresentatives()
    {
        return $this->hasMany(SalesRepresentative::class, 'territory_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'territory_id');
    }

    // Get active sales reps in this territory
    public function getActiveSalesRepsAttribute()
    {
        return $this->salesRepresentatives()->where('status', 1)->get();
    }

    // Get total sales for this territory
    public function getTotalSalesAttribute()
    {
        return $this->salesRepresentatives()
            ->join('orders', 'sales_representatives.id', '=', 'orders.sales_rep_id')
            ->where('orders.delivery_status', 'delivered')
            ->sum('orders.grand_total');
    }
}
