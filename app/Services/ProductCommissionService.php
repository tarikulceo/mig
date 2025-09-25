<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\SalesRepresentative;
use App\Models\SalesCommission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductCommissionService
{
    /**
     * Calculate and create commissions for products in an order
     *
     * @param Order $order
     * @return array
     */
    public function calculateOrderCommissions(Order $order)
    {
        $commissions = [];
        
        try {
            DB::beginTransaction();
            
            // Get the sales representative assigned to this order
            $salesRep = $order->salesRepresentative ?? null;
            
            if (!$salesRep) {
                Log::info("Order {$order->id} has no sales representative assigned");
                DB::rollback();
                return $commissions;
            }
            
            // Process each product in the order
            foreach ($order->orderDetails as $orderDetail) {
                $product = $orderDetail->product;
                
                if (!$product || !$product->isEligibleForSalesCommission()) {
                    continue;
                }
                
                $commissionAmount = $this->calculateProductCommission($orderDetail, $product);
                
                if ($commissionAmount > 0) {
                    $commission = $this->createCommissionRecord(
                        $salesRep,
                        $order,
                        $product,
                        $orderDetail,
                        $commissionAmount
                    );
                    
                    $commissions[] = $commission;
                }
            }
            
            DB::commit();
            Log::info("Created " . count($commissions) . " commission records for order {$order->id}");
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to calculate commissions for order {$order->id}: " . $e->getMessage());
        }
        
        return $commissions;
    }
    
    /**
     * Calculate commission amount for a specific product in an order detail
     *
     * @param OrderDetail $orderDetail
     * @param Product $product
     * @return float
     */
    public function calculateProductCommission(OrderDetail $orderDetail, Product $product)
    {
        // Calculate commission based on the actual selling price (after discounts, taxes, etc.)
        $baseAmount = $orderDetail->price * $orderDetail->quantity;
        
        // Apply any additional calculations if needed (like excluding taxes from commission base)
        $commissionableAmount = $baseAmount;
        
        // Calculate commission using product's commission percentage
        $commissionAmount = $product->calculateSalesCommission($commissionableAmount);
        
        return round($commissionAmount, 2);
    }
    
    /**
     * Create a commission record in the database
     *
     * @param SalesRepresentative $salesRep
     * @param Order $order
     * @param Product $product
     * @param OrderDetail $orderDetail
     * @param float $commissionAmount
     * @return SalesCommission
     */
    private function createCommissionRecord(
        SalesRepresentative $salesRep,
        Order $order,
        Product $product,
        OrderDetail $orderDetail,
        float $commissionAmount
    ) {
        return SalesCommission::create([
            'sales_rep_id' => $salesRep->id,
            'order_id' => $order->id,
            'commission_type' => 'product_based',
            'commission_amount' => $commissionAmount,
            'commission_percentage' => $product->sales_commission_percentage,
            'base_amount' => $orderDetail->price * $orderDetail->quantity,
            'status' => 'pending',
            'commission_date' => now(),
            'notes' => "Product-based commission for {$product->getTranslation('name')} (Product ID: {$product->id})",
            'product_id' => $product->id,
            'order_detail_id' => $orderDetail->id,
        ]);
    }
    
    /**
     * Recalculate commissions when order status changes
     *
     * @param Order $order
     * @param string $newStatus
     * @return void
     */
    public function handleOrderStatusChange(Order $order, string $newStatus)
    {
        try {
            $commissions = SalesCommission::where('order_id', $order->id)
                ->where('commission_type', 'product_based')
                ->get();
            
            foreach ($commissions as $commission) {
                switch ($newStatus) {
                    case 'delivered':
                        // Approve commission when order is delivered
                        $commission->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                            'approved_by' => auth()->id()
                        ]);
                        break;
                        
                    case 'cancelled':
                    case 'returned':
                        // Cancel commission for cancelled/returned orders
                        $commission->update([
                            'status' => 'cancelled',
                            'notes' => $commission->notes . " - Order {$newStatus} on " . now()->format('Y-m-d H:i:s')
                        ]);
                        break;
                }
            }
            
            Log::info("Updated commission status for order {$order->id} to match order status: {$newStatus}");
            
        } catch (\Exception $e) {
            Log::error("Failed to update commission status for order {$order->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Get commission summary for a sales representative
     *
     * @param SalesRepresentative $salesRep
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getCommissionSummary(SalesRepresentative $salesRep, string $startDate = null, string $endDate = null)
    {
        $query = SalesCommission::where('sales_rep_id', $salesRep->id)
            ->where('commission_type', 'product_based');
            
        if ($startDate) {
            $query->whereDate('commission_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('commission_date', '<=', $endDate);
        }
        
        $commissions = $query->get();
        
        return [
            'total_commissions' => $commissions->count(),
            'total_amount' => $commissions->sum('commission_amount'),
            'pending_amount' => $commissions->where('status', 'pending')->sum('commission_amount'),
            'approved_amount' => $commissions->where('status', 'approved')->sum('commission_amount'),
            'cancelled_amount' => $commissions->where('status', 'cancelled')->sum('commission_amount'),
            'paid_amount' => $commissions->where('status', 'paid')->sum('commission_amount'),
            'by_product' => $commissions->groupBy('product_id')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('commission_amount'),
                    'product_name' => $group->first()->product->getTranslation('name') ?? 'Unknown Product'
                ];
            })
        ];
    }
    
    /**
     * Get top performing products by commission
     *
     * @param int $limit
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Support\Collection
     */
    public function getTopPerformingProducts(int $limit = 10, string $startDate = null, string $endDate = null)
    {
        $query = SalesCommission::select('product_id', 
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(commission_amount) as total_commission'),
                DB::raw('SUM(base_amount) as total_sales')
            )
            ->where('commission_type', 'product_based')
            ->where('status', '!=', 'cancelled');
            
        if ($startDate) {
            $query->whereDate('commission_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('commission_date', '<=', $endDate);
        }
        
        return $query->groupBy('product_id')
            ->orderBy('total_commission', 'desc')
            ->limit($limit)
            ->with(['product'])
            ->get();
    }
}
