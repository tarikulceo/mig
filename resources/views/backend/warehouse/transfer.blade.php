@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="align-items-center">
        <h1 class="h3">{{translate('Stock Transfer')}}</h1>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{translate('Transfer Stock Between Warehouses')}}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('warehouse.transfer') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="product_id">{{translate('Product')}} <span class="text-danger">*</span></label>
                        <select name="product_id" id="product_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                            <option value="">{{translate('Select Product')}}</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="product_stock_id">{{translate('Product Variant')}}</label>
                        <select name="product_stock_id" id="product_stock_id" class="form-control aiz-selectpicker">
                            <option value="">{{translate('No Variant')}}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="from_warehouse_id">{{translate('From Warehouse')}} <span class="text-danger">*</span></label>
                        <select name="from_warehouse_id" id="from_warehouse_id" class="form-control aiz-selectpicker" required>
                            <option value="">{{translate('Select Source Warehouse')}}</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="to_warehouse_id">{{translate('To Warehouse')}} <span class="text-danger">*</span></label>
                        <select name="to_warehouse_id" id="to_warehouse_id" class="form-control aiz-selectpicker" required>
                            <option value="">{{translate('Select Destination Warehouse')}}</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="quantity">{{translate('Quantity')}} <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                        <small class="form-text text-muted">
                            <span id="available_stock">{{translate('Available stock will be shown here')}}</span>
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="reason">{{translate('Reason')}}</label>
                        <input type="text" name="reason" id="reason" class="form-control" placeholder="{{translate('Transfer reason (optional)')}}">
                    </div>
                </div>
            </div>

            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary">{{translate('Transfer Stock')}}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function(){
        // Load product variants when product is selected
        $('#product_id').on('change', function(){
            var product_id = $(this).val();
            if(product_id) {
                $.get('{{ route("warehouse.product.stock") }}', {product_id: product_id}, function(data) {
                    $('#product_stock_id').empty().append('<option value="">{{translate("No Variant")}}</option>');
                    $.each(data, function(key, stock) {
                        $('#product_stock_id').append('<option value="'+ stock.id +'">'+ stock.variant +'</option>');
                    });
                    $('#product_stock_id').selectpicker('refresh');
                });
            }
        });

        // Load available stock when warehouse and product are selected
        function loadAvailableStock() {
            var warehouse_id = $('#from_warehouse_id').val();
            var product_id = $('#product_id').val();
            var product_stock_id = $('#product_stock_id').val();
            
            if(warehouse_id && product_id) {
                $.get('{{ route("warehouse.stock.check") }}', {
                    warehouse_id: warehouse_id,
                    product_id: product_id,
                    product_stock_id: product_stock_id
                }, function(data) {
                    $('#available_stock').text('{{translate("Available")}} : ' + data.available_quantity);
                    $('#quantity').attr('max', data.available_quantity);
                });
            }
        }

        $('#from_warehouse_id, #product_id, #product_stock_id').on('change', loadAvailableStock);
    });
</script>
@endsection
