@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 text-primary">{{ translate('Warehouse Stock Reports') }}</h1>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select name="warehouse_id" class="form-control">
                            <option value="">{{ translate('Select Warehouse') }}</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ (request('warehouse_id') == $warehouse->id) ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                    </div>
                </div>
            </form>
            @if($report)
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group">
                            <li class="list-group-item"><strong>{{ translate('Warehouse') }}:</strong> {{ $report['warehouse']->name }}</li>
                            <li class="list-group-item"><strong>{{ translate('Seller Products Count') }}:</strong> {{ $report['seller_products_count'] }}</li>
                            <li class="list-group-item"><strong>{{ translate('Total Stock') }}:</strong> {{ $report['seller_total_stock'] }}</li>
                            <li class="list-group-item"><strong>{{ translate('Low Stock Products') }}:</strong> {{ $report['seller_low_stock_products'] }}</li>
                            <li class="list-group-item"><strong>{{ translate('Active Alerts') }}:</strong> {{ $report['seller_active_alerts'] }}</li>
                        </ul>
                    </div>
                </div>
            @else
                <div class="alert alert-info">{{ translate('Select a warehouse to view the report.') }}</div>
            @endif
        </div>
    </div>
@endsection
