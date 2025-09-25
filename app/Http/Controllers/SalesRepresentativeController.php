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
}
