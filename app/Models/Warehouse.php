<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\PreventDemoModeChanges;
use App\Models\User;

class Warehouse extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'name',
        'address',
        'manager_name',
        'contact_info',
        'email',
        'phone',
        'city',
        'state',
        'country',
        'postal_code',
        'is_active',
        'latitude',
        'longitude',
        'created_by',
        'managed_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'managed_by' => 'array'
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(WarehouseStockTransfer::class, 'from_warehouse_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(WarehouseStockTransfer::class, 'to_warehouse_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'assigned_warehouse_id');
    }

    public function lowStockAlerts(): HasMany
    {
        return $this->hasMany(WarehouseLowStockAlert::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTotalProductsAttribute()
    {
        return $this->stocks()->distinct('product_id')->count();
    }

    public function getTotalStockAttribute()
    {
        return $this->stocks()->sum('quantity');
    }

    public function getLowStockProductsCountAttribute()
    {
        return $this->stocks()->whereColumn('quantity', '<=', 'low_stock_threshold')->count();
    }

    public function getAvailableStockForProduct($productId, $productStockId = null)
    {
        $query = $this->stocks()->where('product_id', $productId);
        
        if ($productStockId) {
            $query->where('product_stock_id', $productStockId);
        }
        
        $stock = $query->first();
        
        return $stock ? ($stock->quantity - $stock->reserved_quantity) : 0;
    }

    public function hasStock($productId, $quantity, $productStockId = null)
    {
        return $this->getAvailableStockForProduct($productId, $productStockId) >= $quantity;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canBeAccessedBy($userId)
    {
        // Check if user is admin
        if (User::find($userId)->user_type == 'admin') {
            return true;
        }
        
        // Check if user created this warehouse
        if ($this->created_by == $userId) {
            return true;
        }
        
        // Check if user is in managed_by list
        if (is_array($this->managed_by) && in_array($userId, $this->managed_by)) {
            return true;
        }
        
        return false;
    }

    public function scopeAccessibleBy($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $user = User::find($userId);
            if ($user && $user->user_type == 'admin') {
                // Admin can access all warehouses
                return $q;
            } else {
                // Sellers can only access warehouses they created or manage
                return $q->where('created_by', $userId)
                        ->orWhereJsonContains('managed_by', $userId);
            }
        });
    }
}
