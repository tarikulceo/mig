<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class StoreOrderItem extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'store_order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total_price',
        'discount_amount',
        'product_variation',
        'item_notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'product_variation' => 'array'
    ];

    // Relationships
    public function storeOrder()
    {
        return $this->belongsTo(StoreOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getFinalPriceAttribute()
    {
        return $this->total_price - $this->discount_amount;
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->total_price > 0) {
            return ($this->discount_amount / $this->total_price) * 100;
        }
        return 0;
    }

    // Methods
    public function calculateTotal()
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->save();
    }
}