@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 text-primary">{{ translate('Create Stock Transfer') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                <a href="{{ route('seller.warehouse.transfers.index') }}" class="btn btn-light">
                    <i class="las la-arrow-left"></i> {{ translate('Back to Transfers') }}
                </a>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('seller.warehouse.transfers.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Product') }} <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                                <option value="">{{ translate('Select Product') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->getTranslation('name') }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Product Variant') }}</label>
                            <select name="product_stock_id" class="form-control">
                                <option value="">{{ translate('No Variant') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('From Warehouse') }} <span class="text-danger">*</span></label>
                            <select name="from_warehouse_id" class="form-control aiz-selectpicker" required>
                                <option value="">{{ translate('Select Source Warehouse') }}</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('To Warehouse') }} <span class="text-danger">*</span></label>
                            <select name="to_warehouse_id" class="form-control aiz-selectpicker" required>
                                <option value="">{{ translate('Select Destination Warehouse') }}</option>
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
                            <label>{{ translate('Quantity') }} <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Notes') }}</label>
                            <textarea name="notes" class="form-control" rows="1" placeholder="{{ translate('Optional transfer notes') }}"></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary">{{ translate('Create Transfer') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script>
    $(document).ready(function(){
        $('select[name="product_id"]').on('change', function(){
            var product_id = $(this).val();
            if(product_id){
                $.ajax({
                    url: "{{ route('seller.warehouses.getProductStock') }}",
                    type: "GET",
                    data: {product_id: product_id},
                    success: function(data){
                        $('select[name="product_stock_id"]').empty();
                        $('select[name="product_stock_id"]').append('<option value="">{{ translate('No Variant') }}</option>');
                        $.each(data, function(key, value){
                            $('select[name="product_stock_id"]').append('<option value="'+ value.id +'">'+ value.variant +'</option>');
                        });
                    }
                });
            }
        });
    });
</script>
@endsection
