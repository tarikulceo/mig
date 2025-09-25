<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesActivity;
use App\Models\SalesRepresentative;
use App\Models\Customer;
use Carbon\Carbon;

class SalesActivityController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check - Temporarily relaxed for Sales Rep access
        // $this->middleware(['permission:manage_sales_activities'])->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of sales activities
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $sales_rep_id = null;
        $activity_type = null;
        $status = null;

        $activities = SalesActivity::with(['salesRepresentative.user', 'customer.user'])
            ->orderBy('activity_date', 'desc');

        if ($request->has('search')) {
            $sort_search = $request->search;
            $activities = $activities->where('subject', 'like', '%' . $sort_search . '%')
                ->orWhereHas('customer.user', function($query) use ($sort_search) {
                    $query->where('name', 'like', '%' . $sort_search . '%');
                });
        }

        if ($request->has('sales_rep_id') && $request->sales_rep_id != '') {
            $sales_rep_id = $request->sales_rep_id;
            $activities = $activities->where('sales_rep_id', $sales_rep_id);
        }

        if ($request->has('activity_type') && $request->activity_type != '') {
            $activity_type = $request->activity_type;
            $activities = $activities->where('activity_type', $activity_type);
        }

        if ($request->has('status') && $request->status != '') {
            $status = $request->status;
            $activities = $activities->where('status', $status);
        }

        $activities = $activities->paginate(15);
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();

        return view('backend.sales_activities.index', compact('activities', 'sort_search', 'sales_rep_id', 'activity_type', 'status', 'salesReps'));
    }

    /**
     * Show the form for creating a new activity
     */
    public function create()
    {
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();
        $customers = Customer::with('user')->latest()->take(100)->get();
        return view('backend.sales_activities.create', compact('salesReps', 'customers'));
    }

    /**
     * Store a newly created activity
     */
    public function store(Request $request)
    {
        $request->validate([
            'sales_rep_id' => 'required|exists:sales_representatives,id',
            'customer_id' => 'required|exists:customers,id',
            'activity_type' => 'required|in:call,email,meeting,demo,follow_up,proposal,other',
            'subject' => 'required|string|max:255',
            'activity_date' => 'required|date',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent'
        ]);

        $activity = new SalesActivity();
        $activity->sales_rep_id = $request->sales_rep_id;
        $activity->customer_id = $request->customer_id;
        $activity->activity_type = $request->activity_type;
        $activity->subject = $request->subject;
        $activity->description = $request->description;
        $activity->activity_date = $request->activity_date;
        $activity->follow_up_date = $request->follow_up_date;
        $activity->status = $request->status;
        $activity->priority = $request->priority;
        $activity->outcome = $request->outcome;
        $activity->notes = $request->notes;
        $activity->save();

        flash(translate('Sales Activity has been created successfully'))->success();
        return redirect()->route('sales_activities.index');
    }

    /**
     * Display the specified activity
     */
    public function show($id)
    {
        $activity = SalesActivity::with(['salesRepresentative.user', 'customer.user'])->findOrFail($id);
        return view('backend.sales_activities.show', compact('activity'));
    }

    /**
     * Show the form for editing the specified activity
     */
    public function edit($id)
    {
        $activity = SalesActivity::findOrFail($id);
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();
        $customers = Customer::with('user')->get();
        return view('backend.sales_activities.edit', compact('activity', 'salesReps', 'customers'));
    }

    /**
     * Update the specified activity
     */
    public function update(Request $request, $id)
    {
        $activity = SalesActivity::findOrFail($id);
        
        $request->validate([
            'sales_rep_id' => 'required|exists:sales_representatives,id',
            'customer_id' => 'required|exists:customers,id',
            'activity_type' => 'required|in:call,email,meeting,demo,follow_up,proposal,other',
            'subject' => 'required|string|max:255',
            'activity_date' => 'required|date',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent'
        ]);

        $activity->sales_rep_id = $request->sales_rep_id;
        $activity->customer_id = $request->customer_id;
        $activity->activity_type = $request->activity_type;
        $activity->subject = $request->subject;
        $activity->description = $request->description;
        $activity->activity_date = $request->activity_date;
        $activity->follow_up_date = $request->follow_up_date;
        $activity->status = $request->status;
        $activity->priority = $request->priority;
        $activity->outcome = $request->outcome;
        $activity->notes = $request->notes;
        $activity->save();

        flash(translate('Sales Activity has been updated successfully'))->success();
        return redirect()->route('sales_activities.index');
    }

    /**
     * Remove the specified activity from storage
     */
    public function destroy($id)
    {
        $activity = SalesActivity::findOrFail($id);
        $activity->delete();

        flash(translate('Sales Activity has been deleted successfully'))->success();
        return redirect()->route('sales_activities.index');
    }

    /**
     * Get calendar view of activities
     */
    public function calendar(Request $request)
    {
        $salesRepId = $request->sales_rep_id;
        $activities = SalesActivity::with(['salesRepresentative.user', 'customer.user']);
        
        if ($salesRepId) {
            $activities = $activities->where('sales_rep_id', $salesRepId);
        }
        
        $activities = $activities->get();
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();

        return view('backend.sales_activities.calendar', compact('activities', 'salesReps', 'salesRepId'));
    }

    /**
     * Get activities for calendar
     */
    public function getCalendarActivities(Request $request)
    {
        $activities = SalesActivity::with(['salesRepresentative.user', 'customer.user']);
        
        if ($request->sales_rep_id) {
            $activities = $activities->where('sales_rep_id', $request->sales_rep_id);
        }
        
        if ($request->start && $request->end) {
            $activities = $activities->whereBetween('activity_date', [$request->start, $request->end]);
        }
        
        $activities = $activities->get();
        
        $events = [];
        foreach ($activities as $activity) {
            $events[] = [
                'id' => $activity->id,
                'title' => $activity->subject,
                'start' => $activity->activity_date->toISOString(),
                'end' => $activity->activity_date->addHour()->toISOString(),
                'backgroundColor' => $this->getActivityColor($activity->priority),
                'borderColor' => $this->getActivityColor($activity->priority),
                'extendedProps' => [
                    'activity_type' => $activity->activity_type_name,
                    'customer' => $activity->customer->user->name ?? '',
                    'sales_rep' => $activity->salesRepresentative->user->name ?? '',
                    'status' => $activity->status_name,
                    'priority' => $activity->priority_name
                ]
            ];
        }
        
        return response()->json($events);
    }

    /**
     * Get activity color based on priority
     */
    private function getActivityColor($priority)
    {
        switch ($priority) {
            case 'urgent':
                return '#dc3545';
            case 'high':
                return '#fd7e14';
            case 'medium':
                return '#ffc107';
            case 'low':
                return '#28a745';
            default:
                return '#6c757d';
        }
    }

    /**
     * Get overdue activities
     */
    public function overdue()
    {
        $activities = SalesActivity::with(['salesRepresentative.user', 'customer.user'])
            ->where('activity_date', '<', now())
            ->where('status', '!=', 'completed')
            ->orderBy('activity_date', 'asc')
            ->paginate(15);

        return view('backend.sales_activities.overdue', compact('activities'));
    }

    /**
     * Get upcoming activities
     */
    public function upcoming()
    {
        $activities = SalesActivity::with(['salesRepresentative.user', 'customer.user'])
            ->where('activity_date', '>=', now())
            ->where('activity_date', '<=', now()->addDays(7))
            ->where('status', '!=', 'completed')
            ->orderBy('activity_date', 'asc')
            ->paginate(15);

        return view('backend.sales_activities.upcoming', compact('activities'));
    }
}
