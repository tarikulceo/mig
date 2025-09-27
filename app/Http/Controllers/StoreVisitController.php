<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StoreVisit;
use App\Models\RetailStore;
use App\Models\SalesRepresentative;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StoreVisitController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display a listing of store visits
     */
    public function index(Request $request)
    {
        $query = StoreVisit::with(['retailStore', 'salesRepresentative.user']);

        // If user is sales rep, only show their visits
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep) {
                $query->where('sales_rep_id', $salesRep->id);
            }
        }

        $visits = $query->latest('visit_date')->paginate(15);

        return view('backend.store_visits.index', compact('visits'));
    }

    /**
     * Show the form for creating a new store visit
     */
    public function create(Request $request)
    {
        // Get stores based on user type
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            $stores = RetailStore::where('sales_rep_id', $salesRep->id ?? 0)->get();
            $selectedStore = $request->get('store_id') ? RetailStore::find($request->get('store_id')) : null;
        } else {
            $stores = RetailStore::with('salesRepresentative.user')->get();
            $selectedStore = $request->get('store_id') ? RetailStore::find($request->get('store_id')) : null;
        }

        return view('backend.store_visits.create', compact('stores', 'selectedStore'));
    }

    /**
     * Store a newly created store visit
     */
    public function store(Request $request)
    {
        $request->validate([
            'retail_store_id' => 'required|exists:retail_stores,id',
            'visit_date' => 'required|date',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
            'visit_status' => 'required|in:scheduled,completed,cancelled',
            'order_amount' => 'nullable|numeric|min:0'
        ]);

        // Get current sales rep
        $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
        if (!$salesRep && auth()->user()->user_type === 'sales_rep') {
            flash(translate('Sales Representative profile not found'))->error();
            return back();
        }

        DB::beginTransaction();
        try {
            $visit = new StoreVisit($request->all());
            $visit->sales_rep_id = $salesRep ? $salesRep->id : $request->sales_rep_id;
            $visit->save();

            // Update store's last visit date
            if ($request->visit_status === 'completed') {
                $store = RetailStore::find($request->retail_store_id);
                $store->last_visit = $visit->visit_date;
                $store->save();
            }

            DB::commit();
            flash(translate('Store visit created successfully'))->success();
            return redirect()->route('store_visits.index');

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong: ') . $e->getMessage())->error();
            return back()->withInput();
        }
    }

    /**
     * Display the specified store visit
     */
    public function show($id)
    {
        $visit = StoreVisit::with(['retailStore', 'salesRepresentative.user'])->findOrFail($id);

        // Check permission for sales rep
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $visit->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to this visit.');
            }
        }

        return view('backend.store_visits.show', compact('visit'));
    }

    /**
     * Show the form for editing the specified store visit
     */
    public function edit($id)
    {
        $visit = StoreVisit::findOrFail($id);

        // Check permission for sales rep
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $visit->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to edit this visit.');
            }
        }

        // Get stores based on user type
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            $stores = RetailStore::where('sales_rep_id', $salesRep->id ?? 0)->get();
        } else {
            $stores = RetailStore::with('salesRepresentative.user')->get();
        }

        return view('backend.store_visits.edit', compact('visit', 'stores'));
    }

    /**
     * Update the specified store visit
     */
    public function update(Request $request, $id)
    {
        $visit = StoreVisit::findOrFail($id);

        // Check permission for sales rep
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $visit->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to update this visit.');
            }
        }

        $request->validate([
            'retail_store_id' => 'required|exists:retail_stores,id',
            'visit_date' => 'required|date',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
            'visit_status' => 'required|in:scheduled,completed,cancelled',
            'order_amount' => 'nullable|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            $visit->update($request->all());

            // Update store's last visit date if completed
            if ($request->visit_status === 'completed') {
                $store = RetailStore::find($request->retail_store_id);
                $store->last_visit = $visit->visit_date;
                $store->save();
            }

            DB::commit();
            flash(translate('Store visit updated successfully'))->success();
            return redirect()->route('store_visits.show', $visit->id);

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong: ') . $e->getMessage())->error();
            return back()->withInput();
        }
    }

    /**
     * Remove the specified store visit
     */
    public function destroy($id)
    {
        $visit = StoreVisit::findOrFail($id);

        // Check permission for sales rep
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $visit->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to delete this visit.');
            }
        }

        try {
            $visit->delete();
            flash(translate('Store visit deleted successfully'))->success();
            return redirect()->route('store_visits.index');
        } catch (\Exception $e) {
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

    /**
     * Get visits by store for AJAX requests
     */
    public function getVisitsByStore($storeId)
    {
        try {
            $query = StoreVisit::where('retail_store_id', $storeId)
                ->with(['salesRepresentative.user'])
                ->latest('visit_date')
                ->limit(10);

            // If user is sales rep, only show their visits
            if (auth()->user()->user_type === 'sales_rep') {
                $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
                if ($salesRep) {
                    $query->where('sales_rep_id', $salesRep->id);
                }
            }

            $visits = $query->get()->map(function ($visit) {
                return [
                    'id' => $visit->id,
                    'visit_date' => $visit->visit_date->format('Y-m-d'),
                    'purpose' => $visit->purpose,
                    'status' => $visit->status,
                    'sales_rep' => $visit->salesRepresentative ? $visit->salesRepresentative->user->name : 'N/A'
                ];
            });

            return response()->json([
                'success' => true,
                'visits' => $visits
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading visits: ' . $e->getMessage()
            ], 500);
        }
    }
}
