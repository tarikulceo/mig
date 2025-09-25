<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesTerritory;
use App\Models\Country;
use App\Models\State;
use App\Models\SalesRepresentative;

class SalesTerritoryController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:manage_sales_territories'])->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of sales territories
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $territories = SalesTerritory::with(['country', 'state'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $sort_search = $request->search;
            $territories = $territories->where('name', 'like', '%' . $sort_search . '%')
                ->orWhere('region', 'like', '%' . $sort_search . '%');
        }

        $territories = $territories->paginate(15);

        return view('backend.sales_territories.index', compact('territories', 'sort_search'));
    }

    /**
     * Show the form for creating a new territory
     */
    public function create()
    {
        $countries = Country::where('status', 1)->get();
        return view('backend.sales_territories.create', compact('countries'));
    }

    /**
     * Store a newly created territory
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sales_territories,name',
            'region' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id'
        ]);

        $territory = new SalesTerritory();
        $territory->name = $request->name;
        $territory->description = $request->description;
        $territory->region = $request->region;
        $territory->country_id = $request->country_id;
        $territory->state_id = $request->state_id;
        $territory->cities = $request->cities ? explode(',', $request->cities) : [];
        $territory->zip_codes = $request->zip_codes ? explode(',', $request->zip_codes) : [];
        $territory->is_active = $request->is_active ?? 1;
        $territory->save();

        flash(translate('Sales Territory has been created successfully'))->success();
        return redirect()->route('sales_territories.index');
    }

    /**
     * Display the specified territory
     */
    public function show($id)
    {
        $territory = SalesTerritory::with(['country', 'state', 'salesRepresentatives.user'])->findOrFail($id);
        
        return view('backend.sales_territories.show', compact('territory'));
    }

    /**
     * Show the form for editing the specified territory
     */
    public function edit($id)
    {
        $territory = SalesTerritory::findOrFail($id);
        $countries = Country::where('status', 1)->get();
        $states = State::where('country_id', $territory->country_id)->get();
        
        return view('backend.sales_territories.edit', compact('territory', 'countries', 'states'));
    }

    /**
     * Update the specified territory
     */
    public function update(Request $request, $id)
    {
        $territory = SalesTerritory::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:sales_territories,name,' . $id,
            'region' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id'
        ]);

        $territory->name = $request->name;
        $territory->description = $request->description;
        $territory->region = $request->region;
        $territory->country_id = $request->country_id;
        $territory->state_id = $request->state_id;
        $territory->cities = $request->cities ? explode(',', $request->cities) : [];
        $territory->zip_codes = $request->zip_codes ? explode(',', $request->zip_codes) : [];
        $territory->is_active = $request->is_active ?? 1;
        $territory->save();

        flash(translate('Sales Territory has been updated successfully'))->success();
        return redirect()->route('sales_territories.index');
    }

    /**
     * Remove the specified territory from storage
     */
    public function destroy($id)
    {
        $territory = SalesTerritory::findOrFail($id);
        
        // Check if territory has sales representatives
        if ($territory->salesRepresentatives()->count() > 0) {
            flash(translate('Cannot delete territory with assigned sales representatives'))->error();
            return back();
        }

        $territory->delete();
        flash(translate('Sales Territory has been deleted successfully'))->success();
        return redirect()->route('sales_territories.index');
    }

    /**
     * Get states by country
     */
    public function getStatesByCountry($countryId)
    {
        $states = State::where('country_id', $countryId)->get();
        return response()->json($states);
    }
}
