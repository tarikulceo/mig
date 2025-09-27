<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesRepresentative;
use App\Models\SalesTerritory;
use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\SalesCommission;
use App\Models\SalesTarget;
use App\Models\SalesActivity;
use App\Services\ProductCommissionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SalesRepresentativeController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check - ADMIN/STAFF ONLY
        $this->middleware(['permission:view_sales_representatives'])->only('index');
        $this->middleware(['permission:add_sales_representative'])->only('create', 'store');
        $this->middleware(['permission:edit_sales_representative'])->only('edit', 'update');
        $this->middleware(['permission:delete_sales_representative'])->only('destroy');
        $this->middleware(['permission:view_sales_rep_dashboard'])->only('dashboard'); // Admin dashboard only
        $this->middleware(['permission:manage_sales_territories'])->only(['territories', 'createTerritory', 'storeTerritory', 'editTerritory', 'updateTerritory', 'destroyTerritory']);
        $this->middleware(['permission:view_sales_reports'])->only(['reports', 'performanceReport', 'commissionReport']);
    }

    /**
     * Display a listing of sales representatives
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $territory_id = null;
        $status = null;

        $salesReps = SalesRepresentative::with(['user', 'territory', 'manager'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $sort_search = $request->search;
            $salesReps = $salesReps->whereHas('user', function($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%')
                      ->orWhere('email', 'like', '%' . $sort_search . '%');
            })->orWhere('employee_id', 'like', '%' . $sort_search . '%');
        }

        if ($request->has('territory') && $request->territory != '') {
            $territory_id = $request->territory;
            $salesReps = $salesReps->where('territory_id', $territory_id);
        }

        if ($request->has('status') && $request->status != '') {
            $status = $request->status;
            $salesReps = $salesReps->where('status', $status);
        }

        $salesReps = $salesReps->paginate(15);
        $territories = SalesTerritory::where('is_active', 1)->get();

        return view('backend.sales_representatives.index', compact('salesReps', 'sort_search', 'territory_id', 'status', 'territories'));
    }

    /**
     * Show the form for creating a new sales representative
     */
    public function create()
    {
        $territories = SalesTerritory::where('is_active', 1)->get();
        
        // Get existing sales representatives as potential managers
        $salesRepManagers = SalesRepresentative::where('status', 1)->with('user')->get();
        
        // Get admin/staff users who can also act as managers
        $adminManagers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['admin', 'staff', 'manager']);
        })->where('user_type', 'staff')->get();
        
        // Combine both collections for manager options
        $managers = collect();
        
        // Add existing sales reps
        foreach ($salesRepManagers as $rep) {
            $managers->push((object)[
                'id' => $rep->id,
                'name' => $rep->user->name ?? '',
                'type' => 'sales_rep',
                'employee_id' => $rep->employee_id,
                'display_name' => ($rep->user->name ?? '') . ' (' . $rep->employee_id . ') - Sales Rep'
            ]);
        }
        
        // Add admin/staff users
        foreach ($adminManagers as $admin) {
            $managers->push((object)[
                'id' => 'admin_' . $admin->id,
                'name' => $admin->name,
                'type' => 'admin',
                'employee_id' => 'ADM-' . $admin->id,
                'display_name' => $admin->name . ' (Admin/Staff)'
            ]);
        }
        
        return view('backend.sales_representatives.create', compact('territories', 'managers'));
    }

    /**
     * Store a newly created sales representative
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'employee_id' => 'required|unique:sales_representatives,employee_id',
            'designation' => 'required',
            'hire_date' => 'required|date',
            'territory_id' => 'required|exists:sales_territories,id',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'base_salary' => 'required|numeric|min:0',
            'phone' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Create user
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->user_type = 'sales_rep';
            $user->password = Hash::make($request->password);
            $user->email_verified_at = now();
            $user->save();

            // Handle manager assignment
            $managerId = null;
            if ($request->manager_id) {
                // Check if it's an admin manager (prefixed with 'admin_')
                if (substr($request->manager_id, 0, 6) === 'admin_') {
                    // For admin managers, we'll store null for now as the relationship
                    // is designed for sales rep to sales rep. You could extend this
                    // to support admin managers if needed.
                    $managerId = null;
                } else {
                    // Regular sales rep manager
                    $managerId = $request->manager_id;
                }
            }

            // Create sales representative
            $salesRep = new SalesRepresentative();
            $salesRep->user_id = $user->id;
            $salesRep->employee_id = $request->employee_id;
            $salesRep->designation = $request->designation;
            $salesRep->hire_date = $request->hire_date;
            $salesRep->territory_id = $request->territory_id;
            $salesRep->sales_target_monthly = $request->sales_target_monthly ?? 0;
            $salesRep->sales_target_quarterly = $request->sales_target_quarterly ?? 0;
            $salesRep->sales_target_yearly = $request->sales_target_yearly ?? 0;
            $salesRep->commission_rate = $request->commission_rate;
            $salesRep->base_salary = $request->base_salary;
            $salesRep->manager_id = $managerId;
            $salesRep->phone = $request->phone;
            $salesRep->address = $request->address;
            $salesRep->notes = $request->notes;
            $salesRep->status = $request->status ?? 1;
            $salesRep->save();

            // Assign role to user (if role exists)
            try {
                if (\Spatie\Permission\Models\Role::where('name', 'sales_representative')->exists()) {
                    $user->assignRole('sales_representative');
                }
            } catch (\Exception $roleException) {
                // Role assignment failed, but continue - log this if needed
                \Log::info('Role assignment failed: ' . $roleException->getMessage());
            }

            DB::commit();
            flash(translate('Sales Representative has been created successfully'))->success();
            return redirect()->route('sales_representatives.index');

        } catch (\Exception $e) {
            DB::rollback();
            
            // Enhanced error logging and display
            \Log::error('Sales Representative Creation Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password'])
            ]);
            
            // Show detailed error message for debugging
            $errorMessage = 'Error: ' . $e->getMessage() . ' (File: ' . basename($e->getFile()) . ', Line: ' . $e->getLine() . ')';
            flash(translate('Something went wrong: ') . $errorMessage)->error();
            
            return back()->withInput()->withErrors(['error' => $errorMessage]);
        }
    }

    /**
     * Display the specified sales representative
     */
    public function show($id)
    {
        $salesRep = SalesRepresentative::with(['user', 'territory', 'manager'])
            ->findOrFail($id);

        // Get performance data
        $monthlyPerformance = $this->getMonthlyPerformance($salesRep);
        
        // Get recent orders and activities with error handling
        try {
            $recentOrders = $salesRep->orders()->latest()->take(10)->get();
        } catch (\Exception $e) {
            $recentOrders = collect([]);
        }
        
        try {
            $recentActivities = $salesRep->activities()->latest()->take(10)->get();
        } catch (\Exception $e) {
            $recentActivities = collect([]);
        }
        
        return view('backend.sales_representatives.show', compact('salesRep', 'monthlyPerformance', 'recentOrders', 'recentActivities'));
    }

    /**
     * Show the form for editing the specified sales representative
     */
    public function edit($id)
    {
        $salesRep = SalesRepresentative::with('user')->findOrFail($id);
        $territories = SalesTerritory::where('is_active', 1)->get();
        
        // Get existing sales representatives as potential managers (excluding current rep)
        $salesRepManagers = SalesRepresentative::where('status', 1)
            ->where('id', '!=', $id)
            ->with('user')->get();
        
        // Get admin/staff users who can also act as managers
        $adminManagers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['admin', 'staff', 'manager']);
        })->where('user_type', 'staff')->get();
        
        // Combine both collections for manager options
        $managers = collect();
        
        // Add existing sales reps
        foreach ($salesRepManagers as $rep) {
            $managers->push((object)[
                'id' => $rep->id,
                'name' => $rep->user->name ?? '',
                'type' => 'sales_rep',
                'employee_id' => $rep->employee_id,
                'display_name' => ($rep->user->name ?? '') . ' (' . $rep->employee_id . ') - Sales Rep'
            ]);
        }
        
        // Add admin/staff users
        foreach ($adminManagers as $admin) {
            $managers->push((object)[
                'id' => 'admin_' . $admin->id,
                'name' => $admin->name,
                'type' => 'admin',
                'employee_id' => 'ADM-' . $admin->id,
                'display_name' => $admin->name . ' (Admin/Staff)'
            ]);
        }
        
        return view('backend.sales_representatives.edit', compact('salesRep', 'territories', 'managers'));
    }

    /**
     * Update the specified sales representative
     */
    public function update(Request $request, $id)
    {
        $salesRep = SalesRepresentative::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $salesRep->user_id,
            'employee_id' => 'required|unique:sales_representatives,employee_id,' . $id,
            'designation' => 'required',
            'hire_date' => 'required|date',
            'territory_id' => 'required|exists:sales_territories,id',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'base_salary' => 'required|numeric|min:0',
            'phone' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Update user
            $user = $salesRep->user;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->save();

            // Handle manager assignment
            $managerId = null;
            if ($request->manager_id) {
                // Check if it's an admin manager (prefixed with 'admin_')
                if (substr($request->manager_id, 0, 6) === 'admin_') {
                    // For admin managers, we'll store null for now as the relationship
                    // is designed for sales rep to sales rep. You could extend this
                    // to support admin managers if needed.
                    $managerId = null;
                } else {
                    // Regular sales rep manager
                    $managerId = $request->manager_id;
                }
            }

            // Update sales representative
            $salesRep->employee_id = $request->employee_id;
            $salesRep->designation = $request->designation;
            $salesRep->hire_date = $request->hire_date;
            $salesRep->territory_id = $request->territory_id;
            $salesRep->sales_target_monthly = $request->sales_target_monthly ?? 0;
            $salesRep->sales_target_quarterly = $request->sales_target_quarterly ?? 0;
            $salesRep->sales_target_yearly = $request->sales_target_yearly ?? 0;
            $salesRep->commission_rate = $request->commission_rate;
            $salesRep->base_salary = $request->base_salary;
            $salesRep->manager_id = $managerId;
            $salesRep->phone = $request->phone;
            $salesRep->address = $request->address;
            $salesRep->notes = $request->notes;
            $salesRep->status = $request->status ?? 1;
            $salesRep->save();

            DB::commit();
            flash(translate('Sales Representative has been updated successfully'))->success();
            return redirect()->route('sales_representatives.index');

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong: ') . $e->getMessage())->error();
            return back();
        }
    }

    /**
     * Remove the specified sales representative from storage
     */
    public function destroy($id)
    {
        $salesRep = SalesRepresentative::findOrFail($id);
        
        DB::beginTransaction();
        try {
            // Soft delete the sales representative
            $salesRep->delete();
            
            // Deactivate the user
            $salesRep->user->update(['user_type' => 'inactive']);
            
            DB::commit();
            flash(translate('Sales Representative has been deleted successfully'))->success();
            return redirect()->route('sales_representatives.index');

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

    /**
     * Sales Representative Dashboard
     */
    public function dashboard()
    {
        $totalReps = SalesRepresentative::where('status', 1)->count();
        $totalTerritories = SalesTerritory::where('is_active', 1)->count();
        $totalCustomers = Customer::whereNotNull('sales_rep_id')->count();
        
        // Monthly statistics
        $currentMonth = Carbon::now();
        $monthlyOrders = Order::whereNotNull('sales_rep_id')
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->count();
            
        $monthlySales = Order::whereNotNull('sales_rep_id')
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->where('delivery_status', 'delivered')
            ->sum('grand_total');
            
        $monthlyCommissions = SalesCommission::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('commission_amount');

        // Top performers
        $topPerformers = SalesRepresentative::with('user')
            ->withCount(['orders' => function($query) use ($currentMonth) {
                $query->whereMonth('created_at', $currentMonth->month)
                      ->whereYear('created_at', $currentMonth->year)
                      ->where('delivery_status', 'delivered');
            }])
            ->orderBy('orders_count', 'desc')
            ->take(5)
            ->get();

        // Recent activities
        $recentActivities = SalesActivity::with(['salesRepresentative.user', 'customer.user'])
            ->latest()
            ->take(10)
            ->get();

        return view('backend.sales_representatives.dashboard', compact(
            'totalReps', 'totalTerritories', 'totalCustomers', 'monthlyOrders',
            'monthlySales', 'monthlyCommissions', 'topPerformers', 'recentActivities'
        ));
    }

    /**
     * Get monthly performance data for a sales rep
     */
    private function getMonthlyPerformance($salesRep)
    {
        $currentMonth = Carbon::now();
        
        // Get current month performance with error handling
        try {
            $currentMonthOrders = $salesRep->orders()
                ->whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->count();
        } catch (\Exception $e) {
            $currentMonthOrders = 0;
        }
            
        try {
            $currentMonthSales = $salesRep->orders()
                ->whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->where('delivery_status', 'delivered')
                ->sum('grand_total');
        } catch (\Exception $e) {
            $currentMonthSales = 0;
        }
            
        // Calculate commission (based on commission rate)
        $currentMonthCommission = ($currentMonthSales * $salesRep->commission_rate) / 100;
        
        // Get current month customers
        try {
            // Try to get customers count, but fall back if the table doesn't exist or relationship fails
            if (\Schema::hasTable('customers') && \Schema::hasColumn('customers', 'sales_rep_id')) {
                $currentMonthCustomers = $salesRep->customers()
                    ->whereMonth('created_at', $currentMonth->month)
                    ->whereYear('created_at', $currentMonth->year)
                    ->distinct('user_id')
                    ->count();
            } else {
                $currentMonthCustomers = 0;
            }
        } catch (\Exception $e) {
            $currentMonthCustomers = 0;
        }
        
        // Get chart data for the last 12 months
        $months = [];
        $sales = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            try {
                $monthlySale = $salesRep->orders()
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->where('delivery_status', 'delivered')
                    ->sum('grand_total');
            } catch (\Exception $e) {
                $monthlySale = 0;
            }
                
            $sales[] = (float) $monthlySale;
        }
        
        return [
            'orders' => $currentMonthOrders ?? 0,
            'sales' => $currentMonthSales ?? 0,
            'commission' => $currentMonthCommission ?? 0,
            'customers' => $currentMonthCustomers ?? 0,
            'months' => $months,
            'sales_chart' => $sales
        ];
    }

    /**
     * Assign customer to sales rep
     */
    public function assignCustomer(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_rep_id' => 'required|exists:sales_representatives,id'
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $customer->sales_rep_id = $request->sales_rep_id;
        $customer->save();

        flash(translate('Customer assigned successfully'))->success();
        return back();
    }

    /**
     * Bulk assign customers to sales rep
     */
    public function bulkAssignCustomers(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|array',
            'sales_rep_id' => 'required|exists:sales_representatives,id'
        ]);

        Customer::whereIn('id', $request->customer_ids)
            ->update(['sales_rep_id' => $request->sales_rep_id]);

        flash(translate('Customers assigned successfully'))->success();
        return back();
    }

    /**
     * Display product-based commissions for a sales representative
     */
    public function productCommissions($id)
    {
        $salesRep = SalesRepresentative::findOrFail($id);
        $commissions = SalesCommission::where('sales_rep_id', $id)
            ->where('commission_type', 'product_based')
            ->with(['order', 'product', 'orderDetail'])
            ->orderBy('commission_date', 'desc')
            ->paginate(15);

        $commissionService = new ProductCommissionService();
        $summary = $commissionService->getCommissionSummary($salesRep);

        return view('backend.sales_representatives.product_commissions', compact('salesRep', 'commissions', 'summary'));
    }

    /**
     * Approve a product-based commission
     */
    public function approveCommission(Request $request)
    {
        $commission = SalesCommission::findOrFail($request->commission_id);
        
        if ($commission->status !== 'pending') {
            flash(translate('Commission is not in pending status'))->error();
            return back();
        }

        $commission->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id()
        ]);

        flash(translate('Commission approved successfully'))->success();
        return back();
    }

    /**
     * Bulk approve commissions
     */
    public function bulkApproveCommissions(Request $request)
    {
        $request->validate([
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'exists:sales_commissions,id'
        ]);

        $updated = SalesCommission::whereIn('id', $request->commission_ids)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id()
            ]);

        flash(translate($updated . ' commissions approved successfully'))->success();
        return back();
    }

    /**
     * Product commission analytics dashboard
     */
    public function commissionAnalytics(Request $request)
    {
        $commissionService = new ProductCommissionService();
        
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        // Top performing products by commission
        $topProducts = $commissionService->getTopPerformingProducts(10, $startDate, $endDate);

        // Commission summary by sales reps
        $salesReps = SalesRepresentative::with('user')->get();
        $repSummaries = [];
        
        foreach ($salesReps as $rep) {
            $repSummaries[] = [
                'rep' => $rep,
                'summary' => $commissionService->getCommissionSummary($rep, $startDate, $endDate)
            ];
        }

        // Total commission stats
        $totalStats = [
            'total_commissions' => SalesCommission::productBased()
                ->whereBetween('commission_date', [$startDate, $endDate])
                ->count(),
            'total_amount' => SalesCommission::productBased()
                ->whereBetween('commission_date', [$startDate, $endDate])
                ->sum('commission_amount'),
            'pending_amount' => SalesCommission::productBased()
                ->whereBetween('commission_date', [$startDate, $endDate])
                ->where('status', 'pending')
                ->sum('commission_amount'),
            'approved_amount' => SalesCommission::productBased()
                ->whereBetween('commission_date', [$startDate, $endDate])
                ->where('status', 'approved')
                ->sum('commission_amount')
        ];

        return view('backend.sales_representatives.commission_analytics', compact(
            'topProducts', 'repSummaries', 'totalStats', 'startDate', 'endDate'
        ));
    }

    /**
     * Mobile dashboard for sales representatives
     */
    public function mobileDashboard()
    {
        // Get current sales rep
        $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
        if (!$salesRep) {
            abort(403, 'Unauthorized access');
        }

        $today = Carbon::today();
        
        // Today's stats
        $todayVisits = \App\Models\StoreVisit::where('sales_rep_id', $salesRep->id)
            ->whereDate('visit_date', $today)
            ->count();

        $todayOrders = \App\Models\StoreOrder::where('sales_rep_id', $salesRep->id)
            ->whereDate('created_at', $today)
            ->count();

        $todaySales = \App\Models\StoreOrder::where('sales_rep_id', $salesRep->id)
            ->whereDate('created_at', $today)
            ->sum('grand_total');

        $pendingOrders = \App\Models\StoreOrder::where('sales_rep_id', $salesRep->id)
            ->where('order_status', 'pending')
            ->count();

        // Today's schedule
        $todaySchedule = \App\Models\StoreVisit::with(['retailStore'])
            ->where('sales_rep_id', $salesRep->id)
            ->whereDate('visit_date', $today)
            ->orderBy('scheduled_time')
            ->get()
            ->map(function ($visit) {
                $visit->status_color = $this->getVisitStatusColor($visit->visit_status);
                $visit->status_icon = $this->getVisitStatusIcon($visit->visit_status);
                return $visit;
            });

        // Recent orders
        $recentOrders = \App\Models\StoreOrder::with(['retailStore'])
            ->where('sales_rep_id', $salesRep->id)
            ->latest()
            ->limit(5)
            ->get();

        // My stores
        $myStores = \App\Models\RetailStore::where('sales_rep_id', $salesRep->id)
            ->orderBy('name')
            ->get();

        // Weekly performance data for chart
        $weeklyPerformance = $this->getWeeklyPerformanceData($salesRep->id);

        return view('backend.sales_representatives.mobile_dashboard', compact(
            'todayVisits', 'todayOrders', 'todaySales', 'pendingOrders',
            'todaySchedule', 'recentOrders', 'myStores', 'salesRep', 'weeklyPerformance'
        ));
    }

    /**
     * Helper methods for mobile dashboard
     */
    private function getVisitStatusColor($status)
    {
        $colors = [
            'pending' => 'warning',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger'
        ];
        return $colors[$status] ?? 'secondary';
    }

    private function getVisitStatusIcon($status)
    {
        $icons = [
            'pending' => 'clock',
            'in_progress' => 'play-circle',
            'completed' => 'check-circle',
            'cancelled' => 'times-circle'
        ];
        return $icons[$status] ?? 'question-circle';
    }

    /**
     * API: Update GPS location for sales rep
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
        if (!$salesRep) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $salesRep->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'last_location_update' => now(),
            'gps_enabled' => true
        ]);

        return response()->json(['success' => true, 'message' => 'Location updated successfully']);
    }

    /**
     * Start a store visit (check-in)
     */
    public function startVisit(Request $request, $visitId)
    {
        $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
        if (!$salesRep) {
            abort(403, 'Unauthorized access');
        }

        $visit = \App\Models\StoreVisit::where('id', $visitId)
            ->where('sales_rep_id', $salesRep->id)
            ->firstOrFail();

        $visit->update([
            'visit_status' => 'in_progress',
            'check_in_time' => now()
        ]);

        // Update location if provided
        if ($request->has('latitude') && $request->has('longitude')) {
            $salesRep->update([
                'current_latitude' => $request->latitude,
                'current_longitude' => $request->longitude,
                'last_location_update' => now()
            ]);
        }

        return redirect()->back()->with('success', 'Visit started successfully');
    }

    /**
     * Complete a store visit (check-out)
     */
    public function completeVisit(Request $request, $visitId)
    {
        $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
        if (!$salesRep) {
            abort(403, 'Unauthorized access');
        }

        $visit = \App\Models\StoreVisit::where('id', $visitId)
            ->where('sales_rep_id', $salesRep->id)
            ->firstOrFail();

        $request->validate([
            'notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'order_amount' => 'nullable|numeric|min:0'
        ]);

        // Handle photo uploads
        $photoUrls = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('visit_photos', 'public');
                $photoUrls[] = $path;
            }
        }

        $visit->update([
            'visit_status' => 'completed',
            'check_out_time' => now(),
            'notes' => $request->notes,
            'photos' => $photoUrls,
            'order_amount' => $request->order_amount ?? 0
        ]);

        // Update sales rep stats
        $salesRep->increment('successful_visits');

        return redirect()->back()->with('success', 'Visit completed successfully');
    }

    /**
     * Get nearby stores for sales rep
     */
    public function getNearbyStores(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50' // km
        ]);

        $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
        if (!$salesRep) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $radius = $request->get('radius', 10); // Default 10km

        $nearbyStores = \App\Models\RetailStore::select([
            'id', 'name', 'address', 'phone', 'latitude', 'longitude',
            \DB::raw("(6371 * acos(cos(radians({$request->latitude})) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians({$request->longitude})) 
                + sin(radians({$request->latitude})) 
                * sin(radians(latitude)))) AS distance")
        ])
        ->where('sales_rep_id', $salesRep->id)
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->having('distance', '<', $radius)
        ->orderBy('distance')
        ->limit(20)
        ->get();

        return response()->json(['stores' => $nearbyStores]);
    }

    /**
     * Performance Analytics for Sales Rep
     */
    public function analytics(Request $request)
    {
        // Check if user is admin or sales rep
        $user = auth()->user();
        
        // If admin, get the first sales rep for demo or handle differently
        if ($user->user_type == 'admin' || $user->user_type == 'staff') {
            // Admin accessing analytics - get specific sales rep ID from request or show overview
            $salesRepId = $request->get('sales_rep_id');
            
            if ($salesRepId) {
                $salesRep = SalesRepresentative::find($salesRepId);
                if (!$salesRep) {
                    // Invalid sales rep ID provided, show demo data instead
                    return $this->showEmptyAnalytics($request);
                }
            } else {
                // Show list of sales reps to choose from or get first one for demo
                $salesRep = SalesRepresentative::first();
                if (!$salesRep) {
                    // No sales reps exist, create dummy data
                    return $this->showEmptyAnalytics($request);
                }
            }
        } else {
            // Regular sales rep user
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if (!$salesRep) {
                abort(403, 'Unauthorized access - No sales representative profile found');
            }
        }

        $period = $request->get('period', 'monthly'); // daily, weekly, monthly, yearly
        $startDate = $this->getAnalyticsStartDate($period);
        $endDate = now();

        // Performance metrics with proper null handling
        $metrics = [
            'visits' => \App\Models\StoreVisit::where('sales_rep_id', $salesRep->id)
                ->whereBetween('visit_date', [$startDate, $endDate])
                ->count() ?? 0,
            'completed_visits' => \App\Models\StoreVisit::where('sales_rep_id', $salesRep->id)
                ->where('visit_status', 'completed')
                ->whereBetween('visit_date', [$startDate, $endDate])
                ->count() ?? 0,
            'orders' => \App\Models\StoreOrder::where('sales_rep_id', $salesRep->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count() ?? 0,
            'sales_amount' => (float) (\App\Models\StoreOrder::where('sales_rep_id', $salesRep->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('grand_total') ?? 0),
            'commission_earned' => (float) (\App\Models\SalesCommission::where('sales_rep_id', $salesRep->id)
                ->where('status', 'approved')
                ->whereBetween('commission_date', [$startDate, $endDate])
                ->sum('commission_amount') ?? 0)
        ];

        // Visit success rate with safe calculation
        $metrics['success_rate'] = ($metrics['visits'] > 0 && $metrics['completed_visits'] >= 0) 
            ? round(($metrics['completed_visits'] / $metrics['visits']) * 100, 2) 
            : 0;

        // Average order value with safe calculation
        $metrics['avg_order_value'] = ($metrics['orders'] > 0 && $metrics['sales_amount'] > 0) 
            ? round($metrics['sales_amount'] / $metrics['orders'], 2) 
            : 0;
            
        // Conversion rate (orders per visit)
        $metrics['conversion_rate'] = ($metrics['visits'] > 0 && $metrics['orders'] >= 0) 
            ? round(($metrics['orders'] / $metrics['visits']) * 100, 2) 
            : 0;
            
        // Sales per visit with safe calculation  
        $metrics['sales_per_visit'] = ($metrics['visits'] > 0 && $metrics['sales_amount'] > 0) 
            ? round($metrics['sales_amount'] / $metrics['visits'], 2) 
            : 0;

        // Daily/Weekly trends
        $trends = \App\Models\StoreVisit::selectRaw('DATE(visit_date) as date, COUNT(*) as visits')
            ->where('sales_rep_id', $salesRep->id)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('backend.sales_representatives.analytics', compact('metrics', 'trends', 'period', 'salesRep'));
    }

    /**
     * Update FCM token for push notifications
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);

        $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
        if (!$salesRep) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $salesRep->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['success' => true]);
    }

    /**
     * Bulk upload visit photos
     */
    public function uploadVisitPhotos(Request $request, $visitId)
    {
        $request->validate([
            'photos' => 'required|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg|max:5120' // 5MB max per image
        ]);

        $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
        if (!$salesRep) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $visit = \App\Models\StoreVisit::where('id', $visitId)
            ->where('sales_rep_id', $salesRep->id)
            ->firstOrFail();

        $existingPhotos = $visit->photos ?? [];
        $newPhotos = [];

        foreach ($request->file('photos') as $photo) {
            $filename = time() . '_' . uniqid() . '.' . $photo->extension();
            $path = $photo->storeAs('visit_photos', $filename, 'public');
            $newPhotos[] = $path;
        }

        $allPhotos = array_merge($existingPhotos, $newPhotos);
        $visit->update(['photos' => $allPhotos]);

        return response()->json([
            'success' => true,
            'photos' => $newPhotos,
            'total_photos' => count($allPhotos)
        ]);
    }

    /**
     * Get weekly performance data for charts
     */
    private function getWeeklyPerformanceData($salesRepId)
    {
        $weeklyData = [];
        $startOfWeek = Carbon::now()->startOfWeek();
        
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            
            $visits = \App\Models\StoreVisit::where('sales_rep_id', $salesRepId)
                ->whereDate('visit_date', $date)
                ->count();
                
            $orders = \App\Models\StoreOrder::where('sales_rep_id', $salesRepId)
                ->whereDate('created_at', $date)
                ->count();
                
            $sales = \App\Models\StoreOrder::where('sales_rep_id', $salesRepId)
                ->whereDate('created_at', $date)
                ->sum('grand_total');
            
            $weeklyData[] = [
                'date' => $date->format('M j'),
                'day' => $date->format('D'),
                'visits' => $visits,
                'orders' => $orders,
                'sales' => (float) $sales
            ];
        }
        
        return $weeklyData;
    }

    /**
     * Helper method to get start date for analytics
     */
    private function getAnalyticsStartDate($period)
    {
        switch ($period) {
            case 'daily':
                return now()->startOfDay();
            case 'weekly':
                return now()->startOfWeek();
            case 'monthly':
                return now()->startOfMonth();
            case 'yearly':
                return now()->startOfYear();
            default:
                return now()->startOfMonth();
        }
    }
    
    private function showEmptyAnalytics($request)
    {
        $period = $request->get('period', 'monthly');
        
        // Create dummy sales rep object for the view
        $salesRep = (object) [
            'id' => 0,
            'name' => 'Demo User',
            'sales_target_monthly' => 0,
            'commission_rate' => 0
        ];
        
        // Empty metrics
        $metrics = [
            'visits' => 0,
            'completed_visits' => 0,
            'orders' => 0,
            'sales_amount' => 0,
            'commission_earned' => 0,
            'success_rate' => 0,
            'avg_order_value' => 0,
            'conversion_rate' => 0,
            'sales_per_visit' => 0
        ];
        
        // Empty trends
        $trends = [];
        
        return view('backend.sales_representatives.analytics', compact('metrics', 'trends', 'period', 'salesRep'));
    }
}
