@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('My Profile')}}</h1>
            <p class="text-muted">{{translate('Sales Representative Profile Information')}}</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Profile Information Card -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Profile Information')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="mb-3">
                            @if($salesRep->profile_image)
                                <img src="{{ uploaded_asset($salesRep->profile_image) }}" class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                            @else
                                <img src="{{ static_asset('assets/img/placeholder.jpg') }}" class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                            @endif
                        </div>
                        <h5>{{ $salesRep->user->name ?? 'N/A' }}</h5>
                        <p class="text-muted">{{ $salesRep->designation ?? 'Sales Representative' }}</p>
                    </div>
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tr>
                                <td class="font-weight-bold">{{translate('Employee ID')}}:</td>
                                <td>{{ $salesRep->employee_id ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{translate('Email')}}:</td>
                                <td>{{ $salesRep->user->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{translate('Phone')}}:</td>
                                <td>{{ $salesRep->phone ?? $salesRep->user->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{translate('Designation')}}:</td>
                                <td>{{ $salesRep->designation ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{translate('Hire Date')}}:</td>
                                <td>{{ $salesRep->hire_date ? $salesRep->hire_date->format('Y-m-d') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{translate('Territory')}}:</td>
                                <td>{{ $salesRep->territory->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{translate('Manager')}}:</td>
                                <td>{{ $salesRep->manager->user->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">{{translate('Commission Rate')}}:</td>
                                <td>{{ $salesRep->commission_rate ?? '0' }}%</td>
                            </tr>
                            @if($salesRep->address)
                            <tr>
                                <td class="font-weight-bold">{{translate('Address')}}:</td>
                                <td>{{ $salesRep->address }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Summary Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('This Month Performance')}}</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="mb-3">
                        <div class="card card-statistic-2">
                            <div class="card-icon shadow-primary bg-primary">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h6>{{translate('Orders')}}</h6>
                                </div>
                                <div class="card-body">
                                    <strong>{{ number_format($monthlyPerformance['monthly_orders'] ?? 0) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="card card-statistic-2">
                            <div class="card-icon shadow-primary bg-success">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h6>{{translate('Sales')}}</h6>
                                </div>
                                <div class="card-body">
                                    <strong>{{ single_price($monthlyPerformance['monthly_sales'] ?? 0) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="card card-statistic-2">
                            <div class="card-icon shadow-primary bg-info">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h6>{{translate('Commissions')}}</h6>
                                </div>
                                <div class="card-body">
                                    <strong>{{ single_price($monthlyPerformance['monthly_commissions'] ?? 0) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Targets Card -->
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Sales Targets')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6>{{translate('Monthly Target')}}</h6>
                            <h4 class="text-primary">{{ single_price($salesRep->sales_target_monthly ?? 0) }}</h4>
                            @if($salesRep->sales_target_monthly > 0)
                                @php
                                    $monthlyAchievement = (($monthlyPerformance['monthly_sales'] ?? 0) / $salesRep->sales_target_monthly) * 100;
                                @endphp
                                <div class="progress mb-2">
                                    <div class="progress-bar" role="progressbar" style="width: {{ min($monthlyAchievement, 100) }}%" aria-valuenow="{{ $monthlyAchievement }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted">{{ number_format($monthlyAchievement, 1) }}% {{translate('achieved')}}</small>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6>{{translate('Quarterly Target')}}</h6>
                            <h4 class="text-info">{{ single_price($salesRep->sales_target_quarterly ?? 0) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6>{{translate('Yearly Target')}}</h6>
                            <h4 class="text-success">{{ single_price($salesRep->sales_target_yearly ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($salesRep->notes)
<!-- Notes Card -->
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Notes')}}</h5>
            </div>
            <div class="card-body">
                <p>{{ $salesRep->notes }}</p>
            </div>
        </div>
    </div>
</div>
@endif

@endsection