@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Store Visit Details')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('store_visits.index') }}" class="btn btn-secondary">
                <span>{{translate('Back to Visits')}}</span>
            </a>
            @if(auth()->user()->user_type === 'admin' || auth()->user()->user_type === 'staff')
                <a href="{{ route('store_visits.edit', $visit->id) }}" class="btn btn-primary ml-1">
                    <span>{{translate('Edit Visit')}}</span>
                </a>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Visit Information Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Visit Information')}}</h5>
                <div class="pull-right">
                    @if($visit->visit_status == 'completed')
                        <span class="badge badge-inline badge-success">{{translate('Completed')}}</span>
                    @elseif($visit->visit_status == 'scheduled')
                        <span class="badge badge-inline badge-info">{{translate('Scheduled')}}</span>
                    @elseif($visit->visit_status == 'cancelled')
                        <span class="badge badge-inline badge-danger">{{translate('Cancelled')}}</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th width="30%">{{translate('Visit Date')}}:</th>
                            <td>{{ $visit->visit_date ? $visit->visit_date->format('F d, Y') : 'N/A' }}</td>
                        </tr>
                        @if($visit->scheduled_time)
                        <tr>
                            <th>{{translate('Scheduled Time')}}:</th>
                            <td>{{ \Carbon\Carbon::parse($visit->scheduled_time)->format('h:i A') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>{{translate('Purpose')}}:</th>
                            <td>{{ $visit->purpose ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>{{translate('Status')}}:</th>
                            <td>
                                @if($visit->visit_status == 'completed')
                                    <span class="badge badge-inline badge-success">{{translate('Completed')}}</span>
                                @elseif($visit->visit_status == 'scheduled')
                                    <span class="badge badge-inline badge-info">{{translate('Scheduled')}}</span>
                                @elseif($visit->visit_status == 'cancelled')
                                    <span class="badge badge-inline badge-danger">{{translate('Cancelled')}}</span>
                                @endif
                            </td>
                        </tr>
                        @if($visit->check_in_time)
                        <tr>
                            <th>{{translate('Check In Time')}}:</th>
                            <td>{{ \Carbon\Carbon::parse($visit->check_in_time)->format('h:i A') }}</td>
                        </tr>
                        @endif
                        @if($visit->check_out_time)
                        <tr>
                            <th>{{translate('Check Out Time')}}:</th>
                            <td>{{ \Carbon\Carbon::parse($visit->check_out_time)->format('h:i A') }}</td>
                        </tr>
                        @endif
                        @if($visit->order_amount > 0)
                        <tr>
                            <th>{{translate('Order Amount')}}:</th>
                            <td><strong class="text-success">${{ number_format($visit->order_amount, 2) }}</strong></td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Visit Notes -->
        @if($visit->notes)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Visit Notes')}}</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $visit->notes }}</p>
            </div>
        </div>
        @endif

        <!-- Feedback -->
        @if($visit->feedback)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Customer Feedback')}}</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $visit->feedback }}</p>
            </div>
        </div>
        @endif

        <!-- Next Action -->
        @if($visit->next_action)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Next Action Required')}}</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $visit->next_action }}</p>
                @if($visit->next_visit_date)
                    <small class="text-muted">
                        {{translate('Next Visit')}} : {{ $visit->next_visit_date->format('F d, Y') }}
                    </small>
                @endif
            </div>
        </div>
        @endif

        <!-- Products Discussed -->
        @if($visit->products_discussed && count($visit->products_discussed) > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Products Discussed')}}</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    @foreach($visit->products_discussed as $product)
                        <li>{{ $product }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <!-- Photos -->
        @if($visit->photos && count($visit->photos) > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Visit Photos')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($visit->photos as $photo)
                        <div class="col-md-4 mb-3">
                            <img src="{{ uploaded_asset($photo) }}" class="img-fluid rounded" alt="Visit Photo">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <!-- Store Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Store Information')}}</h5>
            </div>
            <div class="card-body">
                @php
                    $store = $visit->retailStore ?? \App\Models\RetailStore::find($visit->retail_store_id);
                @endphp
                @if($store)
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th width="40%">{{translate('Store Name')}}:</th>
                                <td>{{ $store->name }}</td>
                            </tr>
                            <tr>
                                <th>{{translate('Store Code')}}:</th>
                                <td>{{ $store->store_code }}</td>
                            </tr>
                            @if($store->phone)
                            <tr>
                                <th>{{translate('Phone')}}:</th>
                                <td>{{ $store->phone }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>{{translate('Address')}}:</th>
                                <td>{{ $store->address }}, {{ $store->city }}</td>
                            </tr>
                            <tr>
                                <th>{{translate('Store Type')}}:</th>
                                <td>{{ ucfirst($store->store_type ?? 'retail') }}</td>
                            </tr>
                        </tbody>
                    </table>
                @else
                    <p class="text-muted">{{translate('Store information not available')}}</p>
                @endif
            </div>
        </div>

        <!-- Sales Representative -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Sales Representative')}}</h5>
            </div>
            <div class="card-body">
                @php
                    $salesRep = $visit->salesRepresentative ?? \App\Models\SalesRepresentative::find($visit->sales_rep_id);
                    $user = $salesRep ? \App\Models\User::find($salesRep->user_id) : null;
                @endphp
                @if($salesRep && $user)
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th width="40%">{{translate('Name')}}:</th>
                                <td>{{ $user->name }}</td>
                            </tr>
                            <tr>
                                <th>{{translate('Employee ID')}}:</th>
                                <td>{{ $salesRep->employee_id }}</td>
                            </tr>
                            @if($user->email)
                            <tr>
                                <th>{{translate('Email')}}:</th>
                                <td>{{ $user->email }}</td>
                            </tr>
                            @endif
                            @if($salesRep->phone)
                            <tr>
                                <th>{{translate('Phone')}}:</th>
                                <td>{{ $salesRep->phone }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                @else
                    <p class="text-muted">{{translate('Sales rep information not available')}}</p>
                @endif
            </div>
        </div>

        <!-- Visit Timeline -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Visit Timeline')}}</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">{{translate('Visit Scheduled')}}</h6>
                            <p class="text-muted">{{ $visit->created_at->format('F d, Y h:i A') }}</p>
                        </div>
                    </div>
                    
                    @if($visit->check_in_time)
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">{{translate('Checked In')}}</h6>
                            <p class="text-muted">{{ $visit->visit_date->format('F d, Y') }} {{ \Carbon\Carbon::parse($visit->check_in_time)->format('h:i A') }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($visit->check_out_time)
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">{{translate('Checked Out')}}</h6>
                            <p class="text-muted">{{ $visit->visit_date->format('F d, Y') }} {{ \Carbon\Carbon::parse($visit->check_out_time)->format('h:i A') }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($visit->visit_status === 'completed')
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">{{translate('Visit Completed')}}</h6>
                            <p class="text-muted">{{ $visit->updated_at->format('F d, Y h:i A') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('style')
<style>
.timeline {
    position: relative;
    padding-left: 3rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: -2.2rem;
    top: 2rem;
    width: 2px;
    height: calc(100% - 1rem);
    background: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: -2.5rem;
    top: 0.5rem;
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-left: -1rem;
}

.timeline-title {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
}
</style>
@endsection