@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('All Sales Targets') }}</h1>
        </div>
        @can('add_sales_target')
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sales_targets.create') }}" class="btn btn-circle btn-info">
                <span>{{ translate('Add New Target') }}</span>
            </a>
        </div>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header d-block d-md-flex">
        <h5 class="mb-0 h6">{{ translate('Sales Targets') }}</h5>
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
                    <th>{{ translate('Period') }}</th>
                    <th>{{ translate('Target Amount') }}</th>
                    <th>{{ translate('Achieved Amount') }}</th>
                    <th>{{ translate('Achievement %') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th class="text-right">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($targets as $key => $target)
                <tr>
                    <td>{{ ($key+1) + ($targets->currentPage() - 1)*$targets->perPage() }}</td>
                    <td>
                        <span class="d-block">{{ $target->salesRepresentative->user->name }}</span>
                        <span class="text-muted small">{{ $target->salesRepresentative->employee_id }}</span>
                    </td>
                    <td>
                        <span class="d-block">{{ $target->period }}</span>
                        <span class="text-muted small">{{ date('M Y', strtotime($target->start_date)) }} - {{ date('M Y', strtotime($target->end_date)) }}</span>
                    </td>
                    <td>{{ format_price($target->target_amount) }}</td>
                    <td>{{ format_price($target->achieved_amount) }}</td>
                    <td>
                        @php
                            $percentage = $target->target_amount > 0 ? ($target->achieved_amount / $target->target_amount) * 100 : 0;
                        @endphp
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar 
                                @if($percentage >= 100) bg-success 
                                @elseif($percentage >= 75) bg-info 
                                @elseif($percentage >= 50) bg-warning 
                                @else bg-danger @endif" 
                                role="progressbar" style="width: {{ min($percentage, 100) }}%">
                                {{ number_format($percentage, 1) }}%
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($target->status == 'active')
                            <span class="badge badge-inline badge-success">{{ translate('Active') }}</span>
                        @else
                            <span class="badge badge-inline badge-secondary">{{ translate('Inactive') }}</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @can('view_sales_target')
                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" 
                           href="{{ route('sales_targets.show', $target->id) }}" 
                           title="{{ translate('View') }}">
                            <i class="las la-eye"></i>
                        </a>
                        @endcan
                        @can('edit_sales_target')
                        <a class="btn btn-soft-info btn-icon btn-circle btn-sm" 
                           href="{{ route('sales_targets.edit', $target->id) }}" 
                           title="{{ translate('Edit') }}">
                            <i class="las la-edit"></i>
                        </a>
                        @endcan
                        @can('delete_sales_target')
                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" 
                           data-href="{{ route('sales_targets.destroy', $target->id) }}" 
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
            {{ $targets->appends(request()->input())->links() }}
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
                $('#targets-table').hide();
                $('#loading').show();
                setTimeout(function() {
                    window.location.href = '{{ route('sales_targets.index') }}?search=' + value;
                }, 300);
            }
        });
    });
</script>
@endsection
