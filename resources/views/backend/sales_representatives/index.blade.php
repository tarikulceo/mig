@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Sales Representatives')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            @can('add_sales_representative')
                <a href="{{ route('sales_representatives.create') }}" class="btn btn-primary">
                    <span>{{translate('Add Sales Representative')}}</span>
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card">
    <form class="" action="" id="sort_sales_reps" method="GET">
        <div class="card-header row gutters-5">
            <div class="col text-center text-md-left">
                <h5 class="mb-md-0 h6">{{ translate('All Sales Representatives') }}</h5>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control form-control-sm aiz-selectpicker" name="territory" onchange="sort_sales_reps()">
                        <option value="">{{ translate('All Territories') }}</option>
                        @foreach ($territories as $territory)
                            <option value="{{ $territory->id }}" @if($territory_id == $territory->id) selected @endif>
                                {{ $territory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control form-control-sm aiz-selectpicker" name="status" onchange="sort_sales_reps()">
                        <option value="">{{ translate('All Status') }}</option>
                        <option value="1" @if($status == '1') selected @endif>{{ translate('Active') }}</option>
                        <option value="0" @if($status == '0') selected @endif>{{ translate('Inactive') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-0">
                    <input type="text" class="form-control form-control-sm" id="search" name="search" @isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type name, email or employee ID & Enter') }}">
                </div>
            </div>
        </div>
    </form>

    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{translate('Photo')}}</th>
                    <th>{{translate('Name')}}</th>
                    <th>{{translate('Employee ID')}}</th>
                    <th>{{translate('Email')}}</th>
                    <th>{{translate('Territory')}}</th>
                    <th>{{translate('Commission Rate')}}</th>
                    <th>{{translate('Monthly Target')}}</th>
                    <th>{{translate('Status')}}</th>
                    <th class="text-right">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesReps as $key => $rep)
                    <tr>
                        <td>{{ ($key+1) + ($salesReps->currentPage() - 1)*$salesReps->perPage() }}</td>
                        <td>
                            @if($rep->profile_image != null)
                                <img src="{{ uploaded_asset($rep->profile_image) }}" alt="{{translate('Image')}}" class="size-50px img-fit">
                            @else
                                <img src="{{ static_asset('assets/img/avatar-place.png') }}" alt="{{translate('Image')}}" class="size-50px img-fit">
                            @endif
                        </td>
                        <td>
                            <div>
                                <span class="d-block fw-600">{{ $rep->user->name ?? '' }}</span>
                                <span class="d-block opacity-50">{{ $rep->designation }}</span>
                            </div>
                        </td>
                        <td>{{ $rep->employee_id }}</td>
                        <td>{{ $rep->user->email ?? '' }}</td>
                        <td>{{ $rep->territory->name ?? '' }}</td>
                        <td>{{ $rep->commission_rate }}%</td>
                        <td>{{ single_price($rep->sales_target_monthly) }}</td>
                        <td>
                            @if($rep->status == 1)
                                <span class="badge badge-inline badge-success">{{translate('Active')}}</span>
                            @else
                                <span class="badge badge-inline badge-danger">{{translate('Inactive')}}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @can('view_sales_representative')
                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('sales_representatives.show', $rep->id) }}" title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
                            @endcan
                            @can('edit_sales_representative')
                                <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('sales_representatives.edit', $rep->id) }}" title="{{ translate('Edit') }}">
                                    <i class="las la-edit"></i>
                                </a>
                            @endcan
                            @can('delete_sales_representative')
                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('sales_representatives.destroy', $rep->id) }}" title="{{ translate('Delete') }}">
                                    <i class="las la-trash"></i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $salesReps->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script type="text/javascript">
    function sort_sales_reps(){
        $('#sort_sales_reps').submit();
    }
    
    $(document).on('click', '.confirm-delete', function (e) {
        e.preventDefault();
        let url = $(this).data('href');
        $('#delete-link').attr('href', url);
        $('#delete-modal').modal('show');
    });
</script>
@endsection
