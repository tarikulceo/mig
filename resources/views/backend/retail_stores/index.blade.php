@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Retail Stores')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            @if(auth()->user()->user_type === 'sales_rep' || auth()->user()->can('add_retail_store'))
                <a href="{{ route('retail_stores.create') }}" class="btn btn-primary">
                    <span>{{translate('Add New Store')}}</span>
                </a>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <form class="" action="" id="sort_stores" method="GET">
        <div class="card-header row gutters-5">
            <div class="col text-center text-md-left">
                <h5 class="mb-md-0 h6">{{ translate('All Retail Stores') }}</h5>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <input type="text" class="form-control" id="search" name="search" @isset($search) value="{{ $search }}" @endisset placeholder="{{ translate('Type & Enter') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control aiz-selectpicker" name="status" onchange="sort_stores()">
                        <option value="">{{ translate('All Status') }}</option>
                        <option value="active" @isset($status) @if($status == 'active') selected @endif @endisset>{{ translate('Active') }}</option>
                        <option value="inactive" @isset($status) @if($status == 'inactive') selected @endif @endisset>{{ translate('Inactive') }}</option>
                        <option value="pending" @isset($status) @if($status == 'pending') selected @endif @endisset>{{ translate('Pending') }}</option>
                    </select>
                </div>
            </div>
            @if(auth()->user()->user_type !== 'sales_rep')
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <select class="form-control aiz-selectpicker" name="territory_id" onchange="sort_stores()">
                            <option value="">{{ translate('All Territories') }}</option>
                            @foreach($territories as $territory)
                                <option value="{{ $territory->id }}" @isset($territory_id) @if($territory_id == $territory->id) selected @endif @endisset>
                                    {{ $territory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <select class="form-control aiz-selectpicker" name="sales_rep_id" onchange="sort_stores()">
                            <option value="">{{ translate('All Sales Reps') }}</option>
                            @foreach($salesReps as $rep)
                                <option value="{{ $rep->id }}" @isset($sales_rep_id) @if($sales_rep_id == $rep->id) selected @endif @endisset>
                                    {{ $rep->user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
        </div>
    </form>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th data-breakpoints="lg">#</th>
                    <th>{{translate('Store Info')}}</th>
                    <th data-breakpoints="md">{{translate('Location')}}</th>
                    <th data-breakpoints="md">{{translate('Sales Rep')}}</th>
                    <th data-breakpoints="lg">{{translate('Type')}}</th>
                    <th data-breakpoints="lg">{{translate('Target')}}</th>
                    <th>{{translate('Status')}}</th>
                    <th data-breakpoints="lg">{{translate('Created')}}</th>
                    <th width="10%">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stores as $key => $store)
                    <tr>
                        <td>{{ ($key+1) + ($stores->currentPage() - 1)*$stores->perPage() }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($store->image)
                                    <img src="{{ uploaded_asset($store->image) }}" alt="Store Image" class="size-50px rounded mr-2">
                                @else
                                    <div class="size-50px rounded mr-2 bg-soft-primary d-flex align-items-center justify-content-center">
                                        <i class="las la-store text-primary"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fs-14 fw-600">{{ $store->name }}</div>
                                    <div class="fs-12 opacity-60">{{ $store->store_code }}</div>
                                    @if($store->owner_name)
                                        <div class="fs-12 opacity-60">{{ $store->owner_name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fs-13">
                                {{ $store->city }}, {{ $store->country }}
                                @if($store->latitude && $store->longitude)
                                    <a href="{{ $store->map_url }}" target="_blank" class="btn btn-xs btn-info ml-1">
                                        <i class="las la-map-marker-alt"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($store->salesRepresentative)
                                <div class="fs-13">{{ $store->salesRepresentative->user->name }}</div>
                                <div class="fs-12 opacity-60">{{ $store->salesRepresentative->employee_id }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-inline badge-secondary">{{ ucfirst($store->store_type) }}</span>
                        </td>
                        <td>
                            <div class="fs-13">Monthly: {{ single_price($store->monthly_target) }}</div>
                            <div class="fs-12 opacity-60">Yearly: {{ single_price($store->yearly_target) }}</div>
                        </td>
                        <td>
                            <span class="badge badge-inline badge-{{ $store->status_badge }}">{{ ucfirst($store->status) }}</span>
                        </td>
                        <td>
                            <div class="fs-13">{{ $store->created_at->format('M d, Y') }}</div>
                        </td>
                        <td class="text-right">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('retail_stores.show', $store->id) }}" title="{{ translate('View') }}">
                                <i class="las la-eye"></i>
                            </a>
                            @if(auth()->user()->user_type === 'sales_rep' || auth()->user()->can('edit_retail_store'))
                                <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('retail_stores.edit', $store->id) }}" title="{{ translate('Edit') }}">
                                    <i class="las la-edit"></i>
                                </a>
                            @endif
                            @if(auth()->user()->user_type === 'sales_rep' || auth()->user()->can('delete_retail_store'))
                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('retail_stores.destroy', $store->id) }}" title="{{ translate('Delete') }}">
                                    <i class="las la-trash"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $stores->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script type="text/javascript">
        function sort_stores(el) {
            $('#sort_stores').submit();
        }
        
        $('#search').on('keyup', function(){
            if($(this).val().length > 0 || $(this).val().length == 0) {
                $('#sort_stores').submit();
            }
        });
    </script>
@endsection