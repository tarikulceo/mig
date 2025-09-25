@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Sales Territories')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            @can('manage_sales_territories')
                <a href="{{ route('sales_territories.create') }}" class="btn btn-primary">
                    <span>{{translate('Add New Territory')}}</span>
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-block d-md-flex">
        <h5 class="mb-0 h6">{{ translate('All Territories') }}</h5>
        <div class="col-md-3 ml-auto">
            <form class="" id="sort_territories" action="" method="GET">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="search" name="search" @isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type name & Enter') }}">
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
                    <th>{{translate('Region')}}</th>
                    <th>{{translate('Country')}}</th>
                    <th>{{translate('State')}}</th>
                    <th>{{translate('Cities')}}</th>
                    <th>{{translate('Sales Reps')}}</th>
                    <th>{{translate('Status')}}</th>
                    <th class="text-right">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($territories as $key => $territory)
                    <tr>
                        <td>{{ ($key+1) + ($territories->currentPage() - 1)*$territories->perPage() }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div>
                                    <h6 class="mb-0">{{ $territory->name }}</h6>
                                    @if($territory->description)
                                        <small class="text-muted">{{ Str::limit($territory->description, 50) }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $territory->region ?? 'N/A' }}</td>
                        <td>{{ $territory->country->name ?? 'N/A' }}</td>
                        <td>{{ $territory->state->name ?? 'N/A' }}</td>
                        <td>
                            @if($territory->cities)
                                @if(is_string($territory->cities))
                                    {{ count(explode(',', $territory->cities)) }} cities
                                @elseif(is_array($territory->cities))
                                    {{ count($territory->cities) }} cities
                                @else
                                    1 city
                                @endif
                            @else
                                All cities
                            @endif
                        </td>
                        <td>{{ $territory->salesRepresentatives->count() }}</td>
                        <td>
                            @if($territory->is_active)
                                <span class="badge badge-inline badge-success">{{translate('Active')}}</span>
                            @else
                                <span class="badge badge-inline badge-danger">{{translate('Inactive')}}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @can('manage_sales_territories')
                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('sales_territories.show', $territory->id) }}" title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
                                
                                <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('sales_territories.edit', $territory->id) }}" title="{{ translate('Edit') }}">
                                    <i class="las la-edit"></i>
                                </a>
                                
                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('sales_territories.destroy', $territory->id) }}" title="{{ translate('Delete') }}">
                                    <i class="las la-trash"></i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $territories->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script type="text/javascript">
    function sort_territories(){
        $('#sort_territories').submit();
    }
    
    $(document).on('click', '.confirm-delete', function (e) {
        e.preventDefault();
        let url = $(this).data('href');
        $('#delete-link').attr('href', url);
        $('#delete-modal').modal('show');
    });
    
    $('#search').on('keyup', function(){
        delay(function(){
            sort_territories();
        }, 1000);
    });
    
    var delay = (function(){
        var timer = 0;
        return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        };
    })();
</script>
@endsection
