<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesTarget;
use App\Models\SalesRepresentative;
use Carbon\Carbon;

class SalesTargetController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check - ADMIN/STAFF ONLY
        $this->middleware(['permission:manage_sales_targets'])->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of sales targets
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $sales_rep_id = null;
        $target_period = null;
        $status = null;

        $targets = SalesTarget::with(['salesRepresentative.user'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $sort_search = $request->search;
            $targets = $targets->whereHas('salesRepresentative.user', function($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%');
            });
        }

        if ($request->has('sales_rep_id') && $request->sales_rep_id != '') {
            $sales_rep_id = $request->sales_rep_id;
            $targets = $targets->where('sales_rep_id', $sales_rep_id);
        }

        if ($request->has('target_period') && $request->target_period != '') {
            $target_period = $request->target_period;
            $targets = $targets->where('target_period', $target_period);
        }

        if ($request->has('status') && $request->status != '') {
            $status = $request->status;
            $targets = $targets->where('status', $status);
        }

        $targets = $targets->paginate(15);
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();

        return view('backend.sales_targets.index', compact('targets', 'sort_search', 'sales_rep_id', 'target_period', 'status', 'salesReps'));
    }

    /**
     * Show the form for creating a new target
     */
    public function create()
    {
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();
        return view('backend.sales_targets.create', compact('salesReps'));
    }

    /**
     * Store a newly created target
     */
    public function store(Request $request)
    {
        $request->validate([
            'sales_rep_id' => 'required|exists:sales_representatives,id',
            'target_period' => 'required|in:monthly,quarterly,yearly,custom',
            'target_type' => 'required|in:revenue,orders,customers,products',
            'target_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        $target = new SalesTarget();
        $target->sales_rep_id = $request->sales_rep_id;
        $target->target_period = $request->target_period;
        $target->target_type = $request->target_type;
        $target->target_value = $request->target_value;
        $target->achieved_value = 0;
        $target->start_date = $request->start_date;
        $target->end_date = $request->end_date;
        $target->status = 'active';
        $target->notes = $request->notes;
        $target->save();

        flash(translate('Sales Target has been created successfully'))->success();
        return redirect()->route('sales_targets.index');
    }

    /**
     * Display the specified target
     */
    public function show($id)
    {
        $target = SalesTarget::with(['salesRepresentative.user'])
            ->findOrFail($id);
        
        return view('backend.sales_targets.show', compact('target'));
    }

    /**
     * Show the form for editing the specified target
     */
    public function edit($id)
    {
        $target = SalesTarget::findOrFail($id);
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();
        return view('backend.sales_targets.edit', compact('target', 'salesReps'));
    }

    /**
     * Update the specified target
     */
    public function update(Request $request, $id)
    {
        $target = SalesTarget::findOrFail($id);
        
        $request->validate([
            'sales_rep_id' => 'required|exists:sales_representatives,id',
            'target_period' => 'required|in:monthly,quarterly,yearly,custom',
            'target_type' => 'required|in:revenue,orders,customers,products',
            'target_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        $target->sales_rep_id = $request->sales_rep_id;
        $target->target_period = $request->target_period;
        $target->target_type = $request->target_type;
        $target->target_value = $request->target_value;
        $target->start_date = $request->start_date;
        $target->end_date = $request->end_date;
        $target->status = $request->status ?? $target->status;
        $target->notes = $request->notes;
        $target->save();

        flash(translate('Sales Target has been updated successfully'))->success();
        return redirect()->route('sales_targets.index');
    }

    /**
     * Remove the specified target from storage
     */
    public function destroy($id)
    {
        $target = SalesTarget::findOrFail($id);
        $target->delete();

        flash(translate('Sales Target has been deleted successfully'))->success();
        return redirect()->route('sales_targets.index');
    }

    /**
     * Update target achievement
     */
    public function updateAchievement()
    {
        $activeTargets = SalesTarget::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get();

        foreach ($activeTargets as $target) {
            $salesRep = $target->salesRepresentative;
            
            switch ($target->target_type) {
                case 'revenue':
                    $achieved = $salesRep->orders()
                        ->whereBetween('created_at', [$target->start_date, $target->end_date])
                        ->where('delivery_status', 'delivered')
                        ->sum('grand_total');
                    break;
                    
                case 'orders':
                    $achieved = $salesRep->orders()
                        ->whereBetween('created_at', [$target->start_date, $target->end_date])
                        ->where('delivery_status', 'delivered')
                        ->count();
                    break;
                    
                case 'customers':
                    $achieved = $salesRep->customers()
                        ->whereBetween('created_at', [$target->start_date, $target->end_date])
                        ->count();
                    break;
                    
                case 'products':
                    $achieved = $salesRep->orders()
                        ->whereBetween('created_at', [$target->start_date, $target->end_date])
                        ->where('delivery_status', 'delivered')
                        ->withCount('orderDetails')
                        ->sum('order_details_count');
                    break;
                    
                default:
                    $achieved = 0;
            }
            
            $target->achieved_value = $achieved;
            
            // Update status based on achievement
            if ($achieved >= $target->target_value) {
                $target->status = 'achieved';
            } elseif ($target->end_date < now()) {
                $target->status = 'failed';
            }
            
            $target->save();
        }

        flash(translate('Target achievements updated successfully'))->success();
        return back();
    }

    /**
     * Bulk create targets for multiple sales reps
     */
    public function bulkCreate(Request $request)
    {
        $request->validate([
            'sales_rep_ids' => 'required|array',
            'target_period' => 'required|in:monthly,quarterly,yearly,custom',
            'target_type' => 'required|in:revenue,orders,customers,products',
            'target_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        $created = 0;
        foreach ($request->sales_rep_ids as $salesRepId) {
            $target = new SalesTarget();
            $target->sales_rep_id = $salesRepId;
            $target->target_period = $request->target_period;
            $target->target_type = $request->target_type;
            $target->target_value = $request->target_value;
            $target->achieved_value = 0;
            $target->start_date = $request->start_date;
            $target->end_date = $request->end_date;
            $target->status = 'active';
            $target->notes = $request->notes;
            $target->save();
            $created++;
        }

        flash(translate($created . ' targets created successfully'))->success();
        return back();
    }
}
