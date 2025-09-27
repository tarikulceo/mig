@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Warehouses')}}</h1>
        </div>
        @can('add_warehouse')
        <div class="col-md-6 text-md-right">
            <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
                <span>{{translate('Add New Warehouse')}}</span>
            </a>
        </div>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col text-center text-md-left">
            <h5 class="mb-md-0 h6">{{ translate('All Warehouses') }}</h5>
        </div>
        <div class="col-md-4">
            <form class="" id="sort_products" action="" method="GET">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="search" name="search" @isset($search) value="{{ $search }}" @endisset placeholder="{{ translate('Type name & Enter') }}">
                </div>
            </form>
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
                    <th>{{translate('Products')}}</th>
                    <th>{{translate('Total Stock')}}</th>
                    <th>{{translate('Low Stock')}}</th>
                    <th>{{translate('Status')}}</th>
                    <th class="text-right">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($warehouses as $key => $warehouse)
                    <tr>
                        <td>{{ ($key+1) + ($warehouses->currentPage() - 1)*$warehouses->perPage() }}</td>
                        <td>
                            <a href="{{ route('warehouses.show', $warehouse->id) }}" class="text-reset">
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
                            <span class="badge badge-inline badge-info">{{ $warehouse->total_products }}</span>
                        </td>
                        <td>
                            <span class="badge badge-inline badge-secondary">{{ $warehouse->total_stock }}</span>
                        </td>
                        <td>
                            @if($warehouse->low_stock_products_count > 0)
                                <span class="badge badge-inline badge-warning">{{ $warehouse->low_stock_products_count }}</span>
                            @else
                                <span class="badge badge-inline badge-success">0</span>
                            @endif
                        </td>
                        <td>
                            @if($warehouse->is_active)
                                <span class="badge badge-inline badge-success">{{translate('Active')}}</span>
                            @else
                                <span class="badge badge-inline badge-danger">{{translate('Inactive')}}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('warehouses.show', $warehouse->id) }}" title="{{ translate('View') }}">
                                <i class="las la-eye"></i>
                            </a>
                            @can('manage_warehouse_stock')
                            <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('warehouses.stock', $warehouse->id) }}" title="{{ translate('Manage Stock') }}">
                                <i class="las la-boxes"></i>
                            </a>
                            @endcan
                            @can('edit_warehouse')
                            <a class="btn btn-soft-secondary btn-icon btn-circle btn-sm" href="{{ route('warehouses.edit', $warehouse->id) }}" title="{{ translate('Edit') }}">
                                <i class="las la-edit"></i>
                            </a>
                            @endcan
                            @can('delete_warehouse')
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('warehouses.destroy', $warehouse->id) }}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                            @endcan
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
        function sort_products(el){
            $('#sort_products').submit();
        }
    </script>
@endsection
