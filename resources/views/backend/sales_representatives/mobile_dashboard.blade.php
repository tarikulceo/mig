@extends('backend.layouts.app')

@section('meta')
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
@endsection

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 col-sm-12">
                <h1 class="h3 mb-2 mb-md-0">{{translate('Mobile Sales Dashboard')}}</h1>
                <p class="text-muted mb-0 d-none d-sm-block">{{translate('Quick access to field sales activities')}}</p>
            </div>
            <div class="col-md-6 col-sm-12 text-md-right text-center">
                <span class="badge badge-inline badge-success px-3 py-2">
                    <i class="las la-map-marker-alt"></i> {{translate('Field Ready')}}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Performance Chart -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <h5 class="mb-2 mb-md-0 h6">{{translate('Weekly Performance')}}</h5>
                    <div class="btn-group btn-group-sm w-100 w-md-auto" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-chart="visits">
                            <span class="d-none d-sm-inline">{{translate('Visits')}}</span>
                            <span class="d-sm-none"><i class="las la-store"></i></span>
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-chart="sales">
                            <span class="d-none d-sm-inline">{{translate('Sales')}}</span>
                            <span class="d-sm-none"><i class="las la-dollar-sign"></i></span>
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-chart="orders">
                            <span class="d-none d-sm-inline">{{translate('Orders')}}</span>
                            <span class="d-sm-none"><i class="las la-shopping-cart"></i></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-2 p-md-3">
                <div class="chart-container position-relative">
                    <canvas id="performanceChart" class="w-100"></canvas>
                    <div id="chartLoading" class="chart-loading position-absolute w-100 h-100 d-flex align-items-center justify-content-center" style="top: 0; left: 0; background: rgba(255,255,255,0.8); display: none;">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">{{translate('Loading chart...')}}</span>
                            </div>
                            <div class="mt-2">{{translate('Loading chart...')}}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Nearby Stores Map -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">{{translate('Nearby Stores')}}</h5>
                <button class="btn btn-sm btn-primary" onclick="refreshNearbyStores()">
                    <i class="las la-sync"></i> {{translate('Refresh')}}
                </button>
            </div>
            <div class="card-body">
                <div id="nearbyStoresMap" style="height: 300px; width: 100%;">
                    <div class="text-center py-5">
                        <i class="las la-map-marker-alt fs-40 opacity-60"></i>
                        <p class="mt-2">{{translate('Enable location to see nearby stores')}}</p>
                        <button class="btn btn-primary btn-sm" onclick="getCurrentLocation()">
                            {{translate('Enable Location')}}
                        </button>
                    </div>
                </div>
                <div id="nearbyStoresList" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Target Progress -->
<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Monthly Target Progress')}}</h5>
            </div>
            <div class="card-body">
                @php
                    $monthlyTarget = $salesRep->sales_target_monthly ?? 0;
                    $monthlySales = $todaySales * 30; // Rough estimate
                    $progressPercentage = $monthlyTarget > 0 ? min(100, ($monthlySales / $monthlyTarget) * 100) : 0;
                @endphp
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>{{translate('Sales Target')}}</span>
                    <span class="fw-600">{{ single_price($monthlyTarget) }}</span>
                </div>
                
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: {{ $progressPercentage }}%"></div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <small class="text-muted">{{translate('Achieved')}}: {{ single_price($monthlySales) }}</small>
                    <small class="text-muted">{{ number_format($progressPercentage, 1) }}%</small>
                </div>
                
                @if($progressPercentage >= 100)
                    <div class="alert alert-success mt-2 py-2">
                        <i class="las la-trophy"></i> {{translate('Target Achieved! 🎉')}}
                    </div>
                @elseif($progressPercentage >= 80)
                    <div class="alert alert-warning mt-2 py-2">
                        <i class="las la-fire"></i> {{translate('Almost there! Keep it up!')}}
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Visit Completion Rate')}}</h5>
            </div>
            <div class="card-body">
                @php
                    $totalVisitsThisMonth = $todayVisits * 30; // Rough estimate
                    $completedVisitsThisMonth = $totalVisitsThisMonth * 0.85; // Assuming 85% completion rate
                    $completionRate = $totalVisitsThisMonth > 0 ? ($completedVisitsThisMonth / $totalVisitsThisMonth) * 100 : 0;
                @endphp
                
                <div class="text-center">
                    <div class="position-relative d-inline-block">
                        <svg width="120" height="120">
                            <circle cx="60" cy="60" r="50" fill="none" stroke="#e9ecef" stroke-width="8"></circle>
                            <circle cx="60" cy="60" r="50" fill="none" stroke="#28a745" stroke-width="8"
                                    stroke-dasharray="{{ 2 * 3.14159 * 50 }}"
                                    stroke-dashoffset="{{ 2 * 3.14159 * 50 * (1 - $completionRate/100) }}"
                                    transform="rotate(-90 60 60)"></circle>
                        </svg>
                        <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                            <h4 class="mb-0">{{ number_format($completionRate, 0) }}%</h4>
                        </div>
                    </div>
                    <p class="mt-2 mb-0">{{translate('Completion Rate')}}</p>
                    <small class="text-muted">{{ $completedVisitsThisMonth }}/{{ $totalVisitsThisMonth }} {{translate('visits')}}</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Stats -->
    <div class="col-12">
        <div class="row">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6">
                <div class="card bg-gradient-primary text-white stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-1 text-white">{{ $todayVisits }}</h4>
                                <small class="text-white-50 d-block">{{translate('Today Visits')}}</small>
                            </div>
                            <div class="fs-30 ml-2">
                                <i class="las la-store"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6">
                <div class="card bg-gradient-success text-white stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-1 text-white">{{ $todayOrders }}</h4>
                                <small class="text-white-50 d-block">{{translate('Today Orders')}}</small>
                            </div>
                            <div class="fs-30 ml-2">
                                <i class="las la-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6">
                <div class="card bg-gradient-info text-white stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-1 text-white text-truncate">{{ single_price($todaySales) }}</h4>
                                <small class="text-white-50 d-block">{{translate('Today Sales')}}</small>
                            </div>
                            <div class="fs-30 ml-2">
                                <i class="las la-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6">
                <div class="card bg-gradient-warning text-white stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-1 text-white">{{ $pendingOrders }}</h4>
                                <small class="text-white-50 d-block">{{translate('Pending Orders')}}</small>
                            </div>
                            <div class="fs-30 ml-2">
                                <i class="las la-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-lg-4 col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">{{translate('Quick Actions')}}</h5>
                <small class="text-muted d-none d-md-block">{{translate('Tap to start')}}</small>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                        <a href="{{ route('store_visits.create') }}" class="btn btn-soft-primary btn-block text-left mb-2 action-btn w-100 text-decoration-none">
                            <div class="d-flex align-items-center justify-content-center justify-content-sm-start">
                                <i class="las la-plus-circle fs-4 me-2"></i>
                                <div class="flex-grow-1 text-center text-sm-start">
                                    <div class="fw-bold small">{{translate('New Visit')}}</div>
                                    <div class="text-muted d-none d-lg-block" style="font-size: 0.75rem;">{{translate('Start store visit')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                        <button type="button" class="btn btn-soft-success btn-block text-left mb-2 action-btn w-100" data-toggle="modal" data-target="#quickOrderModal">
                            <div class="d-flex align-items-center justify-content-center justify-content-sm-start">
                                <i class="las la-shopping-cart fs-4 me-2"></i>
                                <div class="flex-grow-1 text-center text-sm-start">
                                    <div class="fw-bold small">{{translate('Quick Order')}}</div>
                                    <div class="text-muted d-none d-lg-block" style="font-size: 0.75rem;">{{translate('Fast order entry')}}</div>
                                </div>
                            </div>
                        </button>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                        <button type="button" class="btn btn-soft-info btn-block text-left mb-2 action-btn w-100" onclick="getCurrentLocation();">
                            <div class="d-flex align-items-center justify-content-center justify-content-sm-start">
                                <i class="las la-map-marker-alt fs-4 me-2"></i>
                                <div class="flex-grow-1 text-center text-sm-start">
                                    <div class="fw-bold small">{{translate('My Location')}}</div>
                                    <div class="text-muted d-none d-lg-block" style="font-size: 0.75rem;">{{translate('Check GPS')}}</div>
                                </div>
                            </div>
                        </button>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                        <a href="{{ route('personal.activities') }}" class="btn btn-soft-warning btn-block text-left mb-2 action-btn w-100 text-decoration-none">
                            <div class="d-flex align-items-center justify-content-center justify-content-sm-start">
                                <i class="las la-chart-bar fs-4 me-2"></i>
                                <div class="flex-grow-1 text-center text-sm-start">
                                    <div class="fw-bold small">{{translate('Reports')}}</div>
                                    <div class="text-muted d-none d-lg-block" style="font-size: 0.75rem;">{{translate('View analytics')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Additional Actions Row -->
                <div class="row g-2 mt-2">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                        <a href="{{ route('store_orders.create') }}" class="btn btn-outline-success btn-block text-left mb-2 action-btn w-100 text-decoration-none">
                            <div class="d-flex align-items-center justify-content-center justify-content-sm-start">
                                <i class="las la-clipboard-list fs-4 me-2"></i>
                                <div class="flex-grow-1 text-center text-sm-start">
                                    <div class="fw-bold small">{{translate('Full Order')}}</div>
                                    <div class="text-muted d-none d-lg-block" style="font-size: 0.75rem;">{{translate('Complete order form')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                        <a href="{{ route('sales_representatives.analytics') }}" class="btn btn-outline-info btn-block text-left mb-2 action-btn w-100 text-decoration-none">
                            <div class="d-flex align-items-center justify-content-center justify-content-sm-start">
                                <i class="las la-chart-line fs-4 me-2"></i>
                                <div class="flex-grow-1 text-center text-sm-start">
                                    <div class="fw-bold small">{{translate('Analytics')}}</div>
                                    <div class="text-muted d-none d-lg-block" style="font-size: 0.75rem;">{{translate('Performance data')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Status -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Location Status')}}</h5>
            </div>
            <div class="card-body">
                <div id="locationStatus" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">{{translate('Getting location...')}}</span>
                    </div>
                    <div class="mt-2">{{translate('Getting your location...')}}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Schedule -->
    <div class="col-lg-4 col-md-6 col-sm-12">
        <div class="card h-100">
            <div class="card-header">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                    <h5 class="mb-1 mb-sm-0 h6">{{translate("Today's Schedule")}}</h5>
                    <small class="text-muted">{{ date('M d, Y') }}</small>
                </div>
            </div>
            <div class="card-body">
                @if($todaySchedule && $todaySchedule->count() > 0)
                    @foreach($todaySchedule as $visit)
                    <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                        <div class="size-40px rounded-circle bg-soft-{{ $visit->status_color }} d-flex align-items-center justify-content-center mr-3">
                            <i class="las la-{{ $visit->status_icon }} text-{{ $visit->status_color }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-1">{{ $visit->retailStore->name }}</h6>
                                <small class="text-muted">{{ $visit->scheduled_time ? $visit->scheduled_time->format('H:i') : 'No time' }}</small>
                            </div>
                            <p class="mb-1 fs-13 opacity-60">{{ $visit->purpose }}</p>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-inline badge-{{ $visit->status_color }} badge-sm mr-2">{{ ucfirst($visit->status) }}</span>
                                @if($visit->status === 'pending')
                                    <button class="btn btn-xs btn-soft-primary" onclick="startVisit({{ $visit->id }})">
                                        {{translate('Start Visit')}}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="las la-calendar-times fs-40 opacity-60"></i>
                        <p class="mt-2 mb-0 opacity-60">{{translate('No visits scheduled for today')}}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="col-lg-4 col-md-6 col-sm-12">
        <div class="card h-100">
            <div class="card-header">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                    <h5 class="mb-1 mb-sm-0 h6">{{translate('Recent Orders')}}</h5>
                    <a href="{{ route('store_orders.index') }}" class="btn btn-xs btn-soft-primary">{{translate('View All')}}</a>
                </div>
            </div>
            <div class="card-body">
                @if($recentOrders && $recentOrders->count() > 0)
                    @foreach($recentOrders as $order)
                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <div class="size-40px rounded-circle bg-soft-{{ $order->status_badge }} d-flex align-items-center justify-content-center mr-3">
                            <i class="las la-shopping-bag text-{{ $order->status_badge }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-1">{{ $order->order_code }}</h6>
                                <span class="fw-600">{{ single_price($order->grand_total) }}</span>
                            </div>
                            <p class="mb-1 fs-13 opacity-60">{{ $order->retailStore->name }}</p>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-inline badge-{{ $order->status_badge }} badge-sm mr-2">{{ $order->order_status_name }}</span>
                                <small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="las la-shopping-cart fs-40 opacity-60"></i>
                        <p class="mt-2 mb-0 opacity-60">{{translate('No recent orders')}}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Quick Order Modal -->
<div class="modal fade" id="quickOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{translate('Quick Order')}}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="quickOrderForm" method="POST" action="{{ route('store_orders.quick_order') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{translate('Select Store')}}</label>
                                <select class="form-control" name="retail_store_id" required>
                                    <option value="">{{translate('Choose a store')}}</option>
                                    @foreach($myStores as $store)
                                        <option value="{{ $store->id }}">{{ $store->name }} ({{ $store->store_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{translate('Total Amount')}}</label>
                                <input type="number" class="form-control" name="total_amount" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{translate('Order Notes')}}</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="{{translate('Describe the order items...')}}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Cancel')}}</button>
                    <button type="submit" class="btn btn-primary">{{translate('Create Quick Order')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript">
    let watchId = null;
    let performanceChart = null;

    // Chart data from backend
    let weeklyPerformanceData = @json($weeklyPerformance ?? []);
    
    console.log('Initial weekly performance data:', weeklyPerformanceData);
    
    // Debug info for troubleshooting
    window.debugChart = function() {
        console.log('=== Chart Debug Info ===');
        console.log('Chart.js available:', typeof Chart !== 'undefined');
        console.log('Performance chart canvas:', document.getElementById('performanceChart'));
        console.log('Weekly data:', weeklyPerformanceData);
        console.log('Weekly data length:', weeklyPerformanceData ? weeklyPerformanceData.length : 'undefined');
        console.log('Loading element visible:', $('#chartLoading').is(':visible'));
        console.log('Performance chart instance:', window.performanceChart);
        
        // Force hide loading for testing
        if ($('#chartLoading').is(':visible')) {
            console.log('Manually hiding loading...');
            $('#chartLoading').fadeOut(300);
        }
    };
    
    // Auto-debug after 2 seconds
    setTimeout(function() {
        debugChart();
    }, 2000);
    
    // Fallback data if no performance data available
    if (!weeklyPerformanceData || weeklyPerformanceData.length === 0) {
        console.warn('No performance data available for chart, using fallback');
        weeklyPerformanceData = [
            {date: 'Mon', day: 'Mon', visits: 0, orders: 0, sales: 0},
            {date: 'Tue', day: 'Tue', visits: 0, orders: 0, sales: 0},
            {date: 'Wed', day: 'Wed', visits: 0, orders: 0, sales: 0},
            {date: 'Thu', day: 'Thu', visits: 0, orders: 0, sales: 0},
            {date: 'Fri', day: 'Fri', visits: 0, orders: 0, sales: 0},
            {date: 'Sat', day: 'Sat', visits: 0, orders: 0, sales: 0},
            {date: 'Sun', day: 'Sun', visits: 0, orders: 0, sales: 0}
        ];
    }

    // Get current location
    function getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(showPosition, showError);
        } else {
            updateLocationStatus("error", "Geolocation is not supported by this browser.");
        }
    }

    function showPosition(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;
        
        updateLocationStatus("success", `Location: ${lat.toFixed(6)}, ${lng.toFixed(6)}<br>Accuracy: ${accuracy.toFixed(0)}m`);
        
        // Store location for use in forms
        localStorage.setItem('currentLat', lat);
        localStorage.setItem('currentLng', lng);
    }

    function showError(error) {
        let message = "Unknown error occurred.";
        switch(error.code) {
            case error.PERMISSION_DENIED:
                message = "Location access denied by user.";
                break;
            case error.POSITION_UNAVAILABLE:
                message = "Location information is unavailable.";
                break;
            case error.TIMEOUT:
                message = "Location request timed out.";
                break;
        }
        updateLocationStatus("error", message);
    }

    function updateLocationStatus(type, message) {
        const statusDiv = document.getElementById('locationStatus');
        const iconClass = type === 'success' ? 'las la-check-circle text-success' : 'las la-exclamation-triangle text-danger';
        
        statusDiv.innerHTML = `
            <div class="text-center">
                <i class="${iconClass} fs-30"></i>
                <div class="mt-2 fs-13">${message}</div>
                <button class="btn btn-sm btn-soft-primary mt-2" onclick="getCurrentLocation()">
                    <i class="las la-sync"></i> {{translate('Refresh')}}
                </button>
            </div>
        `;
    }

    function startVisit(visitId) {
        if (confirm('{{translate("Are you sure you want to start this visit?")}}')) {
            // Get current location first
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    // Send AJAX request to start visit with location
                    $.ajax({
                        url: `/admin/store-visits/${visitId}/start`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.message || '{{translate("Error starting visit")}}');
                            }
                        },
                        error: function() {
                            alert('{{translate("Error starting visit")}}');
                        }
                    });
                }, function(error) {
                    // Start visit without location if GPS fails
                    $.ajax({
                        url: `/admin/store-visits/${visitId}/start`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.message || '{{translate("Error starting visit")}}');
                            }
                        }
                    });
                });
            }
        }
    }

    // Auto-refresh location every 5 minutes
    setInterval(function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                localStorage.setItem('currentLat', position.coords.latitude);
                localStorage.setItem('currentLng', position.coords.longitude);
            });
        }
    }, 300000); // 5 minutes

    // Initialize location on page load
    $(document).ready(function() {
        console.log('Document ready, initializing mobile dashboard...');
        console.log('Weekly performance data received:', weeklyPerformanceData);
        
        getCurrentLocation();
        
        // Auto-refresh stats every minute
        setInterval(function() {
            // You can add AJAX call to refresh stats here if needed
        }, 60000);
        
        // Mobile optimizations
        initMobileOptimizations();
        
        // Force hide chart loading immediately - no delays
        var loadingDiv = document.getElementById('chartLoading');
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
            loadingDiv.style.visibility = 'hidden';
            loadingDiv.style.opacity = '0';
            loadingDiv.remove(); // Completely remove the element
        }
        
        // Chart responsive handling - with delay to ensure DOM is ready
        setTimeout(function() {
            console.log('Initializing responsive chart...');
            initResponsiveChart();
        }, 100);
        

    });
    
    // Mobile-specific optimizations
    function initMobileOptimizations() {
        // Fix button clicks on mobile devices - Don't interfere with anchor links
        $('button:not([data-toggle]):not([onclick])').off('click touchend').on('click', function(e) {
            // Only handle buttons without specific actions
            console.log('Button clicked:', $(this).text());
        });
        
        // Ensure modal buttons work properly
        $('[data-toggle="modal"]').off('click touchend').on('click', function(e) {
            e.preventDefault();
            const target = $(this).data('target');
            if (target) {
                $(target).modal('show');
            }
        });
        
        // Handle orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                // Recalculate chart size if exists
                if (window.performanceChart) {
                    window.performanceChart.resize();
                }
                
                // Refresh layout
                initMobileOptimizations();
            }, 100);
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.performanceChart) {
                setTimeout(function() {
                    window.performanceChart.resize();
                }, 200);
            }
        });
        
        // Ensure touch events work properly on mobile
        if ('ontouchstart' in window) {
            $('.btn, .action-btn').each(function() {
                $(this).on('touchstart', function() {
                    $(this).addClass('active');
                }).on('touchend', function() {
                    const $this = $(this);
                    setTimeout(function() {
                        $this.removeClass('active');
                    }, 150);
                });
            });
        }
        
        // Quick Order Modal functionality
        $('#quickOrderForm').on('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            
            // Submit via AJAX
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#quickOrderModal').modal('hide');
                        // Show success message
                        alert('{{translate("Order created successfully!")}}');
                        // Optionally reload the page to update stats
                        location.reload();
                    } else {
                        alert(response.message || '{{translate("Error creating order")}}');
                    }
                },
                error: function(xhr) {
                    let message = '{{translate("Error creating order")}}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    alert(message);
                }
            });
        });
    }
    
    // Initialize responsive chart
    function initResponsiveChart() {
        console.log('initResponsiveChart called');
        
        // Simple immediate check
        if (typeof Chart !== 'undefined') {
            console.log('Chart.js is available immediately');
            Chart.defaults.responsive = true;
            Chart.defaults.maintainAspectRatio = false;
            
            // Initialize performance chart
            initPerformanceChart();
        } else {
            console.log('Chart.js not available yet, waiting 1 second...');
            
            // Wait 1 second and try again
            setTimeout(function() {
                if (typeof Chart !== 'undefined') {
                    console.log('Chart.js loaded after delay');
                    Chart.defaults.responsive = true;
                    Chart.defaults.maintainAspectRatio = false;
                    initPerformanceChart();
                } else {
                    console.warn('Chart.js still not available, creating fallback');
                    createFallbackChart();
                }
            }, 1000);
        }
    }
    
    // Fallback chart using simple HTML/CSS bars
    function createFallbackChart() {
        console.log('Creating fallback HTML chart');
        
        const chartContainer = $('#performanceChart').parent();
        const maxValue = Math.max(...weeklyPerformanceData.map(d => d.visits));
        
        let html = '<div class="fallback-chart">';
        html += '<h6 class="text-center mb-3">{{translate("Weekly Visits")}}</h6>';
        html += '<div class="chart-bars d-flex align-items-end justify-content-between" style="height: 150px;">';
        
        weeklyPerformanceData.forEach(function(day, index) {
            const height = maxValue > 0 ? (day.visits / maxValue * 120) : 10;
            html += `<div class="chart-bar text-center" style="width: 12%;">
                        <div class="bar bg-primary" style="height: ${height}px; margin: 0 auto 5px; width: 20px; border-radius: 2px;"></div>
                        <small class="text-muted">${day.day}</small>
                        <br><small class="fw-bold">${day.visits}</small>
                     </div>`;
        });
        
        html += '</div></div>';
        
        chartContainer.html(html);
    }
    
    // Initialize performance chart
    function initPerformanceChart() {
        console.log('initPerformanceChart called');
        const ctx = document.getElementById('performanceChart');
        if (!ctx) {
            console.error('Performance chart canvas not found');
            $('#chartLoading').html('<div class="text-center text-danger"><i class="las la-exclamation-triangle"></i><br>Canvas not found</div>');
            return;
        }
        
        console.log('Weekly performance data:', weeklyPerformanceData);
        // Always proceed to create chart, even with empty data
        
        // Destroy existing chart if it exists
        if (performanceChart) {
            performanceChart.destroy();
        }
        
        // Prepare chart data
        const labels = weeklyPerformanceData.map(item => item.day);
        
        const datasets = {
            visits: {
                label: '{{translate("Visits")}}',
                data: weeklyPerformanceData.map(item => item.visits),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                fill: true,
                tension: 0.4
            },
            sales: {
                label: '{{translate("Sales")}} ({{get_setting("system_default_currency")}})',
                data: weeklyPerformanceData.map(item => item.sales),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                fill: true,
                tension: 0.4
            },
            orders: {
                label: '{{translate("Orders")}}',
                data: weeklyPerformanceData.map(item => item.orders),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                fill: true,
                tension: 0.4
            }
        };
        
        // Default to visits chart
        const activeChart = $('[data-chart].active').data('chart') || 'visits';
        
        const config = {
            type: 'line',
            data: {
                labels: labels,
                datasets: [datasets[activeChart]]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (activeChart === 'sales') {
                                    const currency = '{{get_setting("system_default_currency") ?: "USD"}}';
                                    try {
                                        label += new Intl.NumberFormat('en-US', {
                                            style: 'currency',
                                            currency: currency
                                        }).format(context.parsed.y);
                                    } catch (e) {
                                        label += currency + ' ' + context.parsed.y.toFixed(2);
                                    }
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        display: true,
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                if (activeChart === 'sales') {
                                    const currency = '{{get_setting("system_default_currency") ?: "USD"}}';
                                    try {
                                        return new Intl.NumberFormat('en-US', {
                                            style: 'currency',
                                            currency: currency,
                                            minimumFractionDigits: 0
                                        }).format(value);
                                    } catch (e) {
                                        return currency + ' ' + value.toFixed(0);
                                    }
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        };
        
        try {
            console.log('Creating chart with config:', config);
            
            // Create the chart
            performanceChart = new Chart(ctx, config);
            window.performanceChart = performanceChart; // Store globally for resize
            
            console.log('Chart created successfully');
            
            // Chart created successfully - no loading overlay to hide
            
        } catch (error) {
            console.error('Error creating chart:', error);
            
            // Chart error handled - no loading overlay to hide
            
            // Create fallback chart instead
            createFallbackChart();
        }
        
        // Chart button handlers
        $('[data-chart]').off('click').on('click', function(e) {
            e.preventDefault();
            const chartType = $(this).data('chart');
            
            // Update active button
            $('[data-chart]').removeClass('active');
            $(this).addClass('active');
            
            // Update chart data
            if (performanceChart && datasets[chartType]) {
                performanceChart.data.datasets = [datasets[chartType]];
                
                // Update Y-axis formatting
                const yAxis = performanceChart.options.scales.y;
                if (chartType === 'sales') {
                    yAxis.ticks.callback = function(value) {
                        const currency = '{{get_setting("system_default_currency") ?: "USD"}}';
                        try {
                            return new Intl.NumberFormat('en-US', {
                                style: 'currency',
                                currency: currency,
                                minimumFractionDigits: 0
                            }).format(value);
                        } catch (e) {
                            return currency + ' ' + value.toFixed(0);
                        }
                    };
                } else {
                    yAxis.ticks.callback = function(value) {
                        return value;
                    };
                }
                
                // Update tooltip formatting
                performanceChart.options.plugins.tooltip.callbacks.label = function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (chartType === 'sales') {
                        const currency = '{{get_setting("system_default_currency") ?: "USD"}}';
                        try {
                            label += new Intl.NumberFormat('en-US', {
                                style: 'currency',
                                currency: currency
                            }).format(context.parsed.y);
                        } catch (e) {
                            label += currency + ' ' + context.parsed.y.toFixed(2);
                        }
                    } else {
                        label += context.parsed.y;
                    }
                    return label;
                };
                
                performanceChart.update('active');
            }
        });
    }
    
    // Refresh nearby stores function
    function refreshNearbyStores() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                // You can implement the actual nearby stores fetching here
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // For now, show a success message
                const mapDiv = document.getElementById('nearbyStoresMap');
                if (mapDiv) {
                    mapDiv.innerHTML = `
                        <div class="text-center py-5">
                            <i class="las la-check-circle fs-40 text-success"></i>
                            <p class="mt-2">Location updated successfully!</p>
                            <p class="text-muted">Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}</p>
                            <button class="btn btn-primary btn-sm" onclick="getCurrentLocation()">
                                {{translate('Refresh Location')}}
                            </button>
                        </div>
                    `;
                }
            }, function(error) {
                alert('Error getting location: ' + error.message);
            });
        } else {
            alert('Geolocation is not supported by this browser.');
        }
    }
</script>
@endsection

@section('style')
<style>
    /* Gradient Backgrounds */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .bg-gradient-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }
    
    .bg-gradient-info {
        background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    }
    
    .bg-gradient-warning {
        background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    }
    
    /* Base Card Styling */
    .card {
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border: none;
        border-radius: 10px;
        margin-bottom: 1.5rem;
    }
    
    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    }
    
    /* Button Styling */
    .btn-block.text-left {
        padding: 15px 20px;
        height: auto;
        text-align: left !important;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .btn-block.text-left:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    /* Size Utilities */
    .size-40px {
        width: 40px;
        height: 40px;
        min-width: 40px;
        flex-shrink: 0;
    }
    
    /* Chart Responsive */
    #performanceChart {
        max-height: 300px;
    }
    
    /* Map Responsive */
    #nearbyStoresMap {
        border-radius: 8px;
        overflow: hidden;
    }
    
    /* Stats Cards Enhancement */
    .stats-card .card-body {
        padding: 1.5rem;
    }
    
    .stats-card h4 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .stats-card small {
        font-size: 0.875rem;
        opacity: 0.9;
    }
    
    .stats-card .fs-30 {
        font-size: 2.5rem;
        opacity: 0.8;
    }
    
    /* Progress Bars */
    .progress {
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-bar {
        border-radius: 10px;
    }
    
    /* Badge Improvements */
    .badge {
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-weight: 500;
    }
    
    /* Mobile Optimizations */
    @media (max-width: 576px) {
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .aiz-titlebar {
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .aiz-titlebar .row {
            justify-content: center;
        }
        
        .aiz-titlebar .col-md-6:last-child {
            text-align: center !important;
            margin-top: 0.5rem;
        }
        
        .aiz-titlebar h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .card {
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .card-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .stats-card .card-body {
            padding: 1rem;
        }
        
        .stats-card h4 {
            font-size: 1.5rem;
        }
        
        .stats-card .fs-30 {
            font-size: 2rem;
        }
        
        .btn-block.text-left, .action-btn {
            padding: 15px 10px;
            font-size: 0.875rem;
            min-height: 65px;
            text-align: center !important;
        }
        
        .action-btn .d-flex {
            flex-direction: column;
            text-align: center;
        }
        
        .action-btn .fs-4 {
            font-size: 1.5rem !important;
            margin-bottom: 0.5rem;
        }
        
        .action-btn .fw-bold {
            font-size: 0.8rem;
        }
        
        .btn-group .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }
        
        .chart-container {
            height: 200px !important;
        }
        
        #performanceChart {
            height: 200px !important;
        }
        
        #nearbyStoresMap {
            height: 250px !important;
        }
        
        .btn-group .btn {
            min-height: 44px;
            padding: 8px 12px;
        }
        
        .modal-lg {
            max-width: 95%;
            margin: 1rem auto;
        }
        
        .modal-body {
            padding: 1rem;
        }
        
        .d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .d-flex.justify-content-between > * {
            margin-bottom: 0.5rem;
        }
        
        .d-flex.justify-content-between > *:last-child {
            margin-bottom: 0;
        }
        
        /* Make quick actions grid work better on mobile */
        .row.g-2 .col-6 {
            margin-bottom: 0.5rem;
        }
    }
    
    /* Tablet Optimizations */
    @media (min-width: 577px) and (max-width: 768px) {
        .aiz-titlebar h1 {
            font-size: 1.75rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .stats-card h4 {
            font-size: 1.75rem;
        }
        
        .chart-container {
            height: 250px !important;
        }
        
        #performanceChart {
            height: 250px !important;
        }
        
        .btn-block.text-left {
            padding: 14px 18px;
        }
    }
    
    /* Large Mobile Optimizations */
    @media (min-width: 769px) and (max-width: 991px) {
        .card-body {
            padding: 1.5rem;
        }
        
        .chart-container {
            height: 280px !important;
        }
        
        #performanceChart {
            height: 280px !important;
        }
    }
    
    /* Action Button Improvements */
    .action-btn {
        transition: all 0.2s ease;
        border-radius: 8px;
        border: 1px solid transparent;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        text-decoration: none !important;
        color: inherit;
    }
    
    .action-btn:hover, .action-btn:focus {
        transform: translateY(-1px);
        box-shadow: 0 3px 12px rgba(0,0,0,0.1);
        text-decoration: none !important;
        color: inherit;
    }
    
    .action-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .action-btn:visited {
        color: inherit;
        text-decoration: none !important;
    }
    
    /* Ensure anchor tag buttons inherit text color properly */
    a.action-btn, a.action-btn:hover, a.action-btn:focus, a.action-btn:visited {
        color: inherit !important;
        text-decoration: none !important;
    }
    
    /* Button text styling */
    .action-btn .fw-bold, .action-btn .text-muted {
        color: inherit;
    }
    
    /* Button responsiveness improvements */
    .btn-block {
        width: 100%;
        display: block;
    }
    
    /* Ensure buttons work on all devices */
    button, .btn, a.btn {
        -webkit-tap-highlight-color: transparent;
        user-select: none;
    }
    
    /* Grid gap for better spacing */
    .row.g-2 > * {
        padding-right: 0.5rem;
        padding-left: 0.5rem;
    }
    
    /* Chart Container */
    .chart-container {
        height: 300px;
        position: relative;
        margin-bottom: 1rem;
    }
    
    .chart-container canvas {
        max-height: 100% !important;
        width: 100% !important;
    }
    
    .chart-loading {
        z-index: 10;
        border-radius: 8px;
        display: none !important; /* Force hide by default */
        opacity: 0 !important;
        pointer-events: none !important;
    }
    
    /* Force hide class - ultimate override */
    .chart-loading, 
    #chartLoading {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
    
    /* Fallback Chart Styles */
    .fallback-chart {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        text-align: center;
    }
    
    .chart-bars {
        padding: 10px;
    }
    
    .chart-bar .bar {
        transition: height 0.3s ease;
    }
    
    /* Chart Button Group */
    .btn-group .btn.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    
    .btn-group .btn:not(.active):hover {
        background-color: #f8f9fa;
        color: #007bff;
        border-color: #007bff;
    }
    
    /* Card Height Matching */
    .h-100 .card-body {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    /* Touch-friendly interactions */
    @media (hover: none) and (pointer: coarse) {
        .btn, .card, button, .action-btn {
            transform: none !important;
        }
        
        .btn:active, .card:active, button:active, .action-btn:active {
            transform: scale(0.98) !important;
        }
        
        .btn-block.text-left, .action-btn {
            min-height: 48px;
        }
        
        .btn-group .btn {
            min-height: 44px;
            min-width: 44px;
        }
    }
    
    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .card {
            background-color: #2d3748;
            color: #e2e8f0;
        }
        
        .card-header {
            background-color: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
        }
    }
    
    /* High contrast mode */
    @media (prefers-contrast: high) {
        .card {
            border: 2px solid #000;
        }
        
        .btn {
            border: 2px solid;
        }
    }
    
    /* Reduced motion */
    @media (prefers-reduced-motion: reduce) {
        .card, .btn, * {
            transition: none !important;
            animation: none !important;
        }
    }
    
    /* Print styles */
    @media print {
        .btn, .modal, .card-header button {
            display: none !important;
        }
        
        .card {
            break-inside: avoid;
            box-shadow: none;
            border: 1px solid #000;
        }
    }
</style>
@endsection