<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesRepresentative;
use App\Models\SalesCommission;
use App\Models\SalesTarget;
use App\Models\SalesActivity;
use App\Models\RetailStore;
use App\Models\StoreVisit;
use App\Models\Order;
use App\Models\Customer;
use Carbon\Carbon;
use Auth;

class PersonalSalesController extends Controller
{
    public function __construct()
    {
        // Only allow authenticated sales representatives
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            if (Auth::user()->user_type !== 'sales_rep') {
                abort(403, 'Unauthorized access');
            }
            return $next($request);
        });
    }

    /**
     * Personal Dashboard for Sales Representative
     */
    public function dashboard()
    {
        $salesRep = Auth::user()->salesRepresentative;
        
        if (!$salesRep) {
            flash(translate('Sales Representative profile not found'))->error();
            return redirect()->route('admin.dashboard');
        }

        // Personal statistics
        $currentMonth = Carbon::now();
        
        // My orders this month
        $myOrders = Order::where('sales_rep_id', $salesRep->id)
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->count();
            
        // My sales this month
        $mySales = Order::where('sales_rep_id', $salesRep->id)
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->where('delivery_status', 'delivered')
            ->sum('grand_total');
            
        // My commissions this month
        $myCommissions = SalesCommission::where('sales_rep_id', $salesRep->id)
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('commission_amount');

        // My customers
        $myCustomers = Customer::where('sales_rep_id', $salesRep->id)->count();

        // My stores
        $myStores = RetailStore::where('sales_rep_id', $salesRep->id)->count();

        // My store visits this month
        $myVisits = StoreVisit::where('sales_rep_id', $salesRep->id)
            ->whereMonth('visit_date', $currentMonth->month)
            ->whereYear('visit_date', $currentMonth->year)
            ->count();

        // Recent activities
        $recentActivities = SalesActivity::where('sales_rep_id', $salesRep->id)
            ->with(['customer.user'])
            ->orderBy('activity_date', 'desc')
            ->take(10)
            ->get();

        // Recent orders
        $recentOrders = Order::where('sales_rep_id', $salesRep->id)
            ->with(['customer.user'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Recent store visits
        $recentVisits = StoreVisit::where('sales_rep_id', $salesRep->id)
            ->with(['retailStore'])
            ->orderBy('visit_date', 'desc')
            ->take(5)
            ->get();

        return view('backend.personal_sales.dashboard', compact(
            'salesRep', 'myOrders', 'mySales', 'myCommissions', 
            'myCustomers', 'myStores', 'myVisits', 
            'recentActivities', 'recentOrders', 'recentVisits'
        ));
    }

    /**
     * My Profile
     */
    public function profile()
    {
        $salesRep = Auth::user()->salesRepresentative;
        
        if (!$salesRep) {
            flash(translate('Sales Representative profile not found'))->error();
            return redirect()->route('personal.dashboard');
        }

        // Load relationships
        $salesRep->load(['user', 'territory', 'manager']);
        
        // Performance data
        $monthlyPerformance = $this->getPersonalPerformance($salesRep);
        
        return view('backend.personal_sales.profile', compact('salesRep', 'monthlyPerformance'));
    }

    /**
     * My Commissions
     */
    public function commissions(Request $request)
    {
        $salesRep = Auth::user()->salesRepresentative;
        
        if (!$salesRep) {
            flash(translate('Sales Representative profile not found'))->error();
            return redirect()->route('personal.dashboard');
        }

        $commissions = SalesCommission::where('sales_rep_id', $salesRep->id)
            ->with(['order'])
            ->orderBy('created_at', 'desc');

        // Filter by date range if provided
        if ($request->has('date_range') && $request->date_range) {
            $date_range_array = explode(" / ", $request->date_range);
            $commissions = $commissions->whereBetween('created_at', [$date_range_array[0], $date_range_array[1]]);
        }

        $commissions = $commissions->paginate(15);
        
        // Summary
        $totalCommissions = SalesCommission::where('sales_rep_id', $salesRep->id)->sum('commission_amount');
        $paidCommissions = SalesCommission::where('sales_rep_id', $salesRep->id)
            ->where('payment_status', 'paid')->sum('commission_amount');
        $pendingCommissions = SalesCommission::where('sales_rep_id', $salesRep->id)
            ->where('payment_status', 'pending')->sum('commission_amount');

        return view('backend.personal_sales.commissions', compact(
            'commissions', 'totalCommissions', 'paidCommissions', 'pendingCommissions'
        ));
    }

    /**
     * My Targets
     */
    public function targets()
    {
        $salesRep = Auth::user()->salesRepresentative;
        
        if (!$salesRep) {
            flash(translate('Sales Representative profile not found'))->error();
            return redirect()->route('personal.dashboard');
        }

        $targets = SalesTarget::where('sales_rep_id', $salesRep->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('backend.personal_sales.targets', compact('targets', 'salesRep'));
    }

    /**
     * My Activities
     */
    public function activities(Request $request)
    {
        $salesRep = Auth::user()->salesRepresentative;
        
        if (!$salesRep) {
            flash(translate('Sales Representative profile not found'))->error();
            return redirect()->route('personal.dashboard');
        }

        $activities = SalesActivity::where('sales_rep_id', $salesRep->id)
            ->with(['customer.user'])
            ->orderBy('activity_date', 'desc');

        // Filter by activity type
        if ($request->has('activity_type') && $request->activity_type) {
            $activities = $activities->where('activity_type', $request->activity_type);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $activities = $activities->where('status', $request->status);
        }

        $activities = $activities->paginate(15);

        return view('backend.personal_sales.activities', compact('activities'));
    }

    /**
     * My Customers
     */
    public function customers()
    {
        $salesRep = Auth::user()->salesRepresentative;
        
        if (!$salesRep) {
            flash(translate('Sales Representative profile not found'))->error();
            return redirect()->route('personal.dashboard');
        }

        $customers = Customer::where('sales_rep_id', $salesRep->id)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('backend.personal_sales.customers', compact('customers'));
    }

    /**
     * Calculate personal performance
     */
    private function getPersonalPerformance($salesRep)
    {
        $currentMonth = Carbon::now();
        
        return [
            'monthly_sales' => Order::where('sales_rep_id', $salesRep->id)
                ->whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->where('delivery_status', 'delivered')
                ->sum('grand_total'),
            'monthly_orders' => Order::where('sales_rep_id', $salesRep->id)
                ->whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->count(),
            'monthly_commissions' => SalesCommission::where('sales_rep_id', $salesRep->id)
                ->whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->sum('commission_amount'),
        ];
    }
}