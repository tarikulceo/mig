@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="align-items-center">
        <h1 class="h3">{{translate('Warehouse Reports')}}</h1>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{translate('Generate Warehouse Stock Report')}}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouse.reports') }}" method="GET">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="warehouse_id">{{translate('Warehouse')}} <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="warehouse_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                            <option value="">{{translate('Select Warehouse')}}</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @if($warehouseId == $warehouse->id) selected @endif>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="start_date">{{translate('Start Date')}}</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="end_date">{{translate('End Date')}}</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">{{translate('Generate Report')}}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@if($report)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 h6">{{translate('Warehouse Stock Report')}}</h5>
        <div>
            <button onclick="print_report()" class="btn btn-sm btn-info">
                <i class="las la-print"></i> {{translate('Print')}}
            </button>
            <button onclick="export_report()" class="btn btn-sm btn-success">
                <i class="las la-download"></i> {{translate('Export CSV')}}
            </button>
        </div>
    </div>
    <div class="card-body" id="report_content">
        <div class="mb-4">
            <h6><strong>{{translate('Warehouse')}}:</strong> {{ $report['warehouse']->name }}</h6>
            <p class="mb-1"><strong>{{translate('Report Period')}}:</strong> 
                @if($startDate && $endDate)
                    {{ date('d M Y', strtotime($startDate)) }} - {{ date('d M Y', strtotime($endDate)) }}
                @else
                    {{translate('All Time')}}
                @endif
            </p>
            <p class="mb-1"><strong>{{translate('Generated On')}}:</strong> {{ date('d M Y, h:i A') }}</p>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-soft-primary border-soft-primary">
                    <div class="card-body text-center">
                        <h3 class="text-primary">{{ $report['total_products'] }}</h3>
                        <p class="mb-0">{{translate('Total Products')}}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-soft-success border-soft-success">
                    <div class="card-body text-center">
                        <h3 class="text-success">{{ $report['total_stock'] }}</h3>
                        <p class="mb-0">{{translate('Total Stock')}}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-soft-warning border-soft-warning">
                    <div class="card-body text-center">
                        <h3 class="text-warning">{{ $report['low_stock_products'] }}</h3>
                        <p class="mb-0">{{translate('Low Stock Items')}}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-soft-danger border-soft-danger">
                    <div class="card-body text-center">
                        <h3 class="text-danger">{{ $report['out_of_stock_products'] }}</h3>
                        <p class="mb-0">{{translate('Out of Stock')}}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Details -->
        <h6 class="mb-3">{{translate('Stock Details')}}</h6>
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
                        <th>{{translate('Threshold')}}</th>
                        <th>{{translate('Status')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['stock_details'] as $stock)
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
                            <td>{{ $stock->low_stock_threshold ?? translate('Not Set') }}</td>
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

        @if(count($report['recent_transfers']) > 0)
        <!-- Recent Transfers -->
        <h6 class="mb-3 mt-4">{{translate('Recent Transfers')}}</h6>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{translate('Date')}}</th>
                        <th>{{translate('Transfer ID')}}</th>
                        <th>{{translate('Product')}}</th>
                        <th>{{translate('Type')}}</th>
                        <th>{{translate('Quantity')}}</th>
                        <th>{{translate('Status')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['recent_transfers'] as $transfer)
                        <tr>
                            <td>{{ $transfer->created_at->format('d M Y') }}</td>
                            <td><code>{{ $transfer->transfer_code }}</code></td>
                            <td>{{ $transfer->product->name }}</td>
                            <td>
                                @if($transfer->from_warehouse_id == $report['warehouse']->id)
                                    <span class="text-danger">{{translate('Outgoing')}}</span>
                                @else
                                    <span class="text-success">{{translate('Incoming')}}</span>
                                @endif
                            </td>
                            <td>{{ $transfer->quantity }}</td>
                            <td>
                                @if($transfer->status == 'completed')
                                    <span class="badge badge-soft-success">{{translate('Completed')}}</span>
                                @else
                                    <span class="badge badge-soft-warning">{{ ucfirst($transfer->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endif

@endsection

@section('script')
<script type="text/javascript">
    function print_report() {
        var printContent = document.getElementById('report_content').innerHTML;
        var originalContent = document.body.innerHTML;
        
        document.body.innerHTML = '<div style="padding: 20px;">' + printContent + '</div>';
        window.print();
        document.body.innerHTML = originalContent;
        window.location.reload();
    }

    function export_report() {
        var params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        window.location.href = '{{ route("warehouse.reports") }}?' + params.toString();
    }
</script>
@endsection
