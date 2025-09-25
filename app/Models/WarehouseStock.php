<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\PreventDemoModeChanges;

class WarehouseStock extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'product_stock_id',
        'quantity',
        'reserved_quantity',
        'low_stock_threshold',
        'last_restocked_at'
    ];

    protected $casts = [
        'last_restocked_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        // Check alerts whenever warehouse stock is updated
        static::updated(function ($warehouseStock) {
            if ($warehouseStock->wasChanged(['quantity', 'low_stock_threshold'])) {
                \App\Models\WarehouseLowStockAlert::checkAndCreateAlert($warehouseStock);
            }
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productStock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function getAvailableQuantityAttribute()
    {
        return $this->quantity - $this->reserved_quantity;
    }

    public function getIsLowStockAttribute()
    {
        if ($this->low_stock_threshold <= 0) {
            return false;
        }
        return $this->quantity <= $this->low_stock_threshold;
    }

    public function getFormattedProductNameAttribute()
    {
        $name = $this->product->getTranslation('name');
        
        if ($this->productStock && !empty($this->productStock->variant)) {
            return $name . ' (' . $this->productStock->variant . ')';
        }
        
        return $name;
    }

    public function getStockStatusAttribute()
    {
        if ($this->quantity <= 0) {
            return [
                'status' => 'out_of_stock',
                'text' => translate('Out of Stock'),
                'badge_class' => 'badge-secondary'
            ];
        } elseif ($this->is_low_stock) {
            return [
                'status' => 'low_stock',
                'text' => translate('Low Stock'),
                'badge_class' => 'badge-danger'
            ];
        } else {
            return [
                'status' => 'in_stock',
                'text' => translate('In Stock'),
                'badge_class' => 'badge-success'
            ];
        }
    }

    public function getStockAttribute()
    {
        return $this->quantity;
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'low_stock_threshold');
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    public function incrementStock($quantity, $reason = null)
    {
        $this->increment('quantity', $quantity);
        $this->update(['last_restocked_at' => now()]);
        
        // Log stock movement
        $this->logStockMovement('increment', $quantity, $reason);
        
        // Check and resolve alerts
        \App\Models\WarehouseLowStockAlert::checkAndCreateAlert($this);
        
        return $this;
    }

    public function decrementStock($quantity, $reason = null)
    {
        if ($this->available_quantity >= $quantity) {
            $this->decrement('quantity', $quantity);
            
            // Log stock movement
            $this->logStockMovement('decrement', $quantity, $reason);
            
            // Check and resolve alerts
            \App\Models\WarehouseLowStockAlert::checkAndCreateAlert($this);
            
            return true;
        }
        
        return false;
    }

    public function reserveStock($quantity)
    {
        if ($this->available_quantity >= $quantity) {
            $this->increment('reserved_quantity', $quantity);
            return true;
        }
        
        return false;
    }

    public function releaseStock($quantity)
    {
        $this->decrement('reserved_quantity', min($quantity, $this->reserved_quantity));
        return $this;
    }

    private function logStockMovement($type, $quantity, $reason = null)
    {
        // This could be expanded to create a stock movement log
        // For now, we'll just update the last_restocked_at if it's an increment
        if ($type === 'increment') {
            $this->update(['last_restocked_at' => now()]);
        }
    }
}
