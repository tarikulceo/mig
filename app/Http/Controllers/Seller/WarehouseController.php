<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockTransfer;
use App\Models\WarehouseLowStockAlert;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Auth;

class WarehouseController extends Controller
{
    protected $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Display a listing of seller's warehouses
     */
    public function index(Request $request)
    {
        $search = $request->search;
        
        // Get warehouses accessible by the seller
        $warehouses = Warehouse::accessibleBy(Auth::id())
            ->active()
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('seller.warehouse.index', compact('warehouses', 'search'));
    }

    /**
     * Display warehouse details for seller
     */
    public function show(Warehouse $warehouse)
    {
        // Check if seller can access this warehouse
        if (!$warehouse->canBeAccessedBy(Auth::id())) {
            abort(403, 'Unauthorized access to warehouse');
        }

        // Get seller's products in this warehouse
        $sellerProducts = Product::where('user_id', Auth::user()->id)
            ->where('published', 1)
            ->pluck('id');

        $stocks = $warehouse->stocks()
            ->whereIn('product_id', $sellerProducts)
            ->with(['product', 'productStock'])
            ->get();

        $lowStockAlerts = $warehouse->lowStockAlerts()
            ->active()
            ->whereIn('product_id', $sellerProducts)
            ->with(['product', 'productStock'])
            ->get();

        $recentTransfers = WarehouseStockTransfer::where(function($query) use ($warehouse) {
            $query->where('from_warehouse_id', $warehouse->id)
                  ->orWhere('to_warehouse_id', $warehouse->id);
        })
        ->whereIn('product_id', $sellerProducts)
        ->with(['product', 'fromWarehouse', 'toWarehouse', 'initiatedBy'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

        return view('seller.warehouse.show', compact('warehouse', 'stocks', 'lowStockAlerts', 'recentTransfers'));
    }

    /**
     * Manage seller's products stock in warehouse
     */
    public function stock(Warehouse $warehouse, Request $request)
    {
        $search = $request->search;
        // Get seller's products only
        $sellerProducts = Product::where('user_id', Auth::user()->id)
            ->where('published', 1)
            ->get();

        $sellerProductIds = $sellerProducts->pluck('id');

        $stocks = $warehouse->stocks()
            ->whereIn('product_id', $sellerProductIds)
            ->with(['product', 'productStock'])
            ->when($search, function ($query) use ($search) {
                return $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('quantity', 'asc')
            ->paginate(20);

        return view('seller.warehouse.stock', compact('warehouse', 'stocks', 'search', 'sellerProducts'));
    }

    /**
     * Update seller's product stock levels
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

        // Verify the product belongs to the seller
        $product = Product::where('id', $request->product_id)
            ->where('user_id', Auth::user()->id)
            ->first();

        if (!$product) {
            flash(translate('You can only manage your own products'))->error();
            return back();
        }

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
     * Show transfer form for seller
     */
    public function showTransferForm(Request $request)
    {
        $warehouses = Warehouse::accessibleBy(Auth::id())->active()->get();
        $products = Product::where('user_id', Auth::user()->id)
            ->where('published', 1)
            ->where('approved', 1)
            ->with('stocks')
            ->get();

        return view('seller.warehouse.transfer', compact('warehouses', 'products'));
    }

    /**
     * Create stock transfer for seller's products
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

        // Verify the product belongs to the seller
        $product = Product::where('id', $request->product_id)
            ->where('user_id', Auth::user()->id)
            ->first();

        if (!$product) {
            flash(translate('You can only transfer your own products'))->error();
            return back();
        }

        // Check if source warehouse has enough stock
        $fromWarehouse = Warehouse::accessibleBy(Auth::id())->findOrFail($request->from_warehouse_id);
        $toWarehouse = Warehouse::accessibleBy(Auth::id())->findOrFail($request->to_warehouse_id);
        
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
        return redirect()->route('seller.warehouse.transfers');
    }

    /**
     * List seller's transfers
     */
    public function transfers(Request $request)
    {
        $status = $request->status;
        
        // Get seller's products
        $sellerProducts = Product::where('user_id', Auth::user()->id)->pluck('id');

        $transfers = WarehouseStockTransfer::with([
            'product', 'productStock', 'fromWarehouse', 'toWarehouse', 'initiatedBy', 'approvedBy'
        ])
        ->whereIn('product_id', $sellerProducts)
        ->when($status, function ($query) use ($status) {
            return $query->where('status', $status);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(20);

        return view('seller.warehouse.transfers', compact('transfers', 'status'));
    }

    /**
     * Seller's low stock alerts
     */
    public function lowStockAlerts(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $status = $request->status ?? 'active';

        // Get seller's products
        $sellerProducts = Product::where('user_id', Auth::user()->id)->pluck('id');

        $alerts = WarehouseLowStockAlert::with(['warehouse', 'product', 'productStock', 'resolvedBy'])
            ->whereIn('product_id', $sellerProducts)
            ->when($warehouseId, function ($query) use ($warehouseId) {
                return $query->where('warehouse_id', $warehouseId);
            })
            ->where('status', $status)
            ->orderBy('alerted_at', 'desc')
            ->paginate(20);

        $warehouses = Warehouse::accessibleBy(Auth::id())->active()->get();

        return view('seller.warehouse.alerts', compact('alerts', 'warehouses', 'warehouseId', 'status'));
    }

    /**
     * Seller reports
     */
    public function reports(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $warehouses = Warehouse::accessibleBy(Auth::id())->active()->get();
        $sellerProducts = Product::where('user_id', Auth::user()->id)->pluck('id');
        
        $report = null;

        if ($warehouseId) {
            $warehouse = Warehouse::accessibleBy(Auth::id())->findOrFail($warehouseId);
            
            // Custom report for seller
            $report = [
                'warehouse' => $warehouse,
                'seller_products_count' => $warehouse->stocks()->whereIn('product_id', $sellerProducts)->distinct('product_id')->count(),
                'seller_total_stock' => $warehouse->stocks()->whereIn('product_id', $sellerProducts)->sum('quantity'),
                'seller_low_stock_products' => $warehouse->stocks()->whereIn('product_id', $sellerProducts)->lowStock()->count(),
                'seller_active_alerts' => $warehouse->lowStockAlerts()->whereIn('product_id', $sellerProducts)->active()->count(),
            ];
        }

        return view('seller.warehouse.reports', compact('warehouses', 'report', 'warehouseId', 'startDate', 'endDate'));
    }

    /**
     * Get product stock data for seller's products
     */
    public function getProductStock(Request $request)
    {
        $productId = $request->product_id;
        
        // Verify product belongs to seller
        $product = Product::where('id', $productId)
            ->where('user_id', Auth::user()->id)
            ->first();

        if (!$product) {
            return response()->json([], 403);
        }

        $productStocks = ProductStock::where('product_id', $productId)->get();
        
        return response()->json($productStocks);
    }

    /**
     * Get warehouse stock for seller's product
     */
    public function getWarehouseStock(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $productId = $request->product_id;
        $productStockId = $request->product_stock_id;

        // Verify product belongs to seller
        $product = Product::where('id', $productId)
            ->where('user_id', Auth::user()->id)
            ->first();

        if (!$product) {
            return response()->json([], 403);
        }

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

    /**
     * Show warehouse creation form
     */
    public function create()
    {
        return view('seller.warehouse.create');
    }

    /**
     * Store a new warehouse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'manager_name' => 'nullable|string|max:255',
            'contact_info' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $warehouse = Warehouse::create([
            'name' => $request->name,
            'address' => $request->address,
            'manager_name' => $request->manager_name,
            'contact_info' => $request->contact_info,
            'email' => $request->email,
            'phone' => $request->phone,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
            'is_active' => true,
            'created_by' => Auth::user()->id,
            'managed_by' => json_encode([Auth::user()->id])
        ]);

        flash(translate('Warehouse has been created successfully'))->success();
        return redirect()->route('seller.warehouses.index');
    }

    /**
     * Show warehouse edit form
     */
    public function edit(Warehouse $warehouse)
    {
        // Check if warehouse can be accessed by seller
        if (!$warehouse->canBeAccessedBy(Auth::user())) {
            flash(translate('Access denied'))->error();
            return redirect()->route('seller.warehouses.index');
        }

        return view('seller.warehouse.edit', compact('warehouse'));
    }

    /**
     * Update warehouse
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        // Check if warehouse can be accessed by seller
        if (!$warehouse->canBeAccessedBy(Auth::user())) {
            flash(translate('Access denied'))->error();
            return redirect()->route('seller.warehouses.index');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'manager_name' => 'nullable|string|max:255',
            'contact_info' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $warehouse->update([
            'name' => $request->name,
            'address' => $request->address,
            'manager_name' => $request->manager_name,
            'contact_info' => $request->contact_info,
            'email' => $request->email,
            'phone' => $request->phone,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
        ]);

        flash(translate('Warehouse has been updated successfully'))->success();
        return redirect()->route('seller.warehouses.index');
    }

    /**
     * Delete warehouse
     */
    public function destroy(Warehouse $warehouse)
    {
        // Check if warehouse can be accessed by seller
        if (!$warehouse->canBeAccessedBy(Auth::user())) {
            flash(translate('Access denied'))->error();
            return redirect()->route('seller.warehouses.index');
        }

        // Check if warehouse has any stock
        if ($warehouse->stocks()->exists()) {
            flash(translate('Cannot delete warehouse with existing stock'))->error();
            return back();
        }

        $warehouse->delete();
        flash(translate('Warehouse has been deleted successfully'))->success();
        return redirect()->route('seller.warehouses.index');
    }
}
