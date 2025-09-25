@extends('seller.layouts.app')

@section('panel_content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Warehouse Details')}} - {{ $warehouse->name }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('seller.warehouses.index') }}" class="btn btn-light">
                <i class="las la-arrow-left"></i> {{translate('Back to Warehouses')}}
            </a>
        </div>
    </div>
</div>

<!-- Warehouse Information -->
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0 h6">{{translate('Warehouse Information')}}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="30%">{{translate('Name')}}:</th>
                        <td>{{ $warehouse->name }}</td>
                    </tr>
                    <tr>
                        <th>{{translate('Address')}}:</th>
                        <td>{{ $warehouse->address }}</td>
                    </tr>
                    <tr>
                        <th>{{translate('Manager')}}:</th>
                        <td>{{ $warehouse->manager_name ?: translate('Not Assigned') }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="30%">{{translate('Email')}}:</th>
                        <td>{{ $warehouse->email ?: translate('Not Provided') }}</td>
                    </tr>
                    <tr>
                        <th>{{translate('Phone')}}:</th>
                        <td>{{ $warehouse->phone ?: translate('Not Provided') }}</td>
                    </tr>
                    <tr>
                        <th>{{translate('Status')}}:</th>
                        <td>
                            @if($warehouse->is_active)
                                <span class="badge badge-soft-success">{{translate('Active')}}</span>
                            @else
                                <span class="badge badge-soft-danger">{{translate('Inactive')}}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Stock Summary -->
<div class="row">
    <div class="col-md-3">
        <div class="card bg-soft-primary border-soft-primary">
            <div class="card-body text-center">
                <h3 class="text-primary">{{ $stocks->count() }}</h3>
                <p class="mb-0">{{translate('Your Products')}}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-soft-success border-soft-success">
            <div class="card-body text-center">
                <h3 class="text-success">{{ $stocks->sum('quantity') }}</h3>
                <p class="mb-0">{{translate('Total Stock')}}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-soft-warning border-soft-warning">
            <div class="card-body text-center">
                <h3 class="text-warning">{{ $lowStockAlerts->count() }}</h3>
                <p class="mb-0">{{translate('Low Stock Alerts')}}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-soft-info border-soft-info">
            <div class="card-body text-center">
                <h3 class="text-info">{{ $stocks->sum('available_quantity') }}</h3>
                <p class="mb-0">{{translate('Available Stock')}}</p>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Alerts -->
@if($lowStockAlerts->count() > 0)
<div class="card mt-3">
    <div class="card-header">
        <h5 class="mb-0 h6 text-warning">{{translate('Low Stock Alerts for Your Products')}}</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{translate('Product')}}</th>
                        <th>{{translate('Current Stock')}}</th>
                        <th>{{translate('Threshold')}}</th>
                        <th>{{translate('Alerted At')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockAlerts as $alert)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ uploaded_asset($alert->product->thumbnail_img)}}" alt="Image" class="size-40px img-fit mr-2">
                                    <div>
                                        <span>{{ $alert->product->name }}</span>
                                        @if($alert->productStock)
                                            <br><small class="text-secondary">{{ $alert->productStock->variant }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-danger">{{ $alert->current_stock }}</span></td>
                            <td>{{ $alert->threshold }}</td>
                            <td>{{ $alert->alerted_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Stock Details -->
<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 h6">{{translate('Your Product Stocks in This Warehouse')}}</h5>
        <a href="{{ route('seller.warehouses.stock', $warehouse->id) }}" class="btn btn-sm btn-primary">
            {{translate('Manage Stock')}}
        </a>
    </div>
    <div class="card-body">
        @if($stocks->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{translate('Product')}}</th>
                            <th>{{translate('SKU')}}</th>
                            <th>{{translate('Variant')}}</th>
                            <th>{{translate('Total Stock')}}</th>
                            <th>{{translate('Available')}}</th>
                            <th>{{translate('Reserved')}}</th>
                            <th>{{translate('Status')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stocks->take(10) as $stock)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ uploaded_asset($stock->product->thumbnail_img)}}" alt="Image" class="size-40px img-fit mr-2">
                                        <span>{{ $stock->product->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $stock->product->sku }}</td>
                                <td>
                                    @if($stock->productStock)
                                        {{ $stock->productStock->variant }}
                                    @else
                                        {{translate('No Variant')}}
                                    @endif
                                </td>
                                <td>{{ $stock->quantity }}</td>
                                <td>{{ $stock->available_quantity }}</td>
                                <td>{{ $stock->reserved_quantity }}</td>
                                <td>
                                    @if($stock->quantity == 0)
                                        <span class="badge badge-soft-danger">{{translate('Out of Stock')}}</span>
                                    @elseif($stock->low_stock_threshold && $stock->available_quantity <= $stock->low_stock_threshold)
                                        <span class="badge badge-soft-warning">{{translate('Low Stock')}}</span>
                                    @else
                                        <span class="badge badge-soft-success">{{translate('In Stock')}}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($stocks->count() > 10)
                <div class="text-center mt-3">
                    <a href="{{ route('seller.warehouses.stock', $warehouse->id) }}" class="btn btn-primary">
                        {{translate('View All Stocks')}} ({{ $stocks->count() - 10 }} {{translate('more')}})
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-4">
                <img class="mw-100 mx-auto mb-3" src="{{ static_asset('assets/img/placeholder.jpg') }}" height="40">
                <h4 class="h6">{{translate('No products found in this warehouse')}}</h4>
                <p class="text-muted">{{translate('Your products will appear here once they are stocked in this warehouse')}}</p>
            </div>
        @endif
    </div>
</div>

<!-- Recent Transfers -->
@if($recentTransfers->count() > 0)
<div class="card mt-3">
    <div class="card-header">
        <h5 class="mb-0 h6">{{translate('Recent Transfers for Your Products')}}</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{translate('Date')}}</th>
                        <th>{{translate('Transfer ID')}}</th>
                        <th>{{translate('Product')}}</th>
                        <th>{{translate('From')}}</th>
                        <th>{{translate('To')}}</th>
                        <th>{{translate('Quantity')}}</th>
                        <th>{{translate('Status')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTransfers as $transfer)
                        <tr>
                            <td>{{ $transfer->created_at->format('d M Y') }}</td>
                            <td><code>{{ $transfer->transfer_code }}</code></td>
                            <td>{{ $transfer->product->name }}</td>
                            <td>{{ $transfer->fromWarehouse->name }}</td>
                            <td>{{ $transfer->toWarehouse->name }}</td>
                            <td>{{ $transfer->quantity }}</td>
                            <td>
                                @if($transfer->status == 'completed')
                                    <span class="badge badge-soft-success">{{translate('Completed')}}</span>
                                @elseif($transfer->status == 'pending')
                                    <span class="badge badge-soft-warning">{{translate('Pending')}}</span>
                                @else
                                    <span class="badge badge-soft-info">{{ ucfirst($transfer->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
