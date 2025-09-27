<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class StoreOrder extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'order_code',
        'retail_store_id',
        'sales_rep_id',
        'store_visit_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'grand_total',
        'paid_amount',
        'due_amount',
        'order_status',
        'payment_status',
        'payment_method',
        'order_notes',
        'delivery_address',
        'order_date',
        'delivery_date',
        'due_date',
        'credit_days',
        'is_credit_order',
        'expected_delivery',
        'expected_delivery_date',
        'invoice_number',
        'commission_amount',
        'commission_rate',
        'commission_paid',
        'order_items'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_paid' => 'boolean',
        'is_credit_order' => 'boolean',
        'delivery_address' => 'array',
        'order_items' => 'array',
        'order_date' => 'datetime',
        'delivery_date' => 'datetime',
        'due_date' => 'date',
        'expected_delivery' => 'datetime',
        'expected_delivery_date' => 'datetime'
    ];

    const ORDER_STATUS = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];

    const PAYMENT_STATUS = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'partial' => 'Partial',
        'failed' => 'Failed',
        'refunded' => 'Refunded'
    ];

    const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'card' => 'Credit/Debit Card',
        'bank_transfer' => 'Bank Transfer',
        'credit' => 'Store Credit',
        'cheque' => 'Cheque'
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

    public function storeVisit()
    {
        return $this->belongsTo(StoreVisit::class, 'store_visit_id');
    }

    public function orderItems()
    {
        return $this->hasMany(StoreOrderItem::class);
    }
    
    // Status history functionality can be added later if needed
    // public function statusHistory()
    // {
    //     return $this->hasMany(StoreOrderStatusHistory::class, 'store_order_id');
    // }
    
    // Sales commissions relationship
    // The commission data can now be stored in the sales_commissions table with store_order_id
    public function salesCommissions()
    {
        return $this->hasMany(SalesCommission::class, 'store_order_id');
    }

    public function partialPayments()
    {
        return $this->hasMany(StoreOrderPartialPayment::class, 'store_order_id');
    }

    public function completedPayments()
    {
        return $this->hasMany(StoreOrderPartialPayment::class, 'store_order_id')->where('status', 'completed');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('order_status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('order_status', 'confirmed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('order_status', 'delivered');
    }

    public function scopeForSalesRep($query, $salesRepId)
    {
        return $query->where('sales_rep_id', $salesRepId);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('retail_store_id', $storeId);
    }

    // Accessors
    public function getOrderStatusNameAttribute()
    {
        return self::ORDER_STATUS[$this->order_status] ?? $this->order_status;
    }

    public function getPaymentStatusNameAttribute()
    {
        return self::PAYMENT_STATUS[$this->payment_status] ?? $this->payment_status;
    }

    public function getPaymentMethodNameAttribute()
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'confirmed' => 'info',
            'processing' => 'primary',
            'shipped' => 'success',
            'delivered' => 'success',
            'cancelled' => 'danger'
        ];
        return $badges[$this->order_status] ?? 'secondary';
    }

    public function getPaymentBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'paid' => 'success',
            'partial' => 'info',
            'failed' => 'danger',
            'refunded' => 'secondary'
        ];
        return $badges[$this->payment_status] ?? 'secondary';
    }

    // Methods
    public function calculateCommission()
    {
        if ($this->salesRepresentative && $this->commission_rate > 0) {
            $this->commission_amount = ($this->grand_total * $this->commission_rate) / 100;
            $this->save();
            
            // Create commission record
            if (!$this->commission_paid) {
                $commission = new SalesCommission();
                $commission->sales_rep_id = $this->sales_rep_id;
                $commission->store_order_id = $this->id;
                $commission->commission_amount = $this->commission_amount;
                $commission->commission_rate = $this->commission_rate;
                $commission->sale_amount = $this->grand_total;
                $commission->commission_type = 'store_order';
                $commission->payment_status = 'pending';
                $commission->save();
            }
        }
    }

    public function updateStock()
    {
        foreach ($this->orderItems as $item) {
            $product = $item->product;
            if ($product) {
                // Reduce stock
                $productStock = $product->stocks()->where('variant', $item->product_variation)->first();
                if ($productStock && $productStock->qty >= $item->quantity) {
                    $productStock->qty -= $item->quantity;
                    $productStock->save();
                }
            }
        }
    }

    // Generate unique order code
    public static function generateOrderCode()
    {
        $prefix = 'STO'; // Store Order
        $date = date('Ymd');
        
        $lastOrder = self::where('order_code', 'like', $prefix . $date . '%')
                         ->orderBy('order_code', 'desc')
                         ->first();
        
        if ($lastOrder) {
            $lastNumber = intval(substr($lastOrder->order_code, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Payment calculation methods
    public function updatePaymentAmounts()
    {
        $totalPaid = $this->completedPayments()->sum('payment_amount');
        $this->paid_amount = $totalPaid;
        $this->due_amount = ($this->total_amount ?? $this->grand_total) - $totalPaid;
        
        // Update payment status based on amounts
        if ($totalPaid == 0) {
            $this->payment_status = 'pending';
        } elseif ($totalPaid >= ($this->total_amount ?? $this->grand_total)) {
            $this->payment_status = 'paid';
            $this->due_amount = 0;
        } else {
            $this->payment_status = 'partial';
        }
        
        $this->save();
    }

    public function getRemainingAmountAttribute()
    {
        return $this->grand_total - $this->paid_amount;
    }

    public function getTotalPaidAmountAttribute()
    {
        return $this->completedPayments()->sum('payment_amount');
    }

    public function getPaymentProgressPercentageAttribute()
    {
        return $this->grand_total > 0 ? ($this->paid_amount / $this->grand_total) * 100 : 0;
    }

    public function isFullyPaid()
    {
        return $this->paid_amount >= $this->grand_total;
    }

    public function isPartiallyPaid()
    {
        return $this->paid_amount > 0 && $this->paid_amount < $this->grand_total;
    }

    public function isPending()
    {
        return $this->paid_amount == 0;
    }

    public function isOverdue()
    {
        return $this->due_date && now() > $this->due_date && !$this->isFullyPaid();
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->due_date || $this->isFullyPaid()) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->due_date, false));
    }
}