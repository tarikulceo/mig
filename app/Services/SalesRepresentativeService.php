<?php

namespace App\Services;

use App\Models\SalesRepresentative;
use App\Models\SalesCommission;
use App\Models\SalesTarget;
use App\Models\Order;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesRepresentativeService
{
    /**
     * Calculate commission for an order
     */
    public function calculateOrderCommission(Order $order)
    {
        if (!$order->sales_rep_id) {
            return false;
        }

        $salesRep = $order->salesRepresentative;
        if (!$salesRep || !$salesRep->isActive()) {
            return false;
        }

        // Check if commission already exists
        $existingCommission = SalesCommission::where('order_id', $order->id)
            ->where('sales_rep_id', $salesRep->id)
            ->first();

        if ($existingCommission) {
            return $existingCommission;
        }

        // Calculate commission
        $saleAmount = $order->grand_total;
        $commissionAmount = ($saleAmount * $salesRep->commission_rate) / 100;

        // Create commission record
        $commission = new SalesCommission();
        $commission->sales_rep_id = $salesRep->id;
        $commission->order_id = $order->id;
        $commission->commission_amount = $commissionAmount;
        $commission->commission_rate = $salesRep->commission_rate;
        $commission->sale_amount = $saleAmount;
        $commission->commission_type = 'order';
        $commission->payment_status = 'pending';
        $commission->save();

        return $commission;
    }

    /**
     * Auto-assign customer to sales rep based on territory
     */
    public function autoAssignCustomer(Customer $customer)
    {
        // Get customer's location info (you might need to implement this based on your location logic)
        $customerLocation = $this->getCustomerLocation($customer);
        
        if (!$customerLocation) {
            return false;
        }

        // Find sales rep in the same territory
        $salesRep = SalesRepresentative::whereHas('territory', function($query) use ($customerLocation) {
            $query->where('country_id', $customerLocation['country_id']);
            if ($customerLocation['state_id']) {
                $query->where('state_id', $customerLocation['state_id']);
            }
        })
        ->where('status', 1)
        ->orderBy('created_at')
        ->first();

        if ($salesRep) {
            $customer->sales_rep_id = $salesRep->id;
            $customer->territory_id = $salesRep->territory_id;
            $customer->save();
            return $salesRep;
        }

        return false;
    }

    /**
     * Get customer location info
     */
    private function getCustomerLocation(Customer $customer)
    {
        // You can implement this based on your customer address logic
        // For now, returning dummy data
        return [
            'country_id' => 1,
            'state_id' => null,
            'city' => null,
            'zip_code' => null
        ];
    }

    /**
     * Update sales targets achievement
     */
    public function updateTargetsAchievement(SalesRepresentative $salesRep)
    {
        $activeTargets = $salesRep->targets()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get();

        foreach ($activeTargets as $target) {
            $achieved = $this->calculateTargetAchievement($target);
            $target->achieved_value = $achieved;
            
            // Update status based on achievement
            if ($achieved >= $target->target_value) {
                $target->status = 'achieved';
            } elseif ($target->end_date < now()) {
                $target->status = 'failed';
            }
            
            $target->save();
        }
    }

    /**
     * Calculate target achievement
     */
    private function calculateTargetAchievement(SalesTarget $target)
    {
        $salesRep = $target->salesRepresentative;
        
        switch ($target->target_type) {
            case 'revenue':
                return $salesRep->orders()
                    ->whereBetween('created_at', [$target->start_date, $target->end_date])
                    ->where('delivery_status', 'delivered')
                    ->sum('grand_total');
                    
            case 'orders':
                return $salesRep->orders()
                    ->whereBetween('created_at', [$target->start_date, $target->end_date])
                    ->where('delivery_status', 'delivered')
                    ->count();
                    
            case 'customers':
                return $salesRep->customers()
                    ->whereBetween('created_at', [$target->start_date, $target->end_date])
                    ->count();
                    
            case 'products':
                return $salesRep->orders()
                    ->whereBetween('created_at', [$target->start_date, $target->end_date])
                    ->where('delivery_status', 'delivered')
                    ->join('order_details', 'orders.id', '=', 'order_details.order_id')
                    ->sum('order_details.quantity');
                    
            default:
                return 0;
        }
    }

    /**
     * Get sales rep performance data
     */
    public function getPerformanceData(SalesRepresentative $salesRep, $period = 'monthly')
    {
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $orders = $salesRep->orders()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('delivery_status', 'delivered');

        $totalSales = $orders->sum('grand_total');
        $totalOrders = $orders->count();
        $totalCommissions = $salesRep->commissions()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('commission_amount');

        $newCustomers = $salesRep->customers()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'total_commissions' => $totalCommissions,
            'new_customers' => $newCustomers,
            'average_order_value' => $totalOrders > 0 ? $totalSales / $totalOrders : 0
        ];
    }

    /**
     * Get start date for period
     */
    private function getStartDate($period)
    {
        switch ($period) {
            case 'daily':
                return now()->startOfDay();
            case 'weekly':
                return now()->startOfWeek();
            case 'monthly':
                return now()->startOfMonth();
            case 'quarterly':
                return now()->startOfQuarter();
            case 'yearly':
                return now()->startOfYear();
            default:
                return now()->startOfMonth();
        }
    }

    /**
     * Get top performing sales reps
     */
    public function getTopPerformers($limit = 10, $period = 'monthly')
    {
        $startDate = $this->getStartDate($period);
        $endDate = now();

        return SalesRepresentative::with('user')
            ->select('sales_representatives.*')
            ->leftJoin('orders', 'sales_representatives.id', '=', 'orders.sales_rep_id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.delivery_status', 'delivered')
            ->groupBy('sales_representatives.id')
            ->orderByRaw('SUM(orders.grand_total) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate sales report for sales rep
     */
    public function generateSalesReport(SalesRepresentative $salesRep, $startDate, $endDate)
    {
        $orders = $salesRep->orders()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('delivery_status', 'delivered')
            ->with(['orderDetails.product']);

        $totalSales = $orders->sum('grand_total');
        $totalOrders = $orders->count();
        $totalCommissions = $salesRep->commissions()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('commission_amount');

        $productSales = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->where('orders.sales_rep_id', $salesRep->id)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.delivery_status', 'delivered')
            ->select('products.name', DB::raw('SUM(order_details.quantity) as quantity'), DB::raw('SUM(order_details.price) as revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('revenue', 'desc')
            ->get();

        return [
            'sales_rep' => $salesRep,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'summary' => [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'total_commissions' => $totalCommissions,
                'average_order_value' => $totalOrders > 0 ? $totalSales / $totalOrders : 0
            ],
            'product_sales' => $productSales,
            'orders' => $orders->get()
        ];
    }
}
