@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Sales Representative Dashboard')}}</h1>
        </div>
    </div>
</div>

<div class="row gutters-10">
    <div class="col-lg-3 col-6">
        <div class="card card-statistic-2">
            <div class="card-stats">
                <div class="card-stats-title">{{translate('Statistics')}} -
                    <div class="dropdown d-inline">
                        <a class="font-weight-600 dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                            {{translate('This Month')}}
                        </a>
                    </div>
                </div>
                <div class="card-stats-items">
                    <div class="card-stats-item">
                        <div class="card-stats-item-count">{{ $totalReps }}</div>
                        <div class="card-stats-item-label">{{translate('Total Reps')}}</div>
                    </div>
                    <div class="card-stats-item">
                        <div class="card-stats-item-count">{{ $totalTerritories }}</div>
                        <div class="card-stats-item-label">{{translate('Territories')}}</div>
                    </div>
                    <div class="card-stats-item">
                        <div class="card-stats-item-count">{{ $totalCustomers }}</div>
                        <div class="card-stats-item-label">{{translate('Assigned Customers')}}</div>
                    </div>
                </div>
            </div>
            <div class="card-icon shadow-primary bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('Team Overview')}}</h4>
                </div>
                <div class="card-body">
                    {{ $totalReps }} {{translate('Active Sales Representatives')}}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-primary bg-warning">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('Monthly Orders')}}</h4>
                </div>
                <div class="card-body">
                    {{ $monthlyOrders }}
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
                    <h4>{{translate('Monthly Sales')}}</h4>
                </div>
                <div class="card-body">
                    {{ single_price($monthlySales) }}
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
                    <h4>{{translate('Monthly Commissions')}}</h4>
                </div>
                <div class="card-body">
                    {{ single_price($monthlyCommissions) }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row gutters-10">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Top Performers This Month')}}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{translate('Sales Rep')}}</th>
                                <th>{{translate('Orders')}}</th>
                                <th>{{translate('Sales')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topPerformers as $performer)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ uploaded_asset($performer->profile_image) ?? static_asset('assets/img/avatar-place.png') }}" class="size-30px rounded-circle mr-2">
                                            <span>{{ $performer->user->name ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $performer->orders_count }}</td>
                                    <td>{{ single_price($performer->monthly_sales) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">{{translate('No data available')}}</td>
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
                <h5 class="mb-0 h6">{{translate('Recent Activities')}}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{translate('Sales Rep')}}</th>
                                <th>{{translate('Activity')}}</th>
                                <th>{{translate('Customer')}}</th>
                                <th>{{translate('Date')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivities as $activity)
                                <tr>
                                    <td>{{ $activity->salesRepresentative->user->name ?? '' }}</td>
                                    <td>{{ $activity->activity_type_name }}</td>
                                    <td>{{ $activity->customer->user->name ?? '' }}</td>
                                    <td>{{ $activity->activity_date->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">{{translate('No activities found')}}</td>
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
                    @can('add_sales_representative')
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('sales_representatives.create') }}" class="btn btn-outline-primary btn-block">
                                <i class="las la-plus"></i>
                                {{translate('Add Sales Rep')}}
                            </a>
                        </div>
                    @endcan
                    @can('manage_sales_territories')
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('sales_territories.index') }}" class="btn btn-outline-info btn-block">
                                <i class="las la-map"></i>
                                {{translate('Manage Territories')}}
                            </a>
                        </div>
                    @endcan
                    @can('manage_sales_targets')
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('sales_targets.index') }}" class="btn btn-outline-warning btn-block">
                                <i class="las la-bullseye"></i>
                                {{translate('Sales Targets')}}
                            </a>
                        </div>
                    @endcan
                    @can('view_sales_commissions')
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('sales_commissions.index') }}" class="btn btn-outline-success btn-block">
                                <i class="las la-percentage"></i>
                                {{translate('Commissions')}}
                            </a>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
