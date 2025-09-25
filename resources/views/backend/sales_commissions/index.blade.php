@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('All Sales Commissions') }}</h1>
        </div>
        @can('add_sales_commission')
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sales_commissions.create') }}" class="btn btn-circle btn-info">
                <span>{{ translate('Add New Commission') }}</span>
            </a>
        </div>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header d-block d-md-flex">
        <h5 class="mb-0 h6">{{ translate('Sales Commissions') }}</h5>
        <div class="text-md-right">
            <div class="form-group mb-0">
                <input type="text" class="form-control form-control-sm" id="search" name="search" 
                       @isset($sort_search) value="{{ $sort_search }}" @endisset 
                       placeholder="{{ translate('Type sales rep name & Enter') }}">
            </div>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Sales Representative') }}</th>
                    <th>{{ translate('Order') }}</th>
                    <th>{{ translate('Order Amount') }}</th>
                    <th>{{ translate('Commission Rate') }}</th>
                    <th>{{ translate('Commission Amount') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Date') }}</th>
                    <th class="text-right">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($commissions as $key => $commission)
                <tr>
                    <td>{{ ($key+1) + ($commissions->currentPage() - 1)*$commissions->perPage() }}</td>
                    <td>
                        <span class="d-block">{{ $commission->salesRepresentative->user->name }}</span>
                        <span class="text-muted small">{{ $commission->salesRepresentative->employee_id }}</span>
                    </td>
                    <td>
                        @if($commission->order)
                            <a href="{{ route('orders.show', $commission->order->id) }}" class="text-info">
                                #{{ $commission->order->code }}
                            </a>
                        @else
                            <span class="text-muted">{{ translate('Manual Entry') }}</span>
                        @endif
                    </td>
                    <td>{{ format_price($commission->order_amount) }}</td>
                    <td>{{ $commission->commission_rate }}%</td>
                    <td>
                        <strong class="text-success">{{ format_price($commission->commission_amount) }}</strong>
                    </td>
                    <td>
                        @if($commission->status == 'paid')
                            <span class="badge badge-success">{{ translate('Paid') }}</span>
                        @elseif($commission->status == 'pending')
                            <span class="badge badge-warning">{{ translate('Pending') }}</span>
                        @else
                            <span class="badge badge-secondary">{{ translate('Cancelled') }}</span>
                        @endif
                    </td>
                    <td>{{ date('M d, Y', strtotime($commission->created_at)) }}</td>
                    <td class="text-right">
                        @can('view_sales_commission')
                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" 
                           href="{{ route('sales_commissions.show', $commission->id) }}" 
                           title="{{ translate('View') }}">
                            <i class="las la-eye"></i>
                        </a>
                        @endcan
                        @can('edit_sales_commission')
                        <a class="btn btn-soft-info btn-icon btn-circle btn-sm" 
                           href="{{ route('sales_commissions.edit', $commission->id) }}" 
                           title="{{ translate('Edit') }}">
                            <i class="las la-edit"></i>
                        </a>
                        @endcan
                        @can('delete_sales_commission')
                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" 
                           data-href="{{ route('sales_commissions.destroy', $commission->id) }}" 
                           title="{{ translate('Delete') }}">
                            <i class="las la-trash"></i>
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $commissions->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@include('modals.delete_modal')

@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#search').on('keyup', function() {
            var value = $(this).val();
            if (value.length > 2 || value.length == 0) {
                setTimeout(function() {
                    window.location.href = '{{ route('sales_commissions.index') }}?search=' + value;
                }, 300);
            }
        });
    });
</script>
@endsection
