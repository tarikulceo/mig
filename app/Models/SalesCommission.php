<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class SalesCommission extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'sales_rep_id',
        'order_id',
        'order_detail_id',
        'product_id',
        'commission_amount',
        'commission_rate',
        'commission_percentage',
        'sale_amount',
        'base_amount',
        'commission_type',
        'payment_status',
        'status',
        'commission_date',
        'approved_at',
        'approved_by',
        'paid_at',
        'notes'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'commission_date' => 'datetime',
        'approved_at' => 'datetime',
        'commission_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'sale_amount' => 'decimal:2',
        'base_amount' => 'decimal:2'
    ];

    const COMMISSION_TYPES = [
        'order' => 'Order Commission',
        'product_based' => 'Product-Based Commission',
        'target' => 'Target Achievement',
        'bonus' => 'Bonus Commission'
    ];

    const PAYMENT_STATUS = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled'
    ];

    const STATUS = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'cancelled' => 'Cancelled',
        'paid' => 'Paid'
    ];

    public function salesRepresentative()
    {
        return $this->belongsTo(SalesRepresentative::class, 'sales_rep_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Check if commission is paid
    public function isPaid()
    {
        return $this->payment_status === 'paid' || $this->status === 'paid';
    }

    // Check if commission is pending
    public function isPending()
    {
        return $this->payment_status === 'pending' || $this->status === 'pending';
    }

    // Check if commission is approved
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    // Check if commission is cancelled
    public function isCancelled()
    {
        return $this->payment_status === 'cancelled' || $this->status === 'cancelled';
    }

    // Get commission type name
    public function getCommissionTypeNameAttribute()
    {
        return self::COMMISSION_TYPES[$this->commission_type] ?? $this->commission_type;
    }

    // Get payment status name
    public function getPaymentStatusNameAttribute()
    {
        return self::PAYMENT_STATUS[$this->payment_status] ?? $this->payment_status;
    }

    // Get status name
    public function getStatusNameAttribute()
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    // Get formatted commission amount
    public function getFormattedCommissionAmountAttribute()
    {
        return number_format($this->commission_amount, 2);
    }

    // Scope for product-based commissions
    public function scopeProductBased($query)
    {
        return $query->where('commission_type', 'product_based');
    }

    // Scope for approved commissions
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Scope for pending commissions
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
