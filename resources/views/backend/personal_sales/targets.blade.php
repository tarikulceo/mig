@extends('backend.layouts.app')

@section('content')

<!-- Mobile-optimized title section -->
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-12">
            <h1 class="h3 mb-1">{{translate('My Targets')}}</h1>
            <p class="text-muted mb-0 small">{{translate('Track your sales targets and achievement progress')}}</p>
        </div>
    </div>
</div>

<!-- Responsive Target Summary Cards -->
<div class="row gutters-10 mb-3">
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-2">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-primary bg-primary">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4 class="text-truncate">{{translate('Monthly Target')}}</h4>
                </div>
                <div class="card-body">
                    <span class="d-block text-truncate">{{ single_price($salesRep->sales_target_monthly ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-2">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-info bg-info">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4 class="text-truncate">{{translate('Quarterly Target')}}</h4>
                </div>
                <div class="card-body">
                    <span class="d-block text-truncate">{{ single_price($salesRep->sales_target_quarterly ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-2">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-success bg-success">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4 class="text-truncate">{{translate('Yearly Target')}}</h4>
                </div>
                <div class="card-body">
                    <span class="d-block text-truncate">{{ single_price($salesRep->sales_target_yearly ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-2">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-warning bg-warning">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4 class="text-truncate">{{translate('Commission Rate')}}</h4>
                </div>
                <div class="card-body">
                    <span class="d-block">{{ number_format($salesRep->commission_rate ?? 0, 2) }}%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile-optimized Targets Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Detailed Target History') }}</h5>
    </div>
    
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-lg-block">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>{{ translate('Target Period') }}</th>
                        <th>{{ translate('Target Type') }}</th>
                        <th>{{ translate('Target Value') }}</th>
                        <th>{{ translate('Achieved Value') }}</th>
                        <th>{{ translate('Achievement %') }}</th>
                        <th>{{ translate('Period') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Remaining Days') }}</th>
                        <th>{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($targets as $key => $target)
                    <tr>
                        <td>{{ ($key+1) + ($targets->currentPage() - 1) * $targets->perPage() }}</td>
                        <td>
                            <span class="badge badge-inline badge-info">
                                {{ $target->target_period_name }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-inline badge-secondary">
                                {{ $target->target_type_name }}
                            </span>
                        </td>
                        <td>
                            @if($target->target_type == 'revenue')
                                <strong class="text-primary">{{ single_price($target->target_value) }}</strong>
                            @else
                                <strong class="text-primary">{{ number_format($target->target_value) }}</strong>
                            @endif
                        </td>
                        <td>
                            @if($target->target_type == 'revenue')
                                <strong class="text-success">{{ single_price($target->achieved_value) }}</strong>
                            @else
                                <strong class="text-success">{{ number_format($target->achieved_value) }}</strong>
                            @endif
                        </td>
                        <td>
                            @php
                                $percentage = $target->achievement_percentage;
                                $progressColor = 'bg-danger';
                                if($percentage >= 100) $progressColor = 'bg-success';
                                elseif($percentage >= 75) $progressColor = 'bg-warning';
                                elseif($percentage >= 50) $progressColor = 'bg-info';
                            @endphp
                            <div class="progress mb-1" style="height: 8px;">
                                <div class="progress-bar {{ $progressColor }}" 
                                     role="progressbar" 
                                     style="width: {{ min($percentage, 100) }}%" 
                                     aria-valuenow="{{ $percentage }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted">{{ number_format($percentage, 1) }}%</small>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $target->start_date->format('Y-m-d') }}<br>
                                <strong>to</strong><br>
                                {{ $target->end_date->format('Y-m-d') }}
                            </small>
                        </td>
                        <td>
                            @if($target->status == 'active')
                                <span class="badge badge-inline badge-success">
                                    {{ translate('Active') }}
                                </span>
                            @elseif($target->status == 'achieved')
                                <span class="badge badge-inline badge-primary">
                                    {{ translate('Achieved') }}
                                </span>
                            @elseif($target->status == 'failed')
                                <span class="badge badge-inline badge-danger">
                                    {{ translate('Failed') }}
                                </span>
                            @else
                                <span class="badge badge-inline badge-secondary">
                                    {{ $target->status_name }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($target->isActive())
                                <span class="badge badge-inline badge-info">
                                    {{ $target->remaining_days }} {{translate('days')}}
                                </span>
                            @elseif($target->end_date < now())
                                <span class="badge badge-inline badge-secondary">
                                    {{translate('Expired')}}
                                </span>
                            @else
                                <span class="badge badge-inline badge-light">
                                    {{translate('Not Started')}}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-soft-secondary btn-icon btn-circle btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="las la-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="#" onclick="show_target_details({{ $target->id }})">
                                        {{translate('View Details')}}
                                    </a>
                                    @if($target->notes)
                                        <a class="dropdown-item" href="#" onclick="show_target_notes({{ $target->id }})">
                                            {{translate('View Notes')}}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">
                            <div class="py-4">
                                <i class="las la-bullseye la-3x text-muted mb-3"></i>
                                <p class="text-muted">{{ translate('No specific targets assigned yet') }}</p>
                                <p class="small text-muted">{{ translate('Your base targets are shown in the summary cards above') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="d-block d-lg-none">
            @forelse ($targets as $key => $target)
            <div class="border-bottom p-3">
                <div class="row align-items-center mb-2">
                    <div class="col-6">
                        <span class="badge badge-inline badge-info">
                            {{ $target->target_period_name }}
                        </span>
                        <span class="badge badge-inline badge-secondary ml-1">
                            {{ $target->target_type_name }}
                        </span>
                    </div>
                    <div class="col-6 text-right">
                        @if($target->status == 'active')
                            <span class="badge badge-inline badge-success">
                                {{ translate('Active') }}
                            </span>
                        @elseif($target->status == 'achieved')
                            <span class="badge badge-inline badge-primary">
                                {{ translate('Achieved') }}
                            </span>
                        @elseif($target->status == 'failed')
                            <span class="badge badge-inline badge-danger">
                                {{ translate('Failed') }}
                            </span>
                        @else
                            <span class="badge badge-inline badge-secondary">
                                {{ $target->status_name }}
                            </span>
                        @endif
                    </div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-6">
                        <small class="text-muted">{{ translate('Target Value') }}</small>
                        <div class="font-weight-bold text-primary">
                            @if($target->target_type == 'revenue')
                                {{ single_price($target->target_value) }}
                            @else
                                {{ number_format($target->target_value) }}
                            @endif
                        </div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">{{ translate('Achieved Value') }}</small>
                        <div class="font-weight-bold text-success">
                            @if($target->target_type == 'revenue')
                                {{ single_price($target->achieved_value) }}
                            @else
                                {{ number_format($target->achieved_value) }}
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="mb-2">
                    <small class="text-muted">{{ translate('Achievement Progress') }}</small>
                    @php
                        $percentage = $target->achievement_percentage;
                        $progressColor = 'bg-danger';
                        if($percentage >= 100) $progressColor = 'bg-success';
                        elseif($percentage >= 75) $progressColor = 'bg-warning';
                        elseif($percentage >= 50) $progressColor = 'bg-info';
                    @endphp
                    <div class="progress mb-1" style="height: 10px;">
                        <div class="progress-bar {{ $progressColor }}" 
                             role="progressbar" 
                             style="width: {{ min($percentage, 100) }}%" 
                             aria-valuenow="{{ $percentage }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <small class="text-muted">{{ number_format($percentage, 1) }}% {{ translate('completed') }}</small>
                </div>
                
                <div class="row mb-2">
                    <div class="col-6">
                        <small class="text-muted">{{ translate('Period') }}</small>
                        <div class="small">
                            {{ $target->start_date->format('M d, Y') }}<br>
                            {{ translate('to') }} {{ $target->end_date->format('M d, Y') }}
                        </div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">{{ translate('Time Remaining') }}</small>
                        <div>
                            @if($target->isActive())
                                <span class="badge badge-inline badge-info">
                                    {{ $target->remaining_days }} {{translate('days')}}
                                </span>
                            @elseif($target->end_date < now())
                                <span class="badge badge-inline badge-secondary">
                                    {{translate('Expired')}}
                                </span>
                            @else
                                <span class="badge badge-inline badge-light">
                                    {{translate('Not Started')}}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="text-right">
                    <div class="dropdown">
                        <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="las la-ellipsis-v"></i> {{ translate('Actions') }}
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="#" onclick="show_target_details({{ $target->id }})">
                                <i class="las la-eye"></i> {{translate('View Details')}}
                            </a>
                            @if($target->notes)
                                <a class="dropdown-item" href="#" onclick="show_target_notes({{ $target->id }})">
                                    <i class="las la-sticky-note"></i> {{translate('View Notes')}}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-5">
                <i class="las la-bullseye la-3x text-muted mb-3"></i>
                <p class="text-muted">{{ translate('No specific targets assigned yet') }}</p>
                <p class="small text-muted">{{ translate('Your base targets are shown in the summary cards above') }}</p>
            </div>
            @endforelse
        </div>
        
        @if($targets->count() > 0)
        <div class="aiz-pagination mt-3 px-3">
            {{ $targets->appends(request()->input())->links() }}
        </div>
        @endif
    </div>
</div>

@endsection

@section('modal')
<!-- Target Details Modal -->
<div class="modal fade" id="target_details_modal" tabindex="-1" role="dialog" aria-labelledby="target_details_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="target_details_modal_label">{{ translate('Target Details') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="target_details_content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Target Notes Modal -->
<div class="modal fade" id="target_notes_modal" tabindex="-1" role="dialog" aria-labelledby="target_notes_modal_label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="target_notes_modal_label">{{ translate('Target Notes') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="target_notes_content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    function show_target_details(target_id) {
        $('#target_details_modal').modal('show');
        $('#target_details_content').html('<div class="text-center py-4"><i class="las la-spinner la-spin la-2x"></i></div>');
        
        // You can implement this route in the controller if needed
        $.get('{{ route("personal.targets") }}/' + target_id + '/details', function(data) {
            $('#target_details_content').html(data);
        }).fail(function() {
            $('#target_details_content').html('<div class="text-center py-4 text-danger">{{ translate("Error loading target details") }}</div>');
        });
    }
    
    function show_target_notes(target_id) {
        $('#target_notes_modal').modal('show');
        $('#target_notes_content').html('<div class="text-center py-4"><i class="las la-spinner la-spin la-2x"></i></div>');
        
        // You can implement this route in the controller if needed
        $.get('{{ route("personal.targets") }}/' + target_id + '/notes', function(data) {
            $('#target_notes_content').html(data);
        }).fail(function() {
            $('#target_notes_content').html('<div class="text-center py-4 text-danger">{{ translate("Error loading target notes") }}</div>');
        });
    }
</script>
@endsection