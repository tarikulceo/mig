@extends('seller.layouts.app')

@section('panel_content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Debug - Warehouse Test')}}</h1>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{translate('Debug Information')}}</h5>
    </div>
    <div class="card-body">
        <p><strong>Current User ID:</strong> {{ Auth::id() }}</p>
        <p><strong>Current User Type:</strong> {{ Auth::user()->user_type ?? 'N/A' }}</p>
        <p><strong>Current User Email:</strong> {{ Auth::user()->email ?? 'N/A' }}</p>
        <p><strong>Total Warehouses in DB:</strong> {{ App\Models\Warehouse::count() }}</p>
        <p><strong>Active Warehouses:</strong> {{ App\Models\Warehouse::active()->count() }}</p>
        <p><strong>Warehouses Created by This User:</strong> {{ App\Models\Warehouse::where('created_by', Auth::id())->count() }}</p>
        
        <hr>
        <h6>Test Links:</h6>
        <a href="{{ route('seller.warehouses.index') }}" class="btn btn-primary">Go to Warehouses Index</a>
        <a href="{{ route('seller.warehouses.create') }}" class="btn btn-success">Go to Create Warehouse</a>
        <a href="{{ route('seller.warehouse.alerts') }}" class="btn btn-warning">Go to Alerts</a>
    </div>
</div>
@endsection
