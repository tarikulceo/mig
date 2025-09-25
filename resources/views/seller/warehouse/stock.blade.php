@extends('seller.layouts.app')

@section('panel_content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Manage Stock')}} - {{ $warehouse->name }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('seller.warehouses.show', $warehouse->id) }}" class="btn btn-light">
                <i class="las la-arrow-left"></i> {{translate('Back to Warehouse')}}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col">
            <h5 class="mb-md-0 h6">{{ translate('Product Stocks') }}</h5>
        </div>
        <div class="col-md-4">
            <form class="" action="" method="GET">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="search" name="search" @isset($search) value="{{ $search }}" @endisset placeholder="{{ translate('Type product name & hit Enter') }}">
                </div>
            </form>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#update_stock_modal">
                {{translate('Add/Update Stock')}}
            </button>
        </div>
    </div>
    <div class="card-body">
        @if($stocks->count() > 0)
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{translate('Product')}}</th>
                        <th>{{translate('SKU')}}</th>
                        <th>{{translate('Variant')}}</th>
                        <th>{{translate('Total Stock')}}</th>
                        <th>{{translate('Available')}}</th>
                        <th>{{translate('Reserved')}}</th>
                        <th>{{translate('Low Stock Threshold')}}</th>
                        <th>{{translate('Status')}}</th>
                        <th>{{translate('Actions')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stocks as $stock)
                        <tr>
                            <td>
                                <div class="form-group row">
                                    <div class="col-auto">
                                        <img src="{{ uploaded_asset($stock->product->thumbnail_img)}}" alt="Image" class="size-40px img-fit">
                                    </div>
                                    <div class="col">
                                        <span class="text-muted text-truncate-2">{{ $stock->formatted_product_name }}</span>
                                    </div>
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
                            <td>{{ $stock->low_stock_threshold ?: translate('Not Set') }}</td>
                            <td>
                                <span class="badge {{ $stock->stock_status['badge_class'] }}">
                                    {{ $stock->stock_status['text'] }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-soft-primary btn-icon btn-circle btn-sm" 
                                        onclick="edit_stock('{{ $stock->id }}', '{{ $stock->product->id }}', '{{ $stock->product_stock_id }}', '{{ $stock->quantity }}', '{{ $stock->low_stock_threshold }}')" 
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
        @else
            <div class="text-center py-4">
                <img class="mw-100 mx-auto mb-3" src="{{ static_asset('assets/img/placeholder.jpg') }}" height="40">
                <h4 class="h6">{{translate('No stocks found')}}</h4>
                <p class="text-muted">{{translate('Your product stocks will appear here')}}</p>
            </div>
        @endif
    </div>
</div>

<!-- Stock Update Modal -->
<div class="modal fade" id="update_stock_modal" tabindex="-1" role="dialog" aria-labelledby="updateStockModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStockModalLabel">{{translate('Update Stock')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('seller.warehouses.updateStock', $warehouse->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="product_id">{{translate('Product')}} <span class="text-danger">*</span></label>
                        <select name="product_id" id="product_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                            <option value="">{{translate('Select Product')}}</option>
                            @foreach($sellerProducts as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="product_stock_id">{{translate('Product Variant')}}</label>
                        <select name="product_stock_id" id="product_stock_id" class="form-control aiz-selectpicker">
                            <option value="">{{translate('No Variant')}}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="operation">{{translate('Operation')}} <span class="text-danger">*</span></label>
                        <select name="operation" id="operation" class="form-control" required>
                            <option value="set">{{translate('Set Stock (Replace current stock)')}}</option>
                            <option value="add">{{translate('Add Stock (Increase current stock)')}}</option>
                            <option value="subtract">{{translate('Subtract Stock (Decrease current stock)')}}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">{{translate('Quantity')}} <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="0" required>
                        <small class="form-text text-muted">
                            <span id="current_stock_info">{{translate('Current stock will be shown here')}}</span>
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="low_stock_threshold">{{translate('Low Stock Threshold')}}</label>
                        <input type="number" name="low_stock_threshold" id="low_stock_threshold" class="form-control" min="0">
                        <small class="form-text text-muted">{{translate('Alert when stock falls below this number')}}</small>
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
    // Load product variants when product is selected
    $('#product_id').on('change', function(){
        var product_id = $(this).val();
        if(product_id) {
            $.get('{{ route("seller.warehouses.getProductStock") }}', {product_id: product_id}, function(data) {
                $('#product_stock_id').empty().append('<option value="">{{translate("No Variant")}}</option>');
                $.each(data, function(key, stock) {
                    $('#product_stock_id').append('<option value="'+ stock.id +'">'+ stock.variant +'</option>');
                });
                $('#product_stock_id').selectpicker('refresh');
            });
        }
        loadCurrentStock();
    });

    // Load current stock when product or variant changes
    $('#product_stock_id').on('change', loadCurrentStock);

    function loadCurrentStock() {
        var product_id = $('#product_id').val();
        var product_stock_id = $('#product_stock_id').val();
        
        if(product_id) {
            $.get('{{ route("seller.warehouses.getWarehouseStock", $warehouse->id) }}', {
                product_id: product_id,
                product_stock_id: product_stock_id
            }, function(data) {
                $('#current_stock_info').html(
                    '{{translate("Current stock")}}: ' + data.total_quantity + 
                    ' ({{translate("Available")}}: ' + data.available_quantity + 
                    ', {{translate("Reserved")}}: ' + data.reserved_quantity + ')'
                );
            });
        }
    }

    function edit_stock(stock_id, product_id, product_stock_id, quantity, threshold) {
        $('#product_id').val(product_id).selectpicker('refresh');
        
        // Load variants for the selected product
        if(product_id) {
            $.get('{{ route("seller.warehouses.getProductStock") }}', {product_id: product_id}, function(data) {
                $('#product_stock_id').empty().append('<option value="">{{translate("No Variant")}}</option>');
                $.each(data, function(key, stock) {
                    var selected = stock.id == product_stock_id ? 'selected' : '';
                    $('#product_stock_id').append('<option value="'+ stock.id +'" '+ selected +'>'+ stock.variant +'</option>');
                });
                $('#product_stock_id').selectpicker('refresh');
                loadCurrentStock();
            });
        }
        
        $('#operation').val('set');
        $('#quantity').val(quantity);
        $('#low_stock_threshold').val(threshold);
        
        $('#update_stock_modal').modal('show');
    }
</script>
@endsection
