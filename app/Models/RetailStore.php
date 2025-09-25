<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class RetailStore extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'name',
        'store_code',
        'description',
        'owner_name',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'store_type',
        'store_size',
        'staff_count',
        'image',
        'images',
        'sales_rep_id',
        'territory_id',
        'monthly_target',
        'yearly_target',
        'status',
        'business_hours',
        'notes',
        'established_date',
        'last_visit',
        'created_by'
    ];

    protected $casts = [
        'images' => 'array',
        'business_hours' => 'array',
        'monthly_target' => 'decimal:2',
        'yearly_target' => 'decimal:2',
        'store_size' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'established_date' => 'datetime',
        'last_visit' => 'datetime'
    ];

    // Relationships
    public function salesRepresentative()
    {
        return $this->belongsTo(SalesRepresentative::class, 'sales_rep_id');
    }

    public function territory()
    {
        return $this->belongsTo(SalesTerritory::class, 'territory_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function visits()
    {
        return $this->hasMany(StoreVisit::class);
    }

    // Remove orders relationship - retail stores don't have direct orders in this system
    // Orders are made by customers through the e-commerce platform

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForSalesRep($query, $salesRepId)
    {
        return $query->where('sales_rep_id', $salesRepId);
    }

    // Accessors
    public function getFullAddressAttribute()
    {
        $addressParts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);
        return implode(', ', $addressParts);
    }

    public function getMapUrlAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }
        return null;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'active' => 'success',
            'inactive' => 'secondary',
            'pending' => 'warning'
        ];
        return $badges[$this->status] ?? 'secondary';
    }

    // Store code generator
    public static function generateStoreCode($salesRepId = null)
    {
        $prefix = 'STR';
        if ($salesRepId) {
            $salesRep = SalesRepresentative::find($salesRepId);
            if ($salesRep && $salesRep->employee_id) {
                $prefix = strtoupper(substr($salesRep->employee_id, 0, 3));
            }
        }
        
        $lastStore = self::where('store_code', 'like', $prefix . '%')
                         ->orderBy('store_code', 'desc')
                         ->first();
        
        if ($lastStore) {
            $lastNumber = intval(substr($lastStore->store_code, strlen($prefix)));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
