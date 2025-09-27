@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('All Sales Activities') }}</h1>
        </div>
        @can('add_sales_activity')
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sales_activities.create') }}" class="btn btn-circle btn-info">
                <span>{{ translate('Log New Activity') }}</span>
            </a>
        </div>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header d-block d-md-flex">
        <h5 class="mb-0 h6">{{ translate('Sales Activities') }}</h5>
        <div class="text-md-right">
            <div class="form-group mb-0">
                <input type="text" class="form-control form-control-sm" id="search" name="search" 
                       @isset($sort_search) value="{{ $sort_search }}" @endisset 
                       placeholder="{{ translate('Type customer name & Enter') }}">
            </div>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Date & Time') }}</th>
                    <th>{{ translate('Sales Rep') }}</th>
                    <th>{{ translate('Customer') }}</th>
                    <th>{{ translate('Activity Type') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Follow Up') }}</th>
                    <th class="text-right">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activities as $key => $activity)
                <tr>
                    <td>{{ ($key+1) + ($activities->currentPage() - 1)*$activities->perPage() }}</td>
                    <td>
                        <span class="d-block">{{ date('M d, Y', strtotime($activity->activity_date)) }}</span>
                        <span class="text-muted small">{{ date('h:i A', strtotime($activity->activity_date)) }}</span>
                    </td>
                    <td>
                        <span class="d-block">{{ $activity->salesRepresentative->user->name }}</span>
                        <span class="text-muted small">{{ $activity->salesRepresentative->employee_id }}</span>
                    </td>
                    <td>
                        @if($activity->customer)
                            <span class="d-block">{{ $activity->customer->name }}</span>
                            <span class="text-muted small">{{ $activity->customer->email }}</span>
                        @else
                            <span class="text-muted">{{ translate('N/A') }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-secondary">{{ ucfirst($activity->activity_type) }}</span>
                    </td>
                    <td>
                        @if($activity->status == 'completed')
                            <span class="badge badge-success">{{ translate('Completed') }}</span>
                        @elseif($activity->status == 'in_progress')
                            <span class="badge badge-inline badge-warning">{{ translate('In Progress') }}</span>
                        @else
                            <span class="badge badge-inline badge-info">{{ translate('Scheduled') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($activity->follow_up_date)
                            <span class="d-block">{{ date('M d, Y', strtotime($activity->follow_up_date)) }}</span>
                            @if($activity->follow_up_date < now())
                                <span class="badge badge-inline badge-danger badge-sm">{{ translate('Overdue') }}</span>
                            @else
                                <span class="badge badge-inline badge-info badge-sm">{{ translate('Upcoming') }}</span>
                            @endif
                        @else
                            <span class="text-muted">{{ translate('None') }}</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @can('view_sales_activity')
                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" 
                           href="{{ route('sales_activities.show', $activity->id) }}" 
                           title="{{ translate('View') }}">
                            <i class="las la-eye"></i>
                        </a>
                        @endcan
                        @can('edit_sales_activity')
                        <a class="btn btn-soft-info btn-icon btn-circle btn-sm" 
                           href="{{ route('sales_activities.edit', $activity->id) }}" 
                           title="{{ translate('Edit') }}">
                            <i class="las la-edit"></i>
                        </a>
                        @endcan
                        @can('delete_sales_activity')
                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" 
                           data-href="{{ route('sales_activities.destroy', $activity->id) }}" 
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
            {{ $activities->appends(request()->input())->links() }}
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
                    window.location.href = '{{ route('sales_activities.index') }}?search=' + value;
                }, 300);
            }
        });
    });
</script>
@endsection
