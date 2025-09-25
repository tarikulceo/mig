<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesCommission;
use App\Models\SalesRepresentative;
use App\Models\Order;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesCommissionController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check - ADMIN/STAFF ONLY
        $this->middleware(['permission:view_sales_commissions'])->only('index');
        $this->middleware(['permission:manage_sales_commissions'])->only(['create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware(['permission:pay_sales_commissions'])->only(['pay', 'bulkPay']);
    }

    /**
     * Display a listing of sales commissions
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $sales_rep_id = null;
        $payment_status = null;
        $date_range = null;

        $commissions = SalesCommission::with(['salesRepresentative.user', 'order'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $sort_search = $request->search;
            $commissions = $commissions->whereHas('salesRepresentative.user', function($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%');
            })->orWhereHas('order', function($query) use ($sort_search) {
                $query->where('code', 'like', '%' . $sort_search . '%');
            });
        }

        if ($request->has('sales_rep_id') && $request->sales_rep_id != '') {
            $sales_rep_id = $request->sales_rep_id;
            $commissions = $commissions->where('sales_rep_id', $sales_rep_id);
        }

        if ($request->has('payment_status') && $request->payment_status != '') {
            $payment_status = $request->payment_status;
            $commissions = $commissions->where('payment_status', $payment_status);
        }

        if ($request->has('date_range') && $request->date_range != '') {
            $date_range = $request->date_range;
            $date_range_array = explode(" / ", $request->date_range);
            $commissions = $commissions->whereBetween('created_at', [$date_range_array[0], $date_range_array[1]]);
        }

        $commissions = $commissions->paginate(15);
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();

        return view('backend.sales_commissions.index', compact('commissions', 'sort_search', 'sales_rep_id', 'payment_status', 'date_range', 'salesReps'));
    }

    /**
     * Calculate and create commission for an order
     */
    public function calculateCommission(Order $order, OrderDetail $orderDetail = null)
    {
        // Skip if order doesn't have sales rep
        if (!$order->sales_rep_id) {
            return false;
        }

        $salesRep = $order->salesRepresentative;
        if (!$salesRep || !$salesRep->isActive()) {
            return false;
        }

        // Calculate commission based on order or order detail
        if ($orderDetail) {
            $saleAmount = $orderDetail->price + $orderDetail->tax;
            $commissionAmount = ($saleAmount * $salesRep->commission_rate) / 100;

            // Create commission record
            $commission = new SalesCommission();
            $commission->sales_rep_id = $salesRep->id;
            $commission->order_id = $order->id;
            $commission->order_detail_id = $orderDetail->id;
            $commission->commission_amount = $commissionAmount;
            $commission->commission_rate = $salesRep->commission_rate;
            $commission->sale_amount = $saleAmount;
            $commission->commission_type = 'order';
            $commission->payment_status = 'pending';
            $commission->save();

        } else {
            // Commission for entire order
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
        }

        return $commission;
    }

    /**
     * Display the specified commission
     */
    public function show($id)
    {
        $commission = SalesCommission::with(['salesRepresentative.user', 'order', 'orderDetail'])
            ->findOrFail($id);
        
        return view('backend.sales_commissions.show', compact('commission'));
    }

    /**
     * Mark commission as paid
     */
    public function pay(Request $request, $id)
    {
        $commission = SalesCommission::findOrFail($id);
        
        if ($commission->isPaid()) {
            flash(translate('Commission is already paid'))->warning();
            return back();
        }

        $commission->payment_status = 'paid';
        $commission->paid_at = now();
        $commission->notes = $request->notes;
        $commission->save();

        flash(translate('Commission marked as paid successfully'))->success();
        return back();
    }

    /**
     * Bulk pay commissions
     */
    public function bulkPay(Request $request)
    {
        $request->validate([
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'exists:sales_commissions,id'
        ]);

        $updated = SalesCommission::whereIn('id', $request->commission_ids)
            ->where('payment_status', 'pending')
            ->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'notes' => $request->notes
            ]);

        flash(translate($updated . ' commissions marked as paid successfully'))->success();
        return back();
    }

    /**
     * Commission reports
     */
    public function reports(Request $request)
    {
        $salesRep = null;
        $dateRange = null;

        $query = SalesCommission::with(['salesRepresentative.user']);

        if ($request->sales_rep_id) {
            $salesRep = SalesRepresentative::findOrFail($request->sales_rep_id);
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->date_range) {
            $dateRange = $request->date_range;
            $dateRangeArray = explode(" / ", $request->date_range);
            $query->whereBetween('created_at', [$dateRangeArray[0], $dateRangeArray[1]]);
        }

        // Summary statistics
        $totalCommissions = $query->sum('commission_amount');
        $paidCommissions = $query->where('payment_status', 'paid')->sum('commission_amount');
        $pendingCommissions = $query->where('payment_status', 'pending')->sum('commission_amount');
        $commissionCount = $query->count();

        // Monthly breakdown
        $monthlyData = SalesCommission::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(commission_amount) as total_commission'),
                DB::raw('COUNT(*) as commission_count')
            )
            ->when($salesRep, function($q) use ($salesRep) {
                return $q->where('sales_rep_id', $salesRep->id);
            })
            ->when($dateRange, function($q) use ($dateRange) {
                $dateRangeArray = explode(" / ", $dateRange);
                return $q->whereBetween('created_at', [$dateRangeArray[0], $dateRangeArray[1]]);
            })
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Top performers
        $topPerformers = SalesCommission::select('sales_rep_id')
            ->with('salesRepresentative.user')
            ->selectRaw('SUM(commission_amount) as total_commission')
            ->selectRaw('COUNT(*) as commission_count')
            ->when($dateRange, function($q) use ($dateRange) {
                $dateRangeArray = explode(" / ", $dateRange);
                return $q->whereBetween('created_at', [$dateRangeArray[0], $dateRangeArray[1]]);
            })
            ->groupBy('sales_rep_id')
            ->orderBy('total_commission', 'desc')
            ->limit(10)
            ->get();

        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();

        return view('backend.sales_commissions.reports', compact(
            'totalCommissions', 'paidCommissions', 'pendingCommissions', 'commissionCount',
            'monthlyData', 'topPerformers', 'salesReps', 'salesRep', 'dateRange'
        ));
    }

    /**
     * Create manual commission
     */
    public function create()
    {
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();
        $orders = Order::whereNotNull('sales_rep_id')
            ->whereDoesntHave('commissionHistory')
            ->with('salesRepresentative.user')
            ->latest()
            ->take(100)
            ->get();
            
        return view('backend.sales_commissions.create', compact('salesReps', 'orders'));
    }

    /**
     * Store manual commission
     */
    public function store(Request $request)
    {
        $request->validate([
            'sales_rep_id' => 'required|exists:sales_representatives,id',
            'commission_amount' => 'required|numeric|min:0',
            'commission_type' => 'required|in:order,target,bonus',
            'order_id' => 'nullable|exists:orders,id',
            'sale_amount' => 'required|numeric|min:0'
        ]);

        $commission = new SalesCommission();
        $commission->sales_rep_id = $request->sales_rep_id;
        $commission->order_id = $request->order_id;
        $commission->commission_amount = $request->commission_amount;
        $commission->commission_rate = $request->sale_amount > 0 ? 
            ($request->commission_amount / $request->sale_amount) * 100 : 0;
        $commission->sale_amount = $request->sale_amount;
        $commission->commission_type = $request->commission_type;
        $commission->payment_status = 'pending';
        $commission->notes = $request->notes;
        $commission->save();

        flash(translate('Commission created successfully'))->success();
        return redirect()->route('sales_commissions.index');
    }
}
