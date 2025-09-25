@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Stock Management')}} - {{ $warehouse->name }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addStockModal">
                <span>{{translate('Add/Update Stock')}}</span>
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col text-center text-md-left">
            <h5 class="mb-md-0 h6">{{ translate('Warehouse Stock') }}</h5>
        </div>
        <div class="col-md-4">
            <form class="" id="sort_stocks" action="" method="GET">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="search" name="search" @isset($search) value="{{ $search }}" @endisset placeholder="{{ translate('Type product name & Enter') }}">
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{translate('Product')}}</th>
                    <th>{{translate('SKU')}}</th>
                    <th>{{translate('Variant')}}</th>
                    <th>{{translate('Available Stock')}}</th>
                    <th>{{translate('Reserved Stock')}}</th>
                    <th>{{translate('Total Stock')}}</th>
                    <th>{{translate('Low Stock Threshold')}}</th>
                    <th>{{translate('Status')}}</th>
                    <th class="text-right">{{translate('Actions')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stocks as $key => $stock)
                    <tr>
                        <td>{{ ($key+1) + ($stocks->currentPage() - 1)*$stocks->perPage() }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($stock->product->thumbnail)
                                    <img src="{{ uploaded_asset($stock->product->thumbnail->file_name) }}" class="size-50px img-fit mr-2">
                                @else
                                    <img src="{{ static_asset('assets/img/placeholder.jpg') }}" class="size-50px img-fit mr-2">
                                @endif
                                <div>
                                    <a href="{{ route('product', $stock->product->slug) }}" class="text-reset" target="_blank">
                                        {{ $stock->formatted_product_name }}
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>{{ $stock->productStock ? $stock->productStock->sku : $stock->product->sku }}</td>
                        <td>
                            @if($stock->productStock && $stock->productStock->variant)
                                {{ $stock->productStock->variant }}
                            @else
                                <span class="text-muted">{{translate('Default')}}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $stock->available_quantity > 0 ? 'success' : 'danger' }}">
                                {{ $stock->available_quantity }}
                            </span>
                        </td>
                        <td>
                            @if($stock->reserved_quantity > 0)
                                <span class="badge badge-warning">{{ $stock->reserved_quantity }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $stock->quantity > 0 ? 'info' : 'secondary' }}">
                                {{ $stock->quantity }}
                            </span>
                        </td>
                        <td>{{ $stock->low_stock_threshold }}</td>
                        <td>
                            <span class="badge {{ $stock->stock_status['badge_class'] }}">
                                {{ $stock->stock_status['text'] }}
                            </span>
                        </td>
                        <td class="text-right">
                            <button type="button" class="btn btn-soft-primary btn-icon btn-circle btn-sm" 
                                onclick="editStock({{ $stock->id }}, '{{ addslashes($stock->formatted_product_name) }}', {{ $stock->quantity }}, {{ $stock->low_stock_threshold }}, {{ $stock->product_id }}, {{ $stock->product_stock_id ?: 'null' }})" 
                                title="{{ translate('Edit Stock') }}">
                                <i class="las la-edit"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $stocks->appends(request()->input())->links() }}
        </div>
    </div>
</div>

<!-- Add/Edit Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockModalTitle">{{translate('Add/Update Stock')}}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('warehouses.updateStock', $warehouse->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="product_select">{{translate('Product')}} <span class="text-danger">*</span></label>
                        <select name="product_id" id="product_select" class="form-control aiz-selectpicker" data-live-search="true" onchange="getProductStocks()" required>
                            <option value="">{{translate('Select Product')}}</option>
                            @foreach(App\Models\Product::where('published', 1)->where('approved', 1)->orderBy('name')->get() as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="variant_group" style="display: none;">
                        <label for="product_stock_select">{{translate('Variant')}}</label>
                        <select name="product_stock_id" id="product_stock_select" class="form-control">
                            <option value="">{{translate('Default')}}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="operation">{{translate('Operation')}} <span class="text-danger">*</span></label>
                        <select name="operation" id="operation" class="form-control" required>
                            <option value="set">{{translate('Set Quantity')}}</option>
                            <option value="add">{{translate('Add Quantity')}}</option>
                            <option value="subtract">{{translate('Subtract Quantity')}}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">{{translate('Quantity')}} <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="low_stock_threshold">{{translate('Low Stock Threshold')}}</label>
                        <input type="number" name="low_stock_threshold" id="low_stock_threshold" class="form-control" min="0">
                    </div>

                    <div id="current_stock_info" class="alert alert-info" style="display: none;">
                        <strong>{{translate('Current Stock')}}:</strong> <span id="current_stock_value">0</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{translate('Close')}}</button>
                    <button type="submit" class="btn btn-primary">{{translate('Update Stock')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    function editStock(stockId, productName, quantity, threshold, productId, productStockId) {
        $('#stockModalTitle').text('{{translate("Edit Stock")}} - ' + productName);
        $('#product_select').val(productId).trigger('change');
        
        if (productStockId) {
            setTimeout(function() {
                $('#product_stock_select').val(productStockId);
            }, 500);
        }
        
        $('#quantity').val(quantity);
        $('#low_stock_threshold').val(threshold);
        $('#current_stock_value').text(quantity);
        $('#current_stock_info').show();
        $('#operation').val('set');
        
        $('#addStockModal').modal('show');
    }

    function getProductStocks() {
        var productId = $('#product_select').val();
        if (productId) {
            $.get('{{ route("warehouses.getProductStock") }}', {product_id: productId}, function(data) {
                $('#product_stock_select').empty().append('<option value="">{{translate("Default")}}</option>');
                if (data.length > 0) {
                    $('#variant_group').show();
                    $.each(data, function(index, stock) {
                        $('#product_stock_select').append('<option value="' + stock.id + '">' + (stock.variant || '{{translate("Default")}}') + ' (SKU: ' + stock.sku + ')</option>');
                    });
                } else {
                    $('#variant_group').hide();
                }
                
                // Get current stock info
                getWarehouseStock();
            });
        } else {
            $('#variant_group').hide();
            $('#current_stock_info').hide();
        }
    }

    function getWarehouseStock() {
        var productId = $('#product_select').val();
        var productStockId = $('#product_stock_select').val();
        
        if (productId) {
            $.get('{{ route("warehouses.getWarehouseStock") }}', {
                warehouse_id: {{ $warehouse->id }},
                product_id: productId,
                product_stock_id: productStockId
            }, function(data) {
                $('#current_stock_value').text(data.total_quantity);
                $('#current_stock_info').show();
            });
        }
    }

    $('#product_stock_select').change(function() {
        getWarehouseStock();
    });

    // Reset modal when closed
    $('#addStockModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('#variant_group').hide();
        $('#current_stock_info').hide();
        $('#stockModalTitle').text('{{translate("Add/Update Stock")}}');
        $('#product_select').val('').trigger('change');
    });
</script>
@endsection
