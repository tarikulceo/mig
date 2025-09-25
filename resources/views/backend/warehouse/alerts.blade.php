@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="align-items-center">
        <h1 class="h3">{{translate('Low Stock Alerts')}}</h1>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col">
            <h5 class="mb-md-0 h6">{{ translate('Warehouse Stock Alerts') }}</h5>
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
                        <th>{{translate('Actions')}}</th>
                    </tr>
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
                                @if($alert->warehouse)
                                    @php
                                        $stock = $alert->product->warehouse_stocks()->where('warehouse_id', $alert->warehouse->id)->where('product_stock_id', $alert->product_stock_id)->first();
                                    @endphp
                                    <span class="badge badge-soft-danger">{{ $stock ? $stock->quantity : 0 }}</span>
                                @else
                                    @php
                                        $query = $alert->product->warehouse_stocks();
                                        if ($alert->product_stock_id) {
                                            $query->where('product_stock_id', $alert->product_stock_id);
                                        } else {
                                            $query->whereNull('product_stock_id');
                                        }
                                        $totalStock = $query->sum('quantity');
                                    @endphp
                                    <span class="badge badge-soft-danger">{{ $totalStock }}</span>
                                @endif
                            </td>
                            <td>
                                @if($alert->warehouse)
                                    @php
                                        $stock = $alert->product->warehouse_stocks()->where('warehouse_id', $alert->warehouse->id)->where('product_stock_id', $alert->product_stock_id)->first();
                                    @endphp
                                    {{ $stock ? $stock->low_stock_threshold : 0 }}
                                @else
                                    {{ $alert->product->getGlobalLowStockThreshold() }}
                                @endif
                            </td>
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
                            <td>
                                @if($alert->status == 'active')
                                    <div class="dropdown">
                                        <button class="btn btn-soft-secondary btn-icon btn-circle btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="las la-ellipsis-h"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" onclick="resolve_alert('{{ $alert->id }}')">
                                                {{translate('Resolve')}}
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="ignore_alert('{{ $alert->id }}')">
                                                {{translate('Ignore')}}
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    @if($alert->resolvedBy)
                                        <small class="text-muted">
                                            {{translate('by')}} {{ $alert->resolvedBy->name }}<br>
                                            {{ $alert->resolved_at->format('d M Y') }}
                                        </small>
                                    @endif
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

<!-- Resolve Alert Modal -->
<div class="modal fade" id="resolve_alert_modal" tabindex="-1" role="dialog" aria-labelledby="resolveAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resolveAlertModalLabel">{{translate('Resolve Alert')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST" id="resolve_alert_form">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{translate('Resolution Notes')}}</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="{{translate('Add notes about how this alert was resolved')}}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{translate('Close')}}</button>
                    <button type="submit" class="btn btn-success">{{translate('Resolve Alert')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ignore Alert Modal -->
<div class="modal fade" id="ignore_alert_modal" tabindex="-1" role="dialog" aria-labelledby="ignoreAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ignoreAlertModalLabel">{{translate('Ignore Alert')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST" id="ignore_alert_form">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{translate('Ignore Reason')}}</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="{{translate('Add notes about why this alert is being ignored')}}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{translate('Close')}}</button>
                    <button type="submit" class="btn btn-warning">{{translate('Ignore Alert')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    function sort_alerts() {
        $('#sort_alerts').submit();
    }

    function sort_status() {
        $('#sort_status').submit();
    }

    function resolve_alert(alert_id) {
        $('#resolve_alert_form').attr('action', '{{ route("warehouse.alert.resolve", ":id") }}'.replace(':id', alert_id));
        $('#resolve_alert_modal').modal('show');
    }

    function ignore_alert(alert_id) {
        $('#ignore_alert_form').attr('action', '{{ route("warehouse.alert.ignore", ":id") }}'.replace(':id', alert_id));
        $('#ignore_alert_modal').modal('show');
    }
</script>
@endsection
