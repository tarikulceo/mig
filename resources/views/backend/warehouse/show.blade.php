@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Warehouse Details')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            @can('manage_warehouse_stock')
            <a href="{{ route('warehouses.stock', $warehouse->id) }}" class="btn btn-info">
                <span>{{translate('Manage Stock')}}</span>
            </a>
            @endcan
            @can('edit_warehouse')
            <a href="{{ route('warehouses.edit', $warehouse->id) }}" class="btn btn-primary">
                <span>{{translate('Edit Warehouse')}}</span>
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Warehouse Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Warehouse Information')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="w-50 fw-600">{{translate('Name')}}:</td>
                                <td>{{ $warehouse->name }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Manager')}}:</td>
                                <td>{{ $warehouse->manager_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Email')}}:</td>
                                <td>{{ $warehouse->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Phone')}}:</td>
                                <td>{{ $warehouse->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Status')}}:</td>
                                <td>
                                    @if($warehouse->is_active)
                                        <span class="badge badge-success">{{translate('Active')}}</span>
                                    @else
                                        <span class="badge badge-danger">{{translate('Inactive')}}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="w-50 fw-600">{{translate('Address')}}:</td>
                                <td>{{ $warehouse->address }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('City')}}:</td>
                                <td>{{ $warehouse->city ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('State')}}:</td>
                                <td>{{ $warehouse->state ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Country')}}:</td>
                                <td>{{ $warehouse->country ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Postal Code')}}:</td>
                                <td>{{ $warehouse->postal_code ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        @if($lowStockAlerts->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6 text-danger">{{translate('Low Stock Alerts')}}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
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
                                    {{ $alert->product->name }}
                                    @if($alert->productStock)
                                        <small class="text-muted d-block">{{ $alert->productStock->variant }}</small>
                                    @endif
                                </td>
                                <td><span class="badge badge-danger">{{ $alert->current_quantity }}</span></td>
                                <td>{{ $alert->threshold_quantity }}</td>
                                <td>{{ $alert->alerted_at->format('M d, Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-right mt-3">
                    <a href="{{ route('warehouse.alerts') }}?warehouse_id={{ $warehouse->id }}" class="btn btn-sm btn-outline-primary">
                        {{translate('View All Alerts')}}
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Recent Transfers -->
        @if($recentTransfers->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Recent Stock Transfers')}}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{translate('Product')}}</th>
                                <th>{{translate('From/To')}}</th>
                                <th>{{translate('Quantity')}}</th>
                                <th>{{translate('Status')}}</th>
                                <th>{{translate('Date')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransfers as $transfer)
                            <tr>
                                <td>
                                    {{ $transfer->product->name }}
                                    @if($transfer->productStock)
                                        <small class="text-muted d-block">{{ $transfer->productStock->variant }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($transfer->from_warehouse_id == $warehouse->id)
                                        <span class="text-danger">→ {{ $transfer->toWarehouse->name }}</span>
                                    @else
                                        <span class="text-success">← {{ $transfer->fromWarehouse->name }}</span>
                                    @endif
                                </td>
                                <td>{{ $transfer->quantity }}</td>
                                <td>
                                    @if($transfer->status == 'pending')
                                        <span class="badge badge-inline badge-warning">{{translate('Pending')}}</span>
                                    @elseif($transfer->status == 'in_transit')
                                        <span class="badge badge-inline badge-info">{{translate('In Transit')}}</span>
                                    @elseif($transfer->status == 'completed')
                                        <span class="badge badge-success">{{translate('Completed')}}</span>
                                    @else
                                        <span class="badge badge-danger">{{translate('Cancelled')}}</span>
                                    @endif
                                </td>
                                <td>{{ $transfer->transfer_date->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-right mt-3">
                    <a href="{{ route('warehouse.transfers.index') }}" class="btn btn-sm btn-outline-primary">
                        {{translate('View All Transfers')}}
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <!-- Statistics -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Statistics')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h2 mb-0 text-primary">{{ $warehouse->total_products }}</div>
                            <div class="text-muted">{{translate('Products')}}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h2 mb-0 text-info">{{ $warehouse->total_stock }}</div>
                            <div class="text-muted">{{translate('Total Stock')}}</div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h3 mb-0 text-warning">{{ $warehouse->low_stock_products_count }}</div>
                            <div class="text-muted">{{translate('Low Stock')}}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h3 mb-0 text-danger">{{ $lowStockAlerts->count() }}</div>
                            <div class="text-muted">{{translate('Active Alerts')}}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Quick Actions')}}</h5>
            </div>
            <div class="card-body">
                @can('manage_warehouse_stock')
                <a href="{{ route('warehouses.stock', $warehouse->id) }}" class="btn btn-block btn-outline-info mb-2">
                    <i class="las la-boxes"></i> {{translate('Manage Stock')}}
                </a>
                @endcan
                <a href="{{ route('warehouse.transfers.create') }}" class="btn btn-block btn-outline-primary mb-2">
                    <i class="las la-exchange-alt"></i> {{translate('Transfer Stock')}}
                </a>
                @can('view_warehouse_reports')
                <a href="{{ route('warehouses.reports', $warehouse->id) }}" class="btn btn-block btn-outline-success mb-2">
                    <i class="las la-chart-bar"></i> {{translate('View Reports')}}
                </a>
                @endcan
                <a href="{{ route('warehouse.alerts') }}?warehouse_id={{ $warehouse->id }}" class="btn btn-block btn-outline-warning">
                    <i class="las la-exclamation-triangle"></i> {{translate('View Alerts')}}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
