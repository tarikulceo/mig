<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RetailStore;
use App\Models\SalesRepresentative;
use App\Models\SalesTerritory;
use App\Models\StoreVisit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RetailStoreController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display a listing of retail stores
     */
    public function index(Request $request)
    {
        $query = RetailStore::with(['salesRepresentative.user', 'territory']);

        // If user is sales rep, only show their stores
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
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('store_code', 'LIKE', "%{$search}%")
                  ->orWhere('owner_name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('territory_id') && $request->territory_id) {
            $query->where('territory_id', $request->territory_id);
        }

        if ($request->has('sales_rep_id') && $request->sales_rep_id) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        $stores = $query->latest()->paginate(15);

        // Get filter options
        $territories = SalesTerritory::where('is_active', 1)->get();
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();

        return view('backend.retail_stores.index', compact('stores', 'territories', 'salesReps'));
    }

    /**
     * Show the form for creating a new retail store
     */
    public function create()
    {
        $territories = SalesTerritory::where('is_active', 1)->get();
        
        // If user is sales rep, auto-select them
        if (auth()->user()->user_type === 'sales_rep') {
            $salesReps = SalesRepresentative::with('user')
                ->where('user_id', auth()->id())
                ->where('status', 1)
                ->get();
            $selectedSalesRep = $salesReps->first();
        } else {
            $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();
            $selectedSalesRep = null;
        }

        // Debug: Check if we have sales reps
        if ($salesReps->isEmpty()) {
            flash(translate('No active sales representatives found. Please create a sales representative first.'))->warning();
        }

        return view('backend.retail_stores.create', compact('territories', 'salesReps', 'selectedSalesRep'));
    }

    /**
     * Test form for debugging
     */
    public function test()
    {
        $territories = SalesTerritory::where('is_active', 1)->get();
        $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();
        
        return view('backend.retail_stores.test', compact('territories', 'salesReps'));
    }

    /**
     * Store a newly created retail store
     */
    public function store(Request $request)
    {
        // Debug: Log what's being submitted
        \Log::info('Store submission data:', $request->all());

        $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'store_type' => 'required|string|in:retail,wholesale,dealer',
            'store_size' => 'nullable|numeric|min:0',
            'staff_count' => 'required|integer|min:1',
            'sales_rep_id' => 'required|exists:sales_representatives,id',
            'territory_id' => 'nullable|exists:sales_territories,id',
            'monthly_target' => 'nullable|numeric|min:0',
            'yearly_target' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:active,inactive,pending',
            'established_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Generate store code
            $storeCode = RetailStore::generateStoreCode($request->sales_rep_id);

            $store = new RetailStore($request->except(['image', 'images']));
            $store->store_code = $storeCode;
            $store->created_by = auth()->id();

            // Handle main image
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('retail_stores', 'public');
                $store->image = $imagePath;
            }

            // Handle multiple images
            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $imagePath = $imageFile->store('retail_stores', 'public');
                    $images[] = $imagePath;
                }
                $store->images = $images;
            }

            $store->save();

            DB::commit();
            flash(translate('Retail store created successfully'))->success();
            return redirect()->route('retail_stores.index');

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong: ') . $e->getMessage())->error();
            return back()->withInput();
        }
    }

    /**
     * Display the specified retail store
     */
    public function show($id)
    {
        $store = RetailStore::with([
            'salesRepresentative.user', 
            'territory', 
            'createdBy',
            'visits.salesRepresentative.user'
        ])->findOrFail($id);

        // Check permission for sales rep
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $store->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to this store.');
            }
        }

        // Get recent visits
        $recentVisits = $store->visits()
            ->with('salesRepresentative.user')
            ->latest()
            ->take(10)
            ->get();

        // Get monthly stats
        $monthlyStats = $this->getMonthlyStats($store);

        return view('backend.retail_stores.show', compact('store', 'recentVisits', 'monthlyStats'));
    }

    /**
     * Show the form for editing the specified retail store
     */
    public function edit($id)
    {
        $store = RetailStore::findOrFail($id);

        // Check permission for sales rep
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $store->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to edit this store.');
            }
        }

        $territories = SalesTerritory::where('is_active', 1)->get();
        
        // If user is sales rep, only show their own data
        if (auth()->user()->user_type === 'sales_rep') {
            $salesReps = SalesRepresentative::with('user')
                ->where('user_id', auth()->id())
                ->where('status', 1)
                ->get();
        } else {
            $salesReps = SalesRepresentative::with('user')->where('status', 1)->get();
        }

        return view('backend.retail_stores.edit', compact('store', 'territories', 'salesReps'));
    }

    /**
     * Update the specified retail store
     */
    public function update(Request $request, $id)
    {
        $store = RetailStore::findOrFail($id);

        // Check permission for sales rep
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $store->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to update this store.');
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'store_type' => 'required|string|in:retail,wholesale,dealer',
            'store_size' => 'nullable|numeric|min:0',
            'staff_count' => 'required|integer|min:1',
            'sales_rep_id' => 'required|exists:sales_representatives,id',
            'territory_id' => 'nullable|exists:sales_territories,id',
            'monthly_target' => 'nullable|numeric|min:0',
            'yearly_target' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:active,inactive,pending',
            'established_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $store->fill($request->except(['image', 'images']));

            // Handle main image
            if ($request->hasFile('image')) {
                // Delete old image
                if ($store->image) {
                    Storage::disk('public')->delete($store->image);
                }
                $imagePath = $request->file('image')->store('retail_stores', 'public');
                $store->image = $imagePath;
            }

            // Handle multiple images
            if ($request->hasFile('images')) {
                // Delete old images
                if ($store->images) {
                    foreach ($store->images as $oldImage) {
                        Storage::disk('public')->delete($oldImage);
                    }
                }
                
                $images = [];
                foreach ($request->file('images') as $imageFile) {
                    $imagePath = $imageFile->store('retail_stores', 'public');
                    $images[] = $imagePath;
                }
                $store->images = $images;
            }

            $store->save();

            DB::commit();
            flash(translate('Retail store updated successfully'))->success();
            return redirect()->route('retail_stores.show', $store->id);

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong: ') . $e->getMessage())->error();
            return back()->withInput();
        }
    }

    /**
     * Remove the specified retail store
     */
    public function destroy($id)
    {
        $store = RetailStore::findOrFail($id);

        // Check permission for sales rep
        if (auth()->user()->user_type === 'sales_rep') {
            $salesRep = SalesRepresentative::where('user_id', auth()->id())->first();
            if ($salesRep && $store->sales_rep_id !== $salesRep->id) {
                abort(403, 'Unauthorized access to delete this store.');
            }
        }

        DB::beginTransaction();
        try {
            // Delete images
            if ($store->image) {
                Storage::disk('public')->delete($store->image);
            }
            if ($store->images) {
                foreach ($store->images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }

            $store->delete();

            DB::commit();
            flash(translate('Retail store deleted successfully'))->success();
            return redirect()->route('retail_stores.index');

        } catch (\Exception $e) {
            DB::rollback();
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

    /**
     * Get coordinates from address using Google Maps Geocoding API
     */
    public function getCoordinates(Request $request)
    {
        $address = $request->get('address');
        
        if (empty($address)) {
            return response()->json(['error' => 'Address is required'], 400);
        }

        // Try Google Maps first
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        
        if (!empty($apiKey)) {
            $result = $this->getCoordinatesFromGoogle($address, $apiKey);
            if ($result) {
                return response()->json($result);
            }
        }

        // Fallback to free geocoding service
        $result = $this->getCoordinatesFromFallback($address);
        if ($result) {
            return response()->json($result);
        }

        return response()->json(['error' => 'Unable to find coordinates for the given address. Please check the address or try again later.'], 404);
    }

    /**
     * Get coordinates using Google Maps Geocoding API
     */
    private function getCoordinatesFromGoogle($address, $apiKey)
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $apiKey;
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                curl_close($ch);
                return false;
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return false;
            }
            
            $data = json_decode($response, true);

            if ($data && $data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                return [
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng'],
                    'formatted_address' => $data['results'][0]['formatted_address']
                ];
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fallback geocoding using free service
     */
    private function getCoordinatesFromFallback($address)
    {
        try {
            // Using Nominatim (OpenStreetMap) as fallback
            $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address) . "&limit=1";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Laravel MGS Store Management System');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                curl_close($ch);
                return false;
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return false;
            }
            
            $data = json_decode($response, true);

            if ($data && !empty($data)) {
                $result = $data[0];
                return [
                    'latitude' => floatval($result['lat']),
                    'longitude' => floatval($result['lon']),
                    'formatted_address' => $result['display_name']
                ];
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get monthly statistics for a store
     */
    private function getMonthlyStats($store)
    {
        $currentMonth = Carbon::now();
        
        // Get store orders with due amounts
        $totalDueAmount = \App\Models\StoreOrder::where('retail_store_id', $store->id)
            ->where('due_amount', '>', 0)
            ->sum('due_amount');

        $ordersWithDue = \App\Models\StoreOrder::where('retail_store_id', $store->id)
            ->where('due_amount', '>', 0)
            ->count();

        $totalOrders = \App\Models\StoreOrder::where('retail_store_id', $store->id)->count();
        
        $monthlyOrders = \App\Models\StoreOrder::where('retail_store_id', $store->id)
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('grand_total');

        $stats = [
            'visits_count' => $store->visits()
                ->whereMonth('visit_date', $currentMonth->month)
                ->whereYear('visit_date', $currentMonth->year)
                ->count(),
            
            'completed_visits' => $store->visits()
                ->whereMonth('visit_date', $currentMonth->month)
                ->whereYear('visit_date', $currentMonth->year)
                ->where('visit_status', 'completed')
                ->count(),
                
            'total_orders' => $totalOrders,
            'monthly_sales' => $monthlyOrders,
            'total_due_amount' => $totalDueAmount,
            'orders_with_due' => $ordersWithDue
        ];

        return $stats;
    }
}
