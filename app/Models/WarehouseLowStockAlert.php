<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\PreventDemoModeChanges;

class WarehouseLowStockAlert extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'product_stock_id',
        'current_quantity',
        'threshold_quantity',
        'status',
        'alerted_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes'
    ];

    protected $casts = [
        'alerted_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

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

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeIgnored($query)
    {
        return $query->where('status', 'ignored');
    }

    public function scopeWarehouseSpecific($query)
    {
        return $query->whereNotNull('warehouse_id');
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('warehouse_id');
    }

    public function isGlobalAlert()
    {
        return $this->warehouse_id === null;
    }

    public function getAlertTypeAttribute()
    {
        return $this->isGlobalAlert() ? 'Global Low Stock' : 'Warehouse Low Stock';
    }

    public function resolve($notes = null, $userId = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId ?: auth()->id(),
            'resolution_notes' => $notes
        ]);

        return $this;
    }

    public function ignore($notes = null, $userId = null)
    {
        $this->update([
            'status' => 'ignored',
            'resolved_at' => now(),
            'resolved_by' => $userId ?: auth()->id(),
            'resolution_notes' => $notes
        ]);

        return $this;
    }

    public static function checkAndCreateAlert($warehouseStock)
    {
        // Check per-warehouse low stock ONLY if warehouse has a threshold set
        if ($warehouseStock->low_stock_threshold > 0 && $warehouseStock->is_low_stock) {
            // Check if there's already an active alert for this specific warehouse stock
            $existingAlert = static::where('warehouse_id', $warehouseStock->warehouse_id)
                ->where('product_id', $warehouseStock->product_id)
                ->where('product_stock_id', $warehouseStock->product_stock_id)
                ->where('status', 'active')
                ->first();

            if (!$existingAlert) {
                static::create([
                    'warehouse_id' => $warehouseStock->warehouse_id,
                    'product_id' => $warehouseStock->product_id,
                    'product_stock_id' => $warehouseStock->product_stock_id,
                    'current_quantity' => $warehouseStock->quantity,
                    'threshold_quantity' => $warehouseStock->low_stock_threshold,
                    'status' => 'active',
                    'alerted_at' => now()
                ]);
            }
        } else {
            // Auto-resolve warehouse alert if exists and threshold not set or stock is above threshold
            $existingAlert = static::where('warehouse_id', $warehouseStock->warehouse_id)
                ->where('product_id', $warehouseStock->product_id)
                ->where('product_stock_id', $warehouseStock->product_stock_id)
                ->where('status', 'active')
                ->first();
                
            if ($existingAlert) {
                $existingAlert->resolve('Auto-resolved: Stock above threshold or threshold removed');
            }
        }

        // Also check global low stock for the product across all warehouses
        static::checkGlobalLowStock($warehouseStock->product, $warehouseStock->product_stock_id);

        return null;
    }

    public static function checkGlobalLowStock($product, $productStockId = null)
    {
        // Calculate total stock across all warehouses for this product/variant
        $query = $product->warehouse_stocks();
        
        if ($productStockId) {
            $query->where('product_stock_id', $productStockId);
        } else {
            $query->whereNull('product_stock_id');
        }
        
        $totalStock = $query->sum('quantity');
        $globalThreshold = $product->getGlobalLowStockThreshold();
        
        $existingGlobalAlert = static::where('warehouse_id', null)
            ->where('product_id', $product->id)
            ->where('product_stock_id', $productStockId)
            ->where('status', 'active')
            ->first();
        
        if ($totalStock <= $globalThreshold) {
            // Create global alert if not exists
            if (!$existingGlobalAlert) {
                return static::create([
                    'warehouse_id' => null, // null indicates global alert
                    'product_id' => $product->id,
                    'product_stock_id' => $productStockId,
                    'current_quantity' => $totalStock,
                    'threshold_quantity' => $globalThreshold,
                    'status' => 'active',
                    'alerted_at' => now()
                ]);
            }
        } else {
            // Auto-resolve global alert if exists and total stock is above threshold
            if ($existingGlobalAlert) {
                $existingGlobalAlert->resolve('Auto-resolved: Global stock above threshold');
            }
        }

        return null;
    }
}
