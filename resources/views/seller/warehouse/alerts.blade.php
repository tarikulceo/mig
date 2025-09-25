@extends('seller.layouts.app')

@section('panel_content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Low Stock Alerts')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('seller.warehouses.index') }}" class="btn btn-secondary">
                <i class="las la-warehouse"></i>
                {{ translate('Back to Warehouses') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col">
            <h5 class="mb-md-0 h6">{{ translate('Warehouse Stock Alerts') }}</h5>
            <small class="text-muted">{{ translate('Monitor low stock levels across your warehouses') }}</small>
        </div>
        <div class="col-md-3">
            <form class="" id="sort_alerts" action="" method="GET">
                <div class="input-group input-group-sm">
                    <select class="form-control aiz-selectpicker" name="warehouse_id" onchange="sort_alerts()">
                        <option value="">{{translate('All Warehouses')}}</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @if($warehouseId == $warehouse->id) selected @endif>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
        <div class="col-md-2">
            <form class="" id="sort_status" action="" method="GET">
                <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
                <div class="input-group input-group-sm">
                    <select class="form-control aiz-selectpicker" name="status" onchange="sort_status()">
                        <option value="active" @if($status == 'active') selected @endif>{{translate('Active')}}</option>
                        <option value="resolved" @if($status == 'resolved') selected @endif>{{translate('Resolved')}}</option>
                        <option value="ignored" @if($status == 'ignored') selected @endif>{{translate('Ignored')}}</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        @if($alerts->count() > 0)
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{translate('Warehouse')}}</th>
                        <th>{{translate('Product')}}</th>
                        <th>{{translate('Current Stock')}}</th>
                        <th>{{translate('Threshold')}}</th>
                        <th>{{translate('Alerted At')}}</th>
                        <th>{{translate('Status')}}</th>
                        <th class="text-right">{{translate('Actions')}}</th>
                    </tr>
                </thead>
                </thead>
                <tbody>
                    @foreach($alerts as $alert)
                        <tr>
                            <td>{{ $alert->warehouse ? $alert->warehouse->name : translate('Global Alert') }}</td>
                            <td>
                                <div class="form-group row">
                                    <div class="col-auto">
                                        <img src="{{ uploaded_asset($alert->product->thumbnail_img)}}" alt="Image" class="size-40px img-fit">
                                    </div>
                                    <div class="col">
                                        <span class="text-muted text-truncate-2">{{ $alert->product->name }}</span>
                                        @if($alert->productStock)
                                            <br><small class="text-secondary">{{ $alert->productStock->variant }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($alert->current_quantity <= $alert->threshold_quantity)
                                    <span class="badge badge-soft-danger">{{ $alert->current_quantity }}</span>
                                @else
                                    <span class="badge badge-soft-success">{{ $alert->current_quantity }}</span>
                                @endif
                            </td>
                            <td>{{ $alert->threshold_quantity }}</td>
                            <td>{{ $alert->alerted_at->format('d M Y, h:i A') }}</td>
                            <td>
                                @if($alert->status == 'active')
                                    <span class="badge badge-soft-warning">{{translate('Active')}}</span>
                                @elseif($alert->status == 'resolved')
                                    <span class="badge badge-soft-success">{{translate('Resolved')}}</span>
                                @elseif($alert->status == 'ignored')
                                    <span class="badge badge-soft-secondary">{{translate('Ignored')}}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($alert->warehouse)
                                    <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('seller.warehouses.stock', $alert->warehouse->id) }}" title="{{ translate('Manage Stock') }}">
                                        <i class="las la-boxes"></i>
                                    </a>
                                @endif
                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('seller.warehouses.show', $alert->warehouse->id) }}" title="{{ translate('View Warehouse') }}">
                                    <i class="las la-eye"></i>
                                </a>
                                @if($alert->warehouse)
                                    <a class="btn btn-soft-secondary btn-icon btn-circle btn-sm" href="{{ route('seller.warehouse.transfers.create') }}?from={{ $alert->warehouse->id }}&product={{ $alert->product->id }}" title="{{ translate('Transfer Stock') }}">
                                        <i class="las la-exchange-alt"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-4">
                <img class="mw-100 mx-auto mb-3" src="{{ static_asset('assets/img/placeholder.jpg') }}" height="40">
                <h4 class="h6">{{translate('No alerts found')}}</h4>
            </div>
        @endif
        
        <div class="aiz-pagination">
            {{ $alerts->appends(request()->input())->links() }}
        </div>
    </div>
</div>
@endsection

@section('script')
    <script type="text/javascript">
        function sort_alerts(){
            $('#sort_alerts').submit();
        }
        function sort_status(){
            $('#sort_status').submit();
        }
    </script>
@endsection

@section('script')
<script type="text/javascript">
    function sort_alerts() {
        $('#sort_alerts').submit();
    }

    function sort_status() {
        $('#sort_status').submit();
    }
</script>
@endsection