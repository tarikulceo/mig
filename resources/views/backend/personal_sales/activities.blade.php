@extends('backend.layouts.app')

@section('content')

<!-- Title section -->
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('My Activities')}}</h1>
        </div>
    </div>
</div>

<!-- Activities Filter and Table -->
<div class="card">
    <form class="" action="{{ route('personal.activities') }}" id="sort_activities" method="GET">
        <div class="card-header row gutters-5">
            <div class="col text-center text-md-left">
                <h5 class="mb-0 h6">{{ translate('My Activity History') }}</h5>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <input type="text" class="form-control form-control-sm" id="search" name="search" @isset($search) value="{{ $search }}" @endisset placeholder="{{ translate('Search activities...') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control form-control-sm aiz-selectpicker" name="activity_type" onchange="sort_activities()">
                        <option value="">{{ translate('All Types') }}</option>
                        @foreach(\App\Models\SalesActivity::ACTIVITY_TYPES as $key => $type)
                            <option value="{{ $key }}" {{ request('activity_type') == $key ? 'selected' : '' }}>
                                {{ translate($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control form-control-sm aiz-selectpicker" name="status" onchange="sort_activities()">
                        <option value="">{{ translate('All Status') }}</option>
                        @foreach(\App\Models\SalesActivity::ACTIVITY_STATUS as $key => $status)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                {{ translate($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control form-control-sm aiz-selectpicker" name="priority" onchange="sort_activities()">
                        <option value="">{{ translate('All Priorities') }}</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>{{ translate('Urgent') }}</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>{{ translate('High') }}</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>{{ translate('Medium') }}</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>{{ translate('Low') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </form>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{translate('Activity Info')}}</th>
                    <th>{{translate('Customer')}}</th>
                    <th>{{translate('Date & Time')}}</th>
                    <th>{{translate('Priority')}}</th>
                    <th>{{translate('Status')}}</th>
                    <th>{{translate('Follow Up')}}</th>
                    <th width="10%">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $key => $activity)
                <tr>
                    <td>{{ ($key+1) + ($activities->currentPage() - 1) * $activities->perPage() }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="size-40px rounded mr-2 bg-soft-info d-flex align-items-center justify-content-center">
                                @if($activity->activity_type == 'call')
                                    <i class="las la-phone text-info"></i>
                                @elseif($activity->activity_type == 'meeting')
                                    <i class="las la-users text-info"></i>
                                @elseif($activity->activity_type == 'email')
                                    <i class="las la-envelope text-info"></i>
                                @elseif($activity->activity_type == 'visit')
                                    <i class="las la-map-marker-alt text-info"></i>
                                @else
                                    <i class="las la-clipboard-list text-info"></i>
                                @endif
                            </div>
                            <div>
                                <div class="fs-14 fw-600">{{ $activity->subject }}</div>
                                <div class="fs-12 opacity-60">{{ $activity->activity_type_name }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($activity->customer && $activity->customer->user)
                            <div class="fs-13">{{ $activity->customer->user->name }}</div>
                            <div class="fs-12 opacity-60">{{ $activity->customer->user->email ?? 'N/A' }}</div>
                        @else
                            <div class="text-muted fs-13">{{ translate('N/A') }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="fs-13">{{ $activity->activity_date->format('M d, Y') }}</div>
                        <div class="fs-12 opacity-60">{{ $activity->activity_date->format('h:i A') }}</div>
                    </td>
                    <td>
                        @if($activity->priority == 'urgent')
                            <span class="badge badge-inline badge-danger">{{ translate('Urgent') }}</span>
                        @elseif($activity->priority == 'high')
                            <span class="badge badge-inline badge-warning">{{ translate('High') }}</span>
                        @elseif($activity->priority == 'medium')
                            <span class="badge badge-inline badge-info">{{ translate('Medium') }}</span>
                        @else
                            <span class="badge badge-inline badge-secondary">{{ translate('Low') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($activity->status == 'completed')
                            <span class="badge badge-inline badge-success">{{ translate('Completed') }}</span>
                        @elseif($activity->status == 'in_progress')
                            <span class="badge badge-inline badge-warning">{{ translate('In Progress') }}</span>
                        @elseif($activity->status == 'scheduled')
                            <span class="badge badge-inline badge-info">{{ translate('Scheduled') }}</span>
                        @else
                            <span class="badge badge-inline badge-secondary">{{ $activity->status_name }}</span>
                        @endif
                        
                        @if($activity->isOverdue())
                            <div class="mt-1">
                                <span class="badge badge-inline badge-danger badge-sm">{{ translate('Overdue') }}</span>
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($activity->follow_up_date)
                            <div class="fs-13">{{ $activity->follow_up_date->format('M d, Y') }}</div>
                            @if($activity->isFollowUpDue())
                                <div class="fs-12 text-warning">{{ translate('Follow-up Due') }}</div>
                            @endif
                        @else
                            <span class="text-muted fs-13">{{ translate('No Follow-up') }}</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-icon btn-circle btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="las la-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="#" onclick="show_activity_details({{ $activity->id }})">
                                    <i class="las la-eye"></i> {{translate('View Details')}}
                                </a>
                                @if($activity->outcome)
                                    <a class="dropdown-item" href="#" onclick="show_activity_outcome({{ $activity->id }})">
                                        <i class="las la-clipboard-check"></i> {{translate('View Outcome')}}
                                    </a>
                                @endif
                                @if($activity->notes)
                                    <a class="dropdown-item" href="#" onclick="show_activity_notes({{ $activity->id }})">
                                        <i class="las la-sticky-note"></i> {{translate('View Notes')}}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="py-4">
                            <i class="las la-calendar-check la-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ translate('No activities found') }}</p>
                            <p class="small text-muted">{{ translate('Start logging your sales activities to track customer interactions') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $activities->appends(request()->input())->links() }}
        </div>
    </div>
</div>

<!-- Activity Summary Cards -->
@if($activities->count() > 0)
<div class="row mt-4">
    <div class="col-lg-3 col-md-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-success bg-success">
                <i class="las la-check-circle"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('Completed')}}</h4>
                </div>
                <div class="card-body">
                    {{ $activities->where('status', 'completed')->count() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-warning bg-warning">
                <i class="las la-clock"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('In Progress')}}</h4>
                </div>
                <div class="card-body">
                    {{ $activities->where('status', 'in_progress')->count() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-info bg-info">
                <i class="las la-calendar-alt"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('Scheduled')}}</h4>
                </div>
                <div class="card-body">
                    {{ $activities->where('status', 'scheduled')->count() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-danger bg-danger">
                <i class="las la-exclamation-triangle"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('Overdue')}}</h4>
                </div>
                <div class="card-body">
                    {{ $activities->filter(function($activity) { return $activity->isOverdue(); })->count() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('modal')
<!-- Activity Details Modal -->
<div class="modal fade" id="activity_details_modal" tabindex="-1" role="dialog" aria-labelledby="activity_details_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activity_details_modal_label">{{ translate('Activity Details') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="activity_details_content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Activity Outcome Modal -->
<div class="modal fade" id="activity_outcome_modal" tabindex="-1" role="dialog" aria-labelledby="activity_outcome_modal_label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activity_outcome_modal_label">{{ translate('Activity Outcome') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="activity_outcome_content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Activity Notes Modal -->
<div class="modal fade" id="activity_notes_modal" tabindex="-1" role="dialog" aria-labelledby="activity_notes_modal_label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activity_notes_modal_label">{{ translate('Activity Notes') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="activity_notes_content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    function sort_activities(el) {
        $('#sort_activities').submit();
    }
    
    $('#search').on('keyup', function(){
        if($(this).val().length > 0 || $(this).val().length == 0) {
            $('#sort_activities').submit();
        }
    });

    function show_activity_details(activity_id) {
        $('#activity_details_modal').modal('show');
        $('#activity_details_content').html('<div class="text-center py-4"><i class="las la-spinner la-spin la-2x"></i></div>');
        
        $.get('{{ route("personal.activities") }}/' + activity_id + '/details', function(data) {
            $('#activity_details_content').html(data);
        }).fail(function() {
            $('#activity_details_content').html('<div class="text-center py-4 text-danger">{{ translate("Error loading activity details") }}</div>');
        });
    }
    
    function show_activity_outcome(activity_id) {
        $('#activity_outcome_modal').modal('show');
        $('#activity_outcome_content').html('<div class="text-center py-4"><i class="las la-spinner la-spin la-2x"></i></div>');
        
        $.get('{{ route("personal.activities") }}/' + activity_id + '/outcome', function(data) {
            $('#activity_outcome_content').html(data);
        }).fail(function() {
            $('#activity_outcome_content').html('<div class="text-center py-4 text-danger">{{ translate("Error loading activity outcome") }}</div>');
        });
    }
    
    function show_activity_notes(activity_id) {
        $('#activity_notes_modal').modal('show');
        $('#activity_notes_content').html('<div class="text-center py-4"><i class="las la-spinner la-spin la-2x"></i></div>');
        
        $.get('{{ route("personal.activities") }}/' + activity_id + '/notes', function(data) {
            $('#activity_notes_content').html(data);
        }).fail(function() {
            $('#activity_notes_content').html('<div class="text-center py-4 text-danger">{{ translate("Error loading activity notes") }}</div>');
        });
    }
</script>
@endsection