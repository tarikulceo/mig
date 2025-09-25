@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Sales Target Details') }}</h1>
        </div>
        <div class="col text-right">
            <a href="{{ route('sales_targets.index') }}" class="btn btn-link text-info">
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
                <h5 class="mb-0 h6">{{ translate('Target Information') }}</h5>
                <div class="text-right">
                    @can('edit_sales_target')
                    <a href="{{ route('sales_targets.edit', $target->id) }}" class="btn btn-soft-info btn-sm">
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
                                         src="{{ uploaded_asset($target->salesRepresentative->user->avatar_original) }}" 
                                         onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                    <div>
                                        <h6 class="mb-0">{{ $target->salesRepresentative->user->name }}</h6>
                                        <small class="text-muted">{{ $target->salesRepresentative->employee_id }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Period') }}</label>
                            <div class="form-control-static">
                                <span class="badge badge-primary badge-lg">{{ ucfirst($target->period) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Start Date') }}</label>
                            <div class="form-control-static">{{ date('M d, Y', strtotime($target->start_date)) }}</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('End Date') }}</label>
                            <div class="form-control-static">{{ date('M d, Y', strtotime($target->end_date)) }}</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Target Amount') }}</label>
                            <div class="form-control-static">
                                <h4 class="text-primary">{{ format_price($target->target_amount) }}</h4>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Status') }}</label>
                            <div class="form-control-static">
                                @if($target->status == 'active')
                                    <span class="badge badge-success">{{ translate('Active') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ translate('Inactive') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if($target->description)
                <div class="form-group">
                    <label class="form-label">{{ translate('Description') }}</label>
                    <div class="form-control-static">{{ $target->description }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Achievement Progress') }}</h5>
            </div>
            <div class="card-body text-center">
                @php
                    $percentage = $target->target_amount > 0 ? ($target->achieved_amount / $target->target_amount) * 100 : 0;
                @endphp
                
                <div class="mb-3">
                    <div class="progress progress-lg">
                        <div class="progress-bar 
                            @if($percentage >= 100) bg-success 
                            @elseif($percentage >= 75) bg-info 
                            @elseif($percentage >= 50) bg-warning 
                            @else bg-danger @endif" 
                            role="progressbar" style="width: {{ min($percentage, 100) }}%">
                        </div>
                    </div>
                    <h2 class="mt-2 mb-0 
                        @if($percentage >= 100) text-success 
                        @elseif($percentage >= 75) text-info 
                        @elseif($percentage >= 50) text-warning 
                        @else text-danger @endif">
                        {{ number_format($percentage, 1) }}%
                    </h2>
                </div>

                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-muted mb-1">{{ translate('Achieved') }}</h6>
                        <h5 class="text-success">{{ format_price($target->achieved_amount) }}</h5>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted mb-1">{{ translate('Remaining') }}</h6>
                        <h5 class="text-danger">{{ format_price($target->target_amount - $target->achieved_amount) }}</h5>
                    </div>
                </div>

                @php
                    $daysTotal = \Carbon\Carbon::parse($target->start_date)->diffInDays(\Carbon\Carbon::parse($target->end_date));
                    $daysLeft = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($target->end_date), false);
                @endphp

                <hr>
                <div class="text-center">
                    <h6 class="text-muted mb-1">{{ translate('Days Left') }}</h6>
                    <h4 class="{{ $daysLeft > 0 ? 'text-info' : 'text-danger' }}">
                        {{ $daysLeft > 0 ? $daysLeft : 0 }} {{ translate('days') }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Quick Stats') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 text-center">
                        <h6 class="text-muted mb-1">{{ translate('Created') }}</h6>
                        <small>{{ $target->created_at->format('M d, Y') }}</small>
                    </div>
                    <div class="col-6 text-center">
                        <h6 class="text-muted mb-1">{{ translate('Updated') }}</h6>
                        <small>{{ $target->updated_at->format('M d, Y') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
