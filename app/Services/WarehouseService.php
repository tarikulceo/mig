<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockTransfer;
use App\Models\WarehouseLowStockAlert;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    /**
     * Assign an order to the best available warehouse
     */
    public function assignOrderToWarehouse(Order $order, $warehouseId = null)
    {
        if ($warehouseId) {
            $warehouse = Warehouse::findOrFail($warehouseId);
        } else {
            $warehouse = $this->findBestWarehouseForOrder($order);
        }

        if ($warehouse && $this->canFulfillOrder($warehouse, $order)) {
            $order->update([
                'assigned_warehouse_id' => $warehouse->id,
                'warehouse_assigned_at' => now()
            ]);

            // Reserve stock for the order
            $this->reserveStockForOrder($order);

            return $warehouse;
        }

        return null;
    }

    /**
     * Find the best warehouse to fulfill an order
     */
    public function findBestWarehouseForOrder(Order $order)
    {
        $orderDetails = $order->orderDetails;
        $warehouses = Warehouse::active()->get();
        $bestWarehouse = null;
        $bestScore = 0;

        foreach ($warehouses as $warehouse) {
            if ($this->canFulfillOrder($warehouse, $order)) {
                $score = $this->calculateWarehouseScore($warehouse, $order);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestWarehouse = $warehouse;
                }
            }
        }

        return $bestWarehouse;
    }

    /**
     * Check if warehouse can fulfill an order
     */
    public function canFulfillOrder(Warehouse $warehouse, Order $order)
    {
        foreach ($order->orderDetails as $detail) {
            $availableStock = $warehouse->getAvailableStockForProduct(
                $detail->product_id,
                $detail->product_stock_id
            );

            if ($availableStock < $detail->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate warehouse score for order assignment
     */
    private function calculateWarehouseScore(Warehouse $warehouse, Order $order)
    {
        $score = 0;
        
        // Base score for having all items in stock
        $score += 100;
        
        // Bonus for warehouse location (if coordinates are available)
        if ($warehouse->latitude && $warehouse->longitude) {
            // This could be expanded to calculate distance to customer
            $score += 10;
        }
        
        // Bonus for lower stock levels (to balance inventory)
        $totalStock = $warehouse->total_stock;
        if ($totalStock < 1000) {
            $score += 20;
        }

        return $score;
    }

    /**
     * Reserve stock for an order
     */
    public function reserveStockForOrder(Order $order)
    {
        if (!$order->assigned_warehouse_id) {
            return false;
        }

        foreach ($order->orderDetails as $detail) {
            $warehouseStock = WarehouseStock::where('warehouse_id', $order->assigned_warehouse_id)
                ->where('product_id', $detail->product_id)
                ->where('product_stock_id', $detail->product_stock_id)
                ->first();

            if ($warehouseStock) {
                $warehouseStock->reserveStock($detail->quantity);
            }
        }

        return true;
    }

    /**
     * Fulfill an order (deduct actual stock)
     */
    public function fulfillOrder(Order $order)
    {
        if (!$order->assigned_warehouse_id) {
            return false;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->orderDetails as $detail) {
                $warehouseStock = WarehouseStock::where('warehouse_id', $order->assigned_warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->where('product_stock_id', $detail->product_stock_id)
                    ->first();

                if ($warehouseStock) {
                    // Release reserved stock and deduct actual stock
                    $warehouseStock->releaseStock($detail->quantity);
                    $warehouseStock->decrementStock($detail->quantity, 'Order fulfillment #' . $order->code);
                    
                    // Check for low stock alerts
                    WarehouseLowStockAlert::checkAndCreateAlert($warehouseStock);
                }
            }
        });

        return true;
    }

    /**
     * Cancel order stock reservation
     */
    public function cancelOrderReservation(Order $order)
    {
        if (!$order->assigned_warehouse_id) {
            return false;
        }

        foreach ($order->orderDetails as $detail) {
            $warehouseStock = WarehouseStock::where('warehouse_id', $order->assigned_warehouse_id)
                ->where('product_id', $detail->product_id)
                ->where('product_stock_id', $detail->product_stock_id)
                ->first();

            if ($warehouseStock) {
                $warehouseStock->releaseStock($detail->quantity);
            }
        }

        return true;
    }

    /**
     * Get warehouse stock report
     */
    public function getWarehouseStockReport($warehouseId, $startDate = null, $endDate = null)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        
        $outOfStockProductsCount = $warehouse->stocks()->where('quantity', 0)->count();
        $stockDetails = $warehouse->stocks()->with(['product', 'productStock'])->get();
        $recentTransfers = \App\Models\WarehouseStockTransfer::where(function($query) use ($warehouseId) {
            $query->where('from_warehouse_id', $warehouseId)
                  ->orWhere('to_warehouse_id', $warehouseId);
        })
        ->with(['product', 'productStock', 'fromWarehouse', 'toWarehouse', 'initiatedBy'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
        $report = [
            'warehouse' => $warehouse,
            'total_products' => $warehouse->total_products,
            'total_stock' => $warehouse->total_stock,
            'low_stock_products' => $warehouse->low_stock_products_count,
            'out_of_stock_products' => $outOfStockProductsCount,
            'active_alerts' => $warehouse->lowStockAlerts()->active()->count(),
            'stock_details' => $stockDetails,
            'recent_transfers' => $recentTransfers,
        ];

        // Sales data
        $salesQuery = OrderDetail::join('orders', 'order_details.order_id', '=', 'orders.id')
            ->where('orders.assigned_warehouse_id', $warehouseId)
            ->where('orders.delivery_status', 'delivered');

        if ($startDate) {
            $salesQuery->where('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->where('orders.created_at', '<=', $endDate);
        }

        $report['total_sales'] = $salesQuery->sum('order_details.price');
        $report['total_orders'] = $salesQuery->distinct('orders.id')->count();

        // Stock movements
        $transfersOut = WarehouseStockTransfer::where('from_warehouse_id', $warehouseId)
            ->where('status', 'completed');
        $transfersIn = WarehouseStockTransfer::where('to_warehouse_id', $warehouseId)
            ->where('status', 'completed');

        if ($startDate) {
            $transfersOut->where('created_at', '>=', $startDate);
            $transfersIn->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $transfersOut->where('created_at', '<=', $endDate);
            $transfersIn->where('created_at', '<=', $endDate);
        }

        $report['outgoing_transfers'] = $transfersOut->sum('quantity');
        $report['incoming_transfers'] = $transfersIn->sum('quantity');

        return $report;
    }

    /**
     * Get low stock products for a warehouse
     */
    public function getLowStockProducts($warehouseId)
    {
        return WarehouseStock::with(['product', 'productStock', 'warehouse'])
            ->where('warehouse_id', $warehouseId)
            ->lowStock()
            ->get();
    }

    /**
     * Get all low stock products (warehouse-specific and global)
     */
    public function getAllLowStockProducts()
    {
        $lowStockProducts = collect();

        // Get warehouse-specific low stock
        $warehouseLowStock = WarehouseStock::with(['product', 'productStock', 'warehouse'])
            ->lowStock()
            ->get();

        $lowStockProducts = $lowStockProducts->merge($warehouseLowStock);

        // Get global low stock products
        $products = Product::with(['warehouse_stocks', 'stocks'])->get();
        
        foreach ($products as $product) {
            if ($product->isGlobalLowStock()) {
                // Check if this product has variants
                if ($product->stocks->count() > 0) {
                    foreach ($product->stocks as $productStock) {
                        $variantTotalStock = $product->warehouse_stocks()
                            ->where('product_stock_id', $productStock->id)
                            ->sum('quantity');
                        
                        if ($variantTotalStock <= $product->getGlobalLowStockThreshold()) {
                            $lowStockProducts->push((object)[
                                'product' => $product,
                                'productStock' => $productStock,
                                'warehouse' => null,
                                'quantity' => $variantTotalStock,
                                'low_stock_threshold' => $product->getGlobalLowStockThreshold(),
                                'is_global' => true,
                                'formatted_product_name' => $product->getFormattedNameWithVariant($productStock->id)
                            ]);
                        }
                    }
                } else {
                    // Simple product
                    $totalStock = $product->getTotalStock();
                    if ($totalStock <= $product->getGlobalLowStockThreshold()) {
                        $lowStockProducts->push((object)[
                            'product' => $product,
                            'productStock' => null,
                            'warehouse' => null,
                            'quantity' => $totalStock,
                            'low_stock_threshold' => $product->getGlobalLowStockThreshold(),
                            'is_global' => true,
                            'formatted_product_name' => $product->getFormattedNameWithVariant()
                        ]);
                    }
                }
            }
        }

        return $lowStockProducts->unique(function ($item) {
            return $item->product->id . '-' . ($item->productStock ? $item->productStock->id : 'null') . '-' . ($item->warehouse ? $item->warehouse->id : 'global');
        });
    }

    /**
     * Transfer stock between warehouses
     */
    public function transferStock($productId, $fromWarehouseId, $toWarehouseId, $quantity, $reason = null, $productStockId = null)
    {
        $transfer = WarehouseStockTransfer::create([
            'product_id' => $productId,
            'product_stock_id' => $productStockId,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'quantity' => $quantity,
            'reason' => $reason,
            'status' => 'pending',
            'transfer_date' => now(),
            'initiated_by' => auth()->id()
        ]);

        return $transfer;
    }

    /**
     * Update stock levels
     */
    public function updateStock($warehouseId, $productId, $quantity, $productStockId = null, $operation = 'set')
    {
        $warehouseStock = WarehouseStock::firstOrCreate([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'product_stock_id' => $productStockId
        ], [
            'quantity' => 0,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 10
        ]);

        switch ($operation) {
            case 'add':
                $warehouseStock->incrementStock($quantity, 'Manual adjustment - Add');
                break;
            case 'subtract':
                $warehouseStock->decrementStock($quantity, 'Manual adjustment - Subtract');
                break;
            case 'set':
            default:
                $warehouseStock->update(['quantity' => $quantity]);
                break;
        }

        // Check for low stock alerts
        WarehouseLowStockAlert::checkAndCreateAlert($warehouseStock);

        return $warehouseStock;
    }

    /**
     * Sync warehouse stocks when product is created/updated
     */
    public function syncProductWarehouseStocks($product, $warehouseStocks)
    {
        // Delete existing warehouse stocks for this product
        $product->warehouse_stocks()->delete();

        if (is_array($warehouseStocks)) {
            foreach ($warehouseStocks as $warehouseId => $stockQuantity) {
                if (is_numeric($warehouseId)) {
                    // Get all product stocks for this product
                    $productStocks = $product->stocks;
                    
                    if ($productStocks->count() > 0) {
                        // Create warehouse stock for each product variant
                        foreach ($productStocks as $productStock) {
                            WarehouseStock::create([
                                'product_id' => $product->id,
                                'product_stock_id' => $productStock->id,
                                'warehouse_id' => $warehouseId,
                                'quantity' => $stockQuantity,
                                'reserved_quantity' => 0,
                                'low_stock_threshold' => 10
                            ]);
                        }
                    } else {
                        // Create warehouse stock for simple product
                        WarehouseStock::create([
                            'product_id' => $product->id,
                            'product_stock_id' => null,
                            'warehouse_id' => $warehouseId,
                            'quantity' => $stockQuantity,
                            'reserved_quantity' => 0,
                            'low_stock_threshold' => 10
                        ]);
                    }
                }
            }
        }

        return true;
    }
}
