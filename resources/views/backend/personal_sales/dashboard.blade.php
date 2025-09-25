@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('My Sales Dashboard')}}</h1>
            <p class="text-muted">{{translate('Personal performance overview for')}} {{ $salesRep->user->name ?? '' }}</p>
        </div>
    </div>
</div>

<div class="row gutters-10">
    <div class="col-lg-3 col-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-primary bg-primary">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('My Orders (This Month)')}}</h4>
                </div>
                <div class="card-body">
                    {{ $myOrders }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-primary bg-success">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('My Sales (This Month)')}}</h4>
                </div>
                <div class="card-body">
                    {{ single_price($mySales) }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-primary bg-info">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('My Commissions (This Month)')}}</h4>
                </div>
                <div class="card-body">
                    {{ single_price($myCommissions) }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-primary bg-warning">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('My Customers')}}</h4>
                </div>
                <div class="card-body">
                    {{ $myCustomers }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row gutters-10 mb-3">
    <div class="col-lg-6 col-12">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-primary bg-secondary">
                <i class="fas fa-store"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('My Retail Stores')}}</h4>
                </div>
                <div class="card-body">
                    {{ $myStores }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 col-12">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-primary bg-dark">
                <i class="fas fa-walking"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('Store Visits (This Month)')}}</h4>
                </div>
                <div class="card-body">
                    {{ $myVisits }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row gutters-10">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Recent Activities')}}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{translate('Activity')}}</th>
                                <th>{{translate('Customer')}}</th>
                                <th>{{translate('Date')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivities as $activity)
                                <tr>
                                    <td>{{ $activity->activity_type }}</td>
                                    <td>{{ $activity->customer->user->name ?? 'N/A' }}</td>
                                    <td>{{ $activity->activity_date ? $activity->activity_date->format('M d, Y') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">{{translate('No activities found')}}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Recent Orders')}}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{translate('Order')}}</th>
                                <th>{{translate('Customer')}}</th>
                                <th>{{translate('Amount')}}</th>
                                <th>{{translate('Status')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td>#{{ $order->code }}</td>
                                    <td>{{ $order->customer->user->name ?? 'N/A' }}</td>
                                    <td>{{ single_price($order->grand_total) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $order->delivery_status == 'delivered' ? 'success' : 'warning' }}">
                                            {{ $order->delivery_status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">{{translate('No orders found')}}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row gutters-10 mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Recent Store Visits')}}</h5>
                <a href="{{ route('store_visits.index') }}" class="btn btn-sm btn-primary">
                    <i class="las la-plus"></i> {{translate('View All')}}
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{translate('Store')}}</th>
                                <th>{{translate('Visit Date')}}</th>
                                <th>{{translate('Purpose')}}</th>
                                <th>{{translate('Status')}}</th>
                                <th>{{translate('Order Amount')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentVisits as $visit)
                                <tr>
                                    <td>
                                        <div>
                                            <div class="fw-600">{{ $visit->retailStore->name ?? 'N/A' }}</div>
                                            <div class="fs-12 opacity-60">{{ $visit->retailStore->store_code ?? '' }}</div>
                                        </div>
                                    </td>
                                    <td>{{ $visit->visit_date->format('M d, Y') }}</td>
                                    <td>{{ $visit->purpose ?: 'General visit' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $visit->status_badge }}">
                                            {{ ucfirst($visit->visit_status) }}
                                        </span>
                                    </td>
                                    <td>{{ single_price($visit->order_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{translate('No recent visits found')}}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row gutters-10">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Quick Actions')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <a href="{{ route('personal.activities') }}" class="btn btn-outline-primary btn-block">
                            <i class="las la-clipboard-list"></i>
                            {{translate('My Activities')}}
                        </a>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="{{ route('personal.commissions') }}" class="btn btn-outline-success btn-block">
                            <i class="las la-percentage"></i>
                            {{translate('My Commissions')}}
                        </a>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="{{ route('personal.targets') }}" class="btn btn-outline-warning btn-block">
                            <i class="las la-bullseye"></i>
                            {{translate('My Targets')}}
                        </a>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="{{ route('personal.customers') }}" class="btn btn-outline-info btn-block">
                            <i class="las la-users"></i>
                            {{translate('My Customers')}}
                        </a>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="{{ route('retail_stores.index') }}" class="btn btn-outline-secondary btn-block">
                            <i class="las la-store"></i>
                            {{translate('My Stores')}}
                        </a>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="{{ route('store_visits.index') }}" class="btn btn-outline-dark btn-block">
                            <i class="las la-walking"></i>
                            {{translate('Store Visits')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection