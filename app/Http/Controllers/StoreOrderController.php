<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use App\Models\StoreOrderPartialPayment;
use App\Models\RetailStore;
use App\Models\SalesRepresentative;
use App\Models\Product;
use App\Models\StoreVisit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StoreOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display a listing of store orders
     */
    public function index(Request $request)
    {
        $query = StoreOrder::with(['retailStore', 'salesRepresentative.user', 'storeVisit']);

        // If user is sales rep, only show their orders
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep) {
                $query->where('sales_rep_id', $salesRep->id);
            }
        }

        // Apply filters
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhereHas('retailStore', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('order_status', $request->status);
        }

        if ($request->has('payment_status') && $request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('sales_rep_id') && $request->sales_rep_id) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get filter options
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();
        
        return view('backend.store_orders.index', compact('orders', 'salesReps'));
    }

    /**
     * Show the form for creating a new store order
     */
    public function create(Request $request)
    {
        // Get stores and products
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            $stores = RetailStore::where('sales_rep_id', $salesRep->id ?? 0)->get();
        } else {
            $stores = RetailStore::with('salesRepresentative.user')->get();
        }

        $products = Product::where('published', 1)
                          ->with('thumbnail')
                          ->select('id', 'name', 'unit_price', 'slug')
                          ->get();
        
        // Get store from request if creating order from store visit
        $selectedStore = null;
        $selectedVisit = null;
        
        if ($request->has('store_id')) {
            $selectedStore = RetailStore::find($request->store_id);
        }
        
        if ($request->has('visit_id')) {
            $selectedVisit = StoreVisit::find($request->visit_id);
            if ($selectedVisit) {
                $selectedStore = $selectedVisit->retailStore;
            }
        }

        return view('backend.store_orders.create', compact('stores', 'products', 'selectedStore', 'selectedVisit'));
    }

    /**
     * Store a newly created store order
     */
    public function store(Request $request)
    {
        $request->validate([
            'retail_store_id' => 'required|exists:retail_stores,id',
            'store_visit_id' => 'nullable|exists:store_visits,id',
            'payment_method' => 'required|in:cash,card,bank_transfer,credit,cheque',
            'order_notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            $store = RetailStore::findOrFail($request->retail_store_id);
            $salesRep = $store->salesRepresentative;

            // Create store order
            $order = new StoreOrder();
            $order->order_code = StoreOrder::generateOrderCode();
            $order->retail_store_id = $request->retail_store_id;
            $order->sales_rep_id = $salesRep->id;
            $order->store_visit_id = $request->store_visit_id;
            $order->payment_method = $request->payment_method;
            $order->order_notes = $request->order_notes;
            $order->commission_rate = $salesRep->commission_rate ?? 5;
            
            // Calculate totals
            $subtotal = 0;
            $orderItems = [];

            foreach ($request->products as $productData) {
                $product = Product::find($productData['product_id']);
                $quantity = $productData['quantity'];
                $unitPrice = $productData['unit_price'];
                $totalPrice = $quantity * $unitPrice;
                
                $subtotal += $totalPrice;
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'discount_amount' => $productData['discount_amount'] ?? 0,
                    'product_variation' => $productData['variation'] ?? null,
                    'item_notes' => $productData['notes'] ?? null
                ];
            }

            $taxAmount = ($subtotal * 0.1); // 10% tax (configurable)
            $grandTotal = $subtotal + $taxAmount;

            $order->subtotal = $subtotal;
            $order->tax_amount = $taxAmount;
            $order->grand_total = $grandTotal;
            $order->save();

            // Create order items
            foreach ($orderItems as $itemData) {
                $orderItem = new StoreOrderItem($itemData);
                $orderItem->store_order_id = $order->id;
                $orderItem->save();
            }

            // Calculate commission
            $order->calculateCommission();

            // Update store visit if applicable
            if ($request->store_visit_id) {
                $visit = StoreVisit::find($request->store_visit_id);
                $visit->order_amount += $grandTotal;
                $visit->order_placed = true;
                $visit->save();
            }

            DB::commit();
            flash(translate('Store order created successfully'))->success();
            return redirect()->route('store_orders.show', $order->id);

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong: ') . $e->getMessage())->error();
            return back()->withInput();
        }
    }

    /**
     * Display the specified store order
     */
    public function show($id)
    {
        $order = StoreOrder::with([
            'retailStore.salesRepresentative.user',
            'salesRepresentative.user',
            'storeVisit',
            'orderItems.product'
        ])->findOrFail($id);

        // Check permission for sales rep
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $order->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to this order.');
            }
        }

        return view('backend.store_orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'order_status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string'
        ]);

        $order = StoreOrder::findOrFail($id);

        // Check permission for sales rep
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $order->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to this order.');
            }
        }

        DB::beginTransaction();
        try {
            $oldStatus = $order->order_status;
            $order->order_status = $request->order_status;
            
            if ($request->notes) {
                $order->order_notes .= "\n" . date('Y-m-d H:i') . ": " . $request->notes;
            }

            // Handle delivered status
            if ($request->order_status === 'delivered' && $oldStatus !== 'delivered') {
                $order->delivery_date = now();
                $order->updateStock(); // Update product stock
            }

            // Handle cancelled status
            if ($request->order_status === 'cancelled' && $oldStatus !== 'cancelled') {
                // Revert stock if was delivered
                if ($oldStatus === 'delivered') {
                    // Restore stock logic here
                }
            }

            $order->save();

            DB::commit();
            flash(translate('Order status updated successfully'))->success();
            return back();

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong: ') . $e->getMessage())->error();
            return back();
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,partial,failed,refunded',
            'payment_notes' => 'nullable|string'
        ]);

        $order = StoreOrder::findOrFail($id);

        // Check permission
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $order->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to this order.');
            }
        }

        DB::beginTransaction();
        try {
            $order->payment_status = $request->payment_status;
            
            if ($request->payment_notes) {
                $order->order_notes .= "\n" . date('Y-m-d H:i') . " (Payment): " . $request->payment_notes;
            }

            // Mark commission as paid if order is fully paid and delivered
            if ($request->payment_status === 'paid' && $order->order_status === 'delivered') {
                $order->commission_paid = true;
            }

            $order->save();

            DB::commit();
            flash(translate('Payment status updated successfully'))->success();
            return back();

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong: ') . $e->getMessage())->error();
            return back();
        }
    }

    /**
     * Add partial payment to store order
     */
    public function addPartialPayment(Request $request, $id)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
            'payment_notes' => 'nullable|string',
            'payment_date' => 'required|date'
        ]);

        $order = StoreOrder::findOrFail($id);

        // Check permission
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $order->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to this order.');
            }
        }

        // Validate payment amount doesn't exceed remaining balance
        $remainingAmount = $order->grand_total - $order->paid_amount;
        if ($request->payment_amount > $remainingAmount) {
            flash(translate('Payment amount cannot exceed remaining balance of ') . number_format($remainingAmount, 2))->error();
            return back();
        }

        DB::beginTransaction();
        try {
            // Create partial payment record
            $partialPayment = StoreOrderPartialPayment::create([
                'store_order_id' => $order->id,
                'retail_store_id' => $order->retail_store_id,
                'payment_amount' => $request->payment_amount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'payment_notes' => $request->payment_notes,
                'payment_date' => $request->payment_date,
                'recorded_by' => auth()->id(),
                'status' => 'completed'
            ]);

            // Update order payment amounts
            $order->updatePaymentAmounts();

            // Add note to order
            $note = "Payment of " . number_format($request->payment_amount, 2) . " received via " . $request->payment_method;
            if ($request->payment_reference) {
                $note .= " (Ref: " . $request->payment_reference . ")";
            }
            $order->order_notes .= "\n" . date('Y-m-d H:i') . ": " . $note;
            $order->save();

            DB::commit();
            flash(translate('Payment added successfully'))->success();
            return back();

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong: ') . $e->getMessage())->error();
            return back();
        }
    }

    /**
     * Get payment history for an order
     */
    public function getPaymentHistory($id)
    {
        $order = StoreOrder::with(['partialPayments.recordedBy'])->findOrFail($id);
        
        // Check permission
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $order->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to this order.');
            }
        }

        return response()->json([
            'payments' => $order->partialPayments()->orderBy('payment_date', 'desc')->get(),
            'order_total' => $order->grand_total,
            'total_paid' => $order->paid_amount,
            'remaining_amount' => $order->remaining_amount
        ]);
    }

    /**
     * Generate invoice for store order
     */
    public function generateInvoice($id)
    {
        $order = StoreOrder::with([
            'retailStore',
            'salesRepresentative.user',
            'orderItems.product'
        ])->findOrFail($id);

        // Check permission
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $order->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to this order.');
            }
        }

        // Generate invoice number if not exists
        if (!$order->invoice_number) {
            $order->invoice_number = 'INV-' . $order->order_code;
            $order->save();
        }

        return view('backend.store_orders.invoice', compact('order'));
    }

    /**
     * Get orders for a specific store visit
     */
    public function getVisitOrders($visitId)
    {
        $visit = StoreVisit::findOrFail($visitId);
        
        // Check permission
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $visit->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access.');
            }
        }

        $orders = StoreOrder::where('store_visit_id', $visitId)
            ->with(['orderItems.product'])
            ->get();

        return response()->json(['orders' => $orders]);
    }

    /**
     * Quick order creation from store visit
     */
    public function quickOrder(Request $request)
    {
        $request->validate([
            'store_visit_id' => 'required|exists:store_visits,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            $visit = StoreVisit::findOrFail($request->store_visit_id);
            $product = Product::findOrFail($request->product_id);
            
            // Create quick order
            $order = new StoreOrder();
            $order->order_code = StoreOrder::generateOrderCode();
            $order->retail_store_id = $visit->retail_store_id;
            $order->sales_rep_id = $visit->sales_rep_id;
            $order->store_visit_id = $visit->id;
            $order->payment_method = 'cash';
            $order->commission_rate = $visit->salesRepresentative->commission_rate ?? 5;

            $totalPrice = $request->quantity * $request->unit_price;
            $taxAmount = $totalPrice * 0.1;
            $grandTotal = $totalPrice + $taxAmount;

            $order->subtotal = $totalPrice;
            $order->tax_amount = $taxAmount;
            $order->grand_total = $grandTotal;
            $order->save();

            // Create order item
            $orderItem = new StoreOrderItem([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total_price' => $totalPrice
            ]);
            $orderItem->store_order_id = $order->id;
            $orderItem->save();

            // Calculate commission
            $order->calculateCommission();

            // Update visit
            $visit->order_amount += $grandTotal;
            $visit->order_placed = true;
            $visit->save();

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Quick order created successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating quick order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get due amounts by retail store
     */
    public function getDueAmounts(Request $request)
    {
        $query = StoreOrder::where('due_amount', '>', 0)
            ->orderBy('created_at', 'desc');

        // Filter by retail store if provided
        if ($request->has('retail_store_id') && $request->retail_store_id) {
            $query->where('retail_store_id', $request->retail_store_id);
        }

        // Filter by sales rep if provided
        if ($request->has('sales_rep_id') && $request->sales_rep_id) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        // Filter by overdue status
        if ($request->has('overdue') && $request->overdue == '1') {
            $query->where('due_date', '<', now());
        }

        $orders = $query->paginate(20);

        // Calculate summary statistics
        $totalDue = StoreOrder::where('due_amount', '>', 0)->sum('due_amount');
        $overdueDue = StoreOrder::where('due_amount', '>', 0)
            ->where('due_date', '<', now())
            ->sum('due_amount');

        $storeWiseDue = StoreOrder::select('retail_store_id')
            ->selectRaw('SUM(due_amount) as total_due')
            ->selectRaw('COUNT(*) as order_count')
            ->where('due_amount', '>', 0)
            ->whereNotNull('retail_store_id')
            ->groupBy('retail_store_id')
            ->orderByDesc('total_due')
            ->get();

        return view('backend.store_orders.due_amounts', compact(
            'orders', 
            'totalDue', 
            'overdueDue', 
            'storeWiseDue'
        ));
    }

    /**
     * Export due amounts report
     */
    public function exportDueAmounts(Request $request)
    {
        $query = StoreOrder::where('due_amount', '>', 0);

        if ($request->has('retail_store_id') && $request->retail_store_id) {
            $query->where('retail_store_id', $request->retail_store_id);
        }

        if ($request->has('overdue') && $request->overdue == '1') {
            $query->where('due_date', '<', now());
        }

        $orders = $query->orderBy('due_date', 'asc')->get();

        $filename = 'due_amounts_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Order Code',
                'Store Name',
                'Sales Rep',
                'Order Date',
                'Due Date',
                'Grand Total',
                'Paid Amount',
                'Due Amount',
                'Days Overdue',
                'Status'
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_code,
                    $order->retailStore->store_name ?? 'N/A',
                    $order->salesRepresentative->user->name ?? 'N/A',
                    $order->order_date->format('Y-m-d'),
                    $order->due_date ? $order->due_date->format('Y-m-d') : 'N/A',
                    $order->grand_total,
                    $order->paid_amount,
                    $order->due_amount,
                    $order->days_overdue,
                    $order->payment_status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}