@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Sales Activity Details') }}</h1>
        </div>
        <div class="col text-right">
            <a href="{{ route('sales_activities.index') }}" class="btn btn-link text-info">
                <i class="las la-arrow-left"></i>
                {{ translate('Back to list') }}
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Activity Information') }}</h5>
                <div class="text-right">
                    @can('edit_sales_activity')
                    <a href="{{ route('sales_activities.edit', $activity->id) }}" class="btn btn-soft-info btn-sm">
                        <i class="las la-edit"></i> {{ translate('Edit') }}
                    </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Sales Representative') }}</label>
                            <div class="form-control-static">
                                <div class="d-flex align-items-center">
                                    <img class="avatar avatar-sm mr-3" 
                                         src="{{ uploaded_asset($activity->salesRepresentative->user->avatar_original) }}" 
                                         onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                    <div>
                                        <h6 class="mb-0">{{ $activity->salesRepresentative->user->name }}</h6>
                                        <small class="text-muted">{{ $activity->salesRepresentative->employee_id }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Customer') }}</label>
                            <div class="form-control-static">
                                @if($activity->customer)
                                    <div class="d-flex align-items-center">
                                        <img class="avatar avatar-sm mr-3" 
                                             src="{{ uploaded_asset($activity->customer->avatar_original) }}" 
                                             onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                        <div>
                                            <h6 class="mb-0">{{ $activity->customer->name }}</h6>
                                            <small class="text-muted">{{ $activity->customer->email }}</small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">{{ translate('No specific customer') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Activity Type') }}</label>
                            <div class="form-control-static">
                                <span class="badge badge-secondary badge-lg">{{ ucfirst($activity->activity_type) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Status') }}</label>
                            <div class="form-control-static">
                                @if($activity->status == 'completed')
                                    <span class="badge badge-success badge-lg">{{ translate('Completed') }}</span>
                                @elseif($activity->status == 'in_progress')
                                    <span class="badge badge-inline badge-warning badge-lg">{{ translate('In Progress') }}</span>
                                @else
                                    <span class="badge badge-inline badge-info badge-lg">{{ translate('Scheduled') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Activity Date & Time') }}</label>
                            <div class="form-control-static">
                                <strong>{{ date('M d, Y', strtotime($activity->activity_date)) }}</strong>
                                <span class="text-muted">at {{ date('h:i A', strtotime($activity->activity_date)) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Follow Up Date') }}</label>
                            <div class="form-control-static">
                                @if($activity->follow_up_date)
                                    <strong>{{ date('M d, Y', strtotime($activity->follow_up_date)) }}</strong>
                                    <span class="text-muted">at {{ date('h:i A', strtotime($activity->follow_up_date)) }}</span>
                                    @if($activity->follow_up_date < now())
                                        <br><span class="badge badge-danger">{{ translate('Overdue') }}</span>
                                    @else
                                        <br><span class="badge badge-info">{{ translate('Upcoming') }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">{{ translate('No follow up scheduled') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ translate('Activity Description') }}</label>
                    <div class="form-control-static">
                        <p>{{ $activity->description }}</p>
                    </div>
                </div>

                @if($activity->outcome)
                <div class="form-group">
                    <label class="form-label">{{ translate('Outcome / Result') }}</label>
                    <div class="form-control-static">
                        <p>{{ $activity->outcome }}</p>
                    </div>
                </div>
                @endif

                @if($activity->notes)
                <div class="form-group">
                    <label class="form-label">{{ translate('Additional Notes') }}</label>
                    <div class="form-control-static">
                        <p>{{ $activity->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Activity Timeline') }}</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">{{ translate('Activity Created') }}</h6>
                            <p class="timeline-text">{{ $activity->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                    
                    @if($activity->status == 'completed')
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">{{ translate('Activity Completed') }}</h6>
                            <p class="timeline-text">{{ $activity->updated_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($activity->follow_up_date)
                    <div class="timeline-item">
                        <div class="timeline-marker {{ $activity->follow_up_date < now() ? 'bg-danger' : 'bg-warning' }}"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">{{ translate('Follow Up') }}</h6>
                            <p class="timeline-text">{{ date('M d, Y h:i A', strtotime($activity->follow_up_date)) }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($activity->customer)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Customer Quick Info') }}</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img class="avatar avatar-md" 
                         src="{{ uploaded_asset($activity->customer->avatar_original) }}" 
                         onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                    <h6 class="mt-2 mb-0">{{ $activity->customer->name }}</h6>
                    <small class="text-muted">{{ $activity->customer->email }}</small>
                </div>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-muted mb-1">{{ translate('Total Orders') }}</h6>
                        <h5>{{ $activity->customer->orders->count() ?? 0 }}</h5>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted mb-1">{{ translate('Member Since') }}</h6>
                        <h6>{{ $activity->customer->created_at->format('M Y') }}</h6>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@section('style')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-content {
    padding-left: 15px;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 14px;
}

.timeline-text {
    margin-bottom: 0;
    font-size: 12px;
    color: #6c757d;
}
</style>
@endsection
