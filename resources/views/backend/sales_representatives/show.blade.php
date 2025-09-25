@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Sales Representative Details')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sales_representatives.product_commissions', $salesRep->id) }}" class="btn btn-success btn-sm mr-2">
                <i class="las la-coins"></i>
                <span>{{translate('Product Commissions')}}</span>
            </a>
            <a href="{{ route('sales_representatives.index') }}" class="btn btn-info">
                <span>{{translate('Back to list')}}</span>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Sales Representative Info')}}</h5>
            </div>
            <div class="card-body text-center">
                <div class="avatar avatar-xl mb-3">
                    <img src="{{ uploaded_asset($salesRep->user->avatar ?? '') }}" alt="avatar" class="rounded-circle w-100 h-100" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                </div>
                <h5 class="mb-1">{{ $salesRep->user->name }}</h5>
                <p class="text-muted mb-1">{{ $salesRep->employee_id }}</p>
                <p class="text-muted">{{ $salesRep->user->email }}</p>
                
                <div class="row mt-3">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h6 class="mb-0">{{ $salesRep->territory->name ?? 'N/A' }}</h6>
                            <small class="text-muted">Territory</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h6 class="mb-0">{{ $salesRep->commission_rate }}%</h6>
                            <small class="text-muted">Commission Rate</small>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-2">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h6 class="mb-0">{{ $salesRep->hire_date->format('M Y') }}</h6>
                            <small class="text-muted">Hire Date</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h6 class="mb-0">
                                @if($salesRep->status)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </h6>
                            <small class="text-muted">Status</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Performance Overview')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h3 class="mb-1 text-primary">{{ $monthlyPerformance['orders'] }}</h3>
                            <p class="mb-0 text-muted">Orders This Month</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h3 class="mb-1 text-success">{{ format_price($monthlyPerformance['sales']) }}</h3>
                            <p class="mb-0 text-muted">Sales This Month</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h3 class="mb-1 text-info">{{ format_price($monthlyPerformance['commission']) }}</h3>
                            <p class="mb-0 text-muted">Commission Earned</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h3 class="mb-1 text-warning">{{ $monthlyPerformance['customers'] }}</h3>
                            <p class="mb-0 text-muted">Customers</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Recent Orders')}}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td>{{ $order->code }}</td>
                                    <td>{{ $order->user->name ?? 'Guest' }}</td>
                                    <td>{{ format_price($order->grand_total) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $order->delivery_status == 'delivered' ? 'success' : 'warning' }}">
                                            {{ ucfirst($order->delivery_status) }}
                                        </span>
                                    </td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No recent orders</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Recent Activities')}}</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($recentActivities as $activity)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">{{ $activity->activity_type }}</h6>
                                <p class="mb-1">{{ $activity->description }}</p>
                                <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No recent activities</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
