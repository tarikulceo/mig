<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockTransfer;
use App\Models\WarehouseLowStockAlert;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    protected $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
        
        // Staff Permission Check
        $this->middleware(['permission:view_warehouses'])->only('index');
        $this->middleware(['permission:add_warehouse'])->only('create', 'store');
        $this->middleware(['permission:edit_warehouse'])->only('edit', 'update');
        $this->middleware(['permission:delete_warehouse'])->only('destroy');
        $this->middleware(['permission:manage_warehouse_stock'])->only(['stock', 'updateStock', 'transferStock']);
        $this->middleware(['permission:view_warehouse_reports'])->only(['reports', 'getReport']);
    }

    /**
     * Display a listing of warehouses
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $warehouses = Warehouse::when($search, function ($query) use ($search) {
            return $query->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('manager_name', 'like', "%{$search}%");
        })->orderBy('created_at', 'desc')->paginate(10);

        return view('backend.warehouse.index', compact('warehouses', 'search'));
    }

    /**
     * Show the form for creating a new warehouse
     */
    public function create()
    {
        return view('backend.warehouse.create');
    }

    /**
     * Store a newly created warehouse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'manager_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'managed_by' => 'nullable|array',
            'managed_by.*' => 'exists:users,id'
        ]);

        $data = $request->except(['managed_by']);
        $data['managed_by'] = $request->managed_by ? array_map('intval', $request->managed_by) : null;
        $data['created_by'] = auth()->id();

        Warehouse::create($data);

        flash(translate('Warehouse has been created successfully'))->success();
        return redirect()->route('warehouses.index');
    }

    /**
     * Display the specified warehouse
     */
    public function show(Warehouse $warehouse)
    {
        $warehouse->load(['stocks.product', 'stocks.productStock']);
        $lowStockAlerts = $warehouse->lowStockAlerts()->active()->with(['product', 'productStock'])->get();
        $recentTransfers = WarehouseStockTransfer::where(function($query) use ($warehouse) {
            $query->where('from_warehouse_id', $warehouse->id)
                  ->orWhere('to_warehouse_id', $warehouse->id);
        })->with(['product', 'fromWarehouse', 'toWarehouse', 'initiatedBy'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

        return view('backend.warehouse.show', compact('warehouse', 'lowStockAlerts', 'recentTransfers'));
    }

    /**
     * Show the form for editing the specified warehouse
     */
    public function edit(Warehouse $warehouse)
    {
        return view('backend.warehouse.edit', compact('warehouse'));
    }

    /**
     * Update the specified warehouse
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'manager_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
            'managed_by' => 'nullable|array',
            'managed_by.*' => 'exists:users,id'
        ]);

        $data = $request->except(['managed_by']);
        $data['managed_by'] = $request->managed_by ? array_map('intval', $request->managed_by) : null;

        $warehouse->update($data);

        flash(translate('Warehouse has been updated successfully'))->success();
        return redirect()->route('warehouses.index');
    }

    /**
     * Remove the specified warehouse
     */
    public function destroy(Warehouse $warehouse)
    {
        // Check if warehouse has any stock or orders
        if ($warehouse->stocks()->exists() || $warehouse->orders()->exists()) {
            flash(translate('Cannot delete warehouse with existing stock or orders'))->error();
            return back();
        }

        $warehouse->delete();
        flash(translate('Warehouse has been deleted successfully'))->success();
        return redirect()->route('warehouses.index');
    }

    /**
     * Manage warehouse stock
     */
    public function stock(Warehouse $warehouse, Request $request)
    {
        $search = $request->search;
        $stocks = $warehouse->stocks()->with(['product', 'productStock'])
            ->when($search, function ($query) use ($search) {
                return $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('quantity', 'asc')
            ->paginate(20);

        return view('backend.warehouse.stock', compact('warehouse', 'stocks', 'search'));
    }

    /**
     * Update stock levels
     */
    public function updateStock(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_stock_id' => 'nullable|exists:product_stocks,id',
            'quantity' => 'required|integer|min:0',
            'operation' => 'required|in:set,add,subtract',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $warehouseStock = $this->warehouseService->updateStock(
            $warehouse->id,
            $request->product_id,
            $request->quantity,
            $request->product_stock_id,
            $request->operation
        );

        if ($request->low_stock_threshold !== null) {
            $warehouseStock->update(['low_stock_threshold' => $request->low_stock_threshold]);
        }

        flash(translate('Stock has been updated successfully'))->success();
        return back();
    }

    /**
     * Show stock transfer form
     */
    public function showTransferForm(Request $request)
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::where('published', 1)
            ->where('approved', 1)
            ->with('stocks')
            ->get();

        return view('backend.warehouse.transfer', compact('warehouses', 'products'));
    }

    /**
     * Create stock transfer
     */
    public function transferStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_stock_id' => 'nullable|exists:product_stocks,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        // Check if source warehouse has enough stock
        $fromWarehouse = Warehouse::findOrFail($request->from_warehouse_id);
        $availableStock = $fromWarehouse->getAvailableStockForProduct(
            $request->product_id,
            $request->product_stock_id
        );

        if ($availableStock < $request->quantity) {
            flash(translate('Insufficient stock in source warehouse'))->error();
            return back();
        }

        $transfer = $this->warehouseService->transferStock(
            $request->product_id,
            $request->from_warehouse_id,
            $request->to_warehouse_id,
            $request->quantity,
            $request->reason,
            $request->product_stock_id
        );

        flash(translate('Stock transfer has been initiated successfully'))->success();
        return redirect()->route('warehouse.transfers.index');
    }

    /**
     * List all transfers
     */
    public function transfers(Request $request)
    {
        $status = $request->status;
        $transfers = WarehouseStockTransfer::with([
            'product', 'productStock', 'fromWarehouse', 'toWarehouse', 'initiatedBy', 'approvedBy'
        ])
        ->when($status, function ($query) use ($status) {
            return $query->where('status', $status);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(20);

        return view('backend.warehouse.transfers', compact('transfers', 'status'));
    }

    /**
     * Approve transfer
     */
    public function approveTransfer(WarehouseStockTransfer $transfer)
    {
        $transfer->approve();
        flash(translate('Transfer has been approved'))->success();
        return back();
    }

    /**
     * Complete transfer
     */
    public function completeTransfer(WarehouseStockTransfer $transfer)
    {
        if ($transfer->complete()) {
            flash(translate('Transfer has been completed successfully'))->success();
        } else {
            flash(translate('Error completing transfer'))->error();
        }
        return back();
    }

    /**
     * Cancel transfer
     */
    public function cancelTransfer(WarehouseStockTransfer $transfer, Request $request)
    {
        $transfer->cancel($request->reason);
        flash(translate('Transfer has been cancelled'))->success();
        return back();
    }

    /**
     * Low stock alerts
     */
    public function lowStockAlerts(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $status = $request->status ?? 'active';
        $alertType = $request->alert_type ?? 'all'; // all, warehouse, global

        $alerts = WarehouseLowStockAlert::with(['warehouse', 'product', 'productStock', 'resolvedBy'])
            ->when($warehouseId, function ($query) use ($warehouseId) {
                return $query->where('warehouse_id', $warehouseId);
            })
            ->when($alertType === 'warehouse', function ($query) {
                return $query->warehouseSpecific();
            })
            ->when($alertType === 'global', function ($query) {
                return $query->global();
            })
            ->where('status', $status)
            ->orderBy('alerted_at', 'desc')
            ->paginate(20);

        // Get additional low stock products that might not have alerts yet
        if ($status === 'active') {
            $allLowStockProducts = $this->warehouseService->getAllLowStockProducts();
            
            // Create alerts for products that don't have active alerts
            foreach ($allLowStockProducts as $lowStockItem) {
                if (isset($lowStockItem->is_global) && $lowStockItem->is_global) {
                    // Check for global alert
                    WarehouseLowStockAlert::checkGlobalLowStock(
                        $lowStockItem->product, 
                        $lowStockItem->productStock ? $lowStockItem->productStock->id : null
                    );
                } else {
                    // Check for warehouse-specific alert
                    if (isset($lowStockItem->warehouse)) {
                        $warehouseStock = $lowStockItem;
                        WarehouseLowStockAlert::checkAndCreateAlert($warehouseStock);
                    }
                }
            }
        }

        $warehouses = Warehouse::active()->get();

        return view('backend.warehouse.alerts', compact('alerts', 'warehouses', 'warehouseId', 'status', 'alertType'));
    }

    /**
     * Resolve alert
     */
    public function resolveAlert(WarehouseLowStockAlert $alert, Request $request)
    {
        $alert->resolve($request->notes);
        flash(translate('Alert has been resolved'))->success();
        return back();
    }

    /**
     * Ignore alert
     */
    public function ignoreAlert(WarehouseLowStockAlert $alert, Request $request)
    {
        $alert->ignore($request->notes);
        flash(translate('Alert has been ignored'))->success();
        return back();
    }

    /**
     * Warehouse reports
     */
    public function reports(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $warehouses = Warehouse::active()->get();
        $report = null;

        if ($warehouseId) {
            $report = $this->warehouseService->getWarehouseStockReport($warehouseId, $startDate, $endDate);
        }

        return view('backend.warehouse.reports', compact('warehouses', 'report', 'warehouseId', 'startDate', 'endDate'));
    }

    /**
     * Get product stock data for AJAX
     */
    public function getProductStock(Request $request)
    {
        $productId = $request->product_id;
        $productStocks = ProductStock::where('product_id', $productId)->get();
        
        return response()->json($productStocks);
    }

    /**
     * Get warehouse stock for a product
     */
    public function getWarehouseStock(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $productId = $request->product_id;
        $productStockId = $request->product_stock_id;

        $query = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId);

        if ($productStockId && $productStockId !== 'null') {
            $query->where('product_stock_id', $productStockId);
        } else {
            $query->whereNull('product_stock_id');
        }

        $warehouseStock = $query->first();

        return response()->json([
            'available_quantity' => $warehouseStock ? $warehouseStock->available_quantity : 0,
            'total_quantity' => $warehouseStock ? $warehouseStock->quantity : 0,
            'reserved_quantity' => $warehouseStock ? $warehouseStock->reserved_quantity : 0,
            'low_stock_threshold' => $warehouseStock ? $warehouseStock->low_stock_threshold : 10,
        ]);
    }
}
