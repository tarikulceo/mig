@extends('seller.layouts.app')

@section('panel_content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Warehouses')}}</h1>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col text-center text-md-left">
            <h5 class="mb-md-0 h6">{{ translate('Available Warehouses') }}</h5>
        </div>
        <div class="col-md-4">
            <form class="" id="sort_warehouses" action="" method="GET">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="search" name="search" @isset($search) value="{{ $search }}" @endisset placeholder="{{ translate('Type name & Enter') }}">
                </div>
            </form>
        </div>
        <div class="col-md-3 text-right">
            <a href="{{ route('seller.warehouses.create') }}" class="btn btn-primary btn-sm">
                <i class="las la-plus"></i>
                {{ translate('Add New Warehouse') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{translate('Name')}}</th>
                    <th>{{translate('Address')}}</th>
                    <th>{{translate('Manager')}}</th>
                    <th>{{translate('Contact')}}</th>
                    <th>{{translate('Your Products')}}</th>
                    <th>{{translate('Your Stock')}}</th>
                    <th class="text-right">{{translate('Actions')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($warehouses as $key => $warehouse)
                    @php
                        $sellerProducts = App\Models\Product::where('user_id', Auth::user()->id)->pluck('id');
                        $sellerStocksCount = $warehouse->stocks()->whereIn('product_id', $sellerProducts)->count();
                        $sellerTotalStock = $warehouse->stocks()->whereIn('product_id', $sellerProducts)->sum('quantity');
                    @endphp
                    <tr>
                        <td>{{ ($key+1) + ($warehouses->currentPage() - 1)*$warehouses->perPage() }}</td>
                        <td>
                            <a href="{{ route('seller.warehouses.show', $warehouse->id) }}" class="text-reset">
                                {{ $warehouse->name }}
                            </a>
                        </td>
                        <td>
                            <span class="text-truncate" style="max-width: 200px; display: inline-block;" title="{{ $warehouse->address }}">
                                {{ $warehouse->address }}
                            </span>
                        </td>
                        <td>{{ $warehouse->manager_name ?? 'N/A' }}</td>
                        <td>
                            @if($warehouse->email)
                                <div>{{ $warehouse->email }}</div>
                            @endif
                            @if($warehouse->phone)
                                <div>{{ $warehouse->phone }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-info">{{ $sellerStocksCount }}</span>
                        </td>
                        <td>
                            <span class="badge badge-secondary">{{ $sellerTotalStock }}</span>
                        </td>
                        <td class="text-right">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('seller.warehouses.show', $warehouse->id) }}" title="{{ translate('View') }}">
                                <i class="las la-eye"></i>
                            </a>
                            <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('seller.warehouses.stock', $warehouse->id) }}" title="{{ translate('Manage Stock') }}">
                                <i class="las la-boxes"></i>
                            </a>
                            <a class="btn btn-soft-secondary btn-icon btn-circle btn-sm" href="{{ route('seller.warehouses.edit', $warehouse->id) }}" title="{{ translate('Edit') }}">
                                <i class="las la-edit"></i>
                            </a>
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('seller.warehouses.destroy', $warehouse->id) }}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $warehouses->appends(request()->input())->links() }}
        </div>
    </div>
</div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function () {
            //$('#container').removeClass('mainnav-lg').addClass('mainnav-sm');
        });
        function sort_warehouses(el){
            $('#sort_warehouses').submit();
        }
    </script>
@endsection
