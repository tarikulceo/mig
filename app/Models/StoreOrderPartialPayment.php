<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class StoreOrderPartialPayment extends Model
{
    use HasFactory, PreventDemoModeChanges;

    protected $fillable = [
        'store_order_id',
        'retail_store_id',
        'payment_amount',
        'payment_method',
        'payment_reference',
        'payment_notes',
        'payment_date',
        'recorded_by',
        'status'
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'status' => 'string'
    ];

    // Relationships
    public function storeOrder()
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id');
    }

    public function retailStore()
    {
        return $this->belongsTo(RetailStore::class, 'retail_store_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('retail_store_id', $storeId);
    }

    public function scopeForOrder($query, $orderId)
    {
        return $query->where('store_order_id', $orderId);
    }
}
