<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\PreventDemoModeChanges;

class WarehouseStockTransfer extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'product_id',
        'product_stock_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'quantity',
        'reason',
        'status',
        'transfer_date',
        'initiated_by',
        'approved_by',
        'approved_at',
        'notes'
    ];

    protected $casts = [
        'transfer_date' => 'datetime',
        'approved_at' => 'datetime'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productStock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function approve($userId = null)
    {
        $this->update([
            'status' => 'in_transit',
            'approved_by' => $userId ?: auth()->id(),
            'approved_at' => now()
        ]);

        return $this;
    }

    public function complete()
    {
        if ($this->status !== 'in_transit') {
            return false;
        }

        // Get source and destination stocks
        $fromStock = WarehouseStock::where('warehouse_id', $this->from_warehouse_id)
            ->where('product_id', $this->product_id)
            ->where('product_stock_id', $this->product_stock_id)
            ->first();

        $toStock = WarehouseStock::firstOrCreate([
            'warehouse_id' => $this->to_warehouse_id,
            'product_id' => $this->product_id,
            'product_stock_id' => $this->product_stock_id
        ], [
            'quantity' => 0,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 10
        ]);

        if ($fromStock && $fromStock->decrementStock($this->quantity, 'Transfer to ' . $this->toWarehouse->name)) {
            $toStock->incrementStock($this->quantity, 'Transfer from ' . $this->fromWarehouse->name);
            
            $this->update(['status' => 'completed']);
            return true;
        }

        return false;
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason
        ]);

        return $this;
    }
}
