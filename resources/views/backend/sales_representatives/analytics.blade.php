@extends('backend.layouts.app')

@section('content')
<div class="aiz-main-wrapper">
    <div class="aiz-sidebar-wrap">
        @include('backend.inc.admin_sidenav')
    </div>
    <div class="aiz-content-wrap">
        <div class="aiz-content">

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="container-fluid">
    <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{translate('Performance Analytics')}}</h1>
                <p class="text-muted mb-0">{{translate('Your sales performance insights')}}</p>
            </div>
            <div class="col-md-6 text-md-right">
                <div class="d-flex flex-wrap justify-content-md-end justify-content-center gap-2">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary @if(request('period') == 'daily' || !request('period')) active @endif" 
                                onclick="window.location='{{ route('sales_representatives.analytics', ['period' => 'daily']) }}'">
                            {{translate('Daily')}}
                        </button>
                        <button type="button" class="btn btn-outline-primary @if(request('period') == 'weekly') active @endif" 
                                onclick="window.location='{{ route('sales_representatives.analytics', ['period' => 'weekly']) }}'">
                            {{translate('Weekly')}}
                        </button>
                        <button type="button" class="btn btn-outline-primary @if(request('period') == 'monthly') active @endif" 
                                onclick="window.location='{{ route('sales_representatives.analytics', ['period' => 'monthly']) }}'">
                            {{translate('Monthly')}}
                        </button>
                        <button type="button" class="btn btn-outline-primary @if(request('period') == 'yearly') active @endif" 
                                onclick="window.location='{{ route('sales_representatives.analytics', ['period' => 'yearly']) }}'">
                            {{translate('Yearly')}}
                        </button>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="las la-download"></i> {{translate('Export')}}
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#" onclick="exportData('pdf')">
                                <i class="las la-file-pdf"></i> {{translate('Export PDF')}}
                            </a>
                            <a class="dropdown-item" href="#" onclick="exportData('excel')">
                                <i class="las la-file-excel"></i> {{translate('Export Excel')}}
                            </a>
                        </div>
                    </div>
                    
                    <button class="btn btn-outline-info" onclick="refreshAnalytics()" title="{{translate('Refresh')}}">
                        <i class="las la-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@if($salesRep->id == 0)
<div class="alert alert-info mb-4">
    <div class="d-flex align-items-center">
        <i class="las la-info-circle fs-20 me-2"></i>
        <div>
            <strong>{{translate('Demo Analytics View')}}</strong><br>
            <small>{{translate('No sales representatives found. This is a demo view with sample data structure.')}}</small>
        </div>
    </div>
</div>
@elseif(auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff')
<div class="alert alert-primary mb-4">
    <div class="d-flex align-items-center">
        <i class="las la-user-tie fs-20 me-2"></i>
        <div>
            <strong>{{translate('Admin View')}}</strong> - {{translate('Viewing analytics for')}}: {{ $salesRep->name ?? 'Sales Representative' }}<br>
            <small>{{translate('You are viewing this as an administrator.')}}</small>
        </div>
    </div>
</div>
@endif

<!-- Key Performance Metrics -->
<div class="row">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-gradient-primary text-black h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">{{ number_format($metrics['visits'] ?? 0) }}</h3>
                        <p class="mb-0">{{translate('Total Visits')}}</p>
                        @if(isset($metrics['visits']) && $metrics['visits'] > 0)
                            <small class="opacity-80">
                                @if(request('period') == 'daily')
                                    {{translate('Today')}}
                                @elseif(request('period') == 'weekly')
                                    {{translate('This Week')}}
                                @elseif(request('period') == 'monthly')
                                    {{translate('This Month')}}
                                @else
                                    {{translate('This Year')}}
                                @endif
                            </small>
                        @endif
                    </div>
                    <div class="align-self-center">
                        <i class="las la-store fs-30 opacity-80"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-gradient-success text-black h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">{{ number_format($metrics['completed_visits'] ?? 0) }}</h3>
                        <p class="mb-0">{{translate('Completed Visits')}}</p>
                        <small class="opacity-80">{{ $metrics['success_rate'] ?? 0 }}% {{translate('success rate')}}</small>
                    </div>
                    <div class="align-self-center">
                        <i class="las la-check-circle fs-30 opacity-80"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-gradient-info text-black h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">{{ single_price($metrics['sales_amount'] ?? 0) }}</h3>
                        <p class="mb-0">{{translate('Sales Amount')}}</p>
                        <small class="opacity-80">{{ number_format($metrics['orders'] ?? 0) }} {{translate('orders')}}</small>
                    </div>
                    <div class="align-self-center">
                        <i class="las la-dollar-sign fs-30 opacity-80"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-gradient-warning text-black h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">{{ single_price($metrics['commission_earned'] ?? 0) }}</h3>
                        <p class="mb-0">{{translate('Commission Earned')}}</p>
                        <small class="opacity-80">{{ single_price($metrics['avg_order_value'] ?? 0) }} {{translate('avg order')}}</small>
                    </div>
                    <div class="align-self-center">
                        <i class="las la-coins fs-30 opacity-80"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Performance Trends Chart -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Performance Trends')}}</h5>
            </div>
            <div class="card-body">
                <canvas id="performanceTrendChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Target Achievement -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Target Achievement')}}</h5>
            </div>
            <div class="card-body">
                @php
                    $monthlyTarget = (float) ($salesRep->sales_target_monthly ?? 0);
                    $currentSales = (float) ($metrics['sales_amount'] ?? 0);
                    
                    // Determine target based on selected period
                    $targetAmount = $monthlyTarget;
                    if (request('period') == 'yearly') {
                        $targetAmount = (float) ($salesRep->sales_target_yearly ?? ($monthlyTarget * 12));
                    } elseif (request('period') == 'quarterly') {
                        $targetAmount = (float) ($salesRep->sales_target_quarterly ?? ($monthlyTarget * 3));
                    } elseif (request('period') == 'weekly') {
                        $targetAmount = $monthlyTarget / 4;
                    } elseif (request('period') == 'daily') {
                        $targetAmount = $monthlyTarget / 30;
                    }
                    
                    $achievementPercentage = ($targetAmount > 0) ? min(100, round(($currentSales / $targetAmount) * 100, 2)) : 0;
                @endphp
                
                <div class="text-center mb-3">
                    <div class="position-relative d-inline-block">
                        <svg width="150" height="150">
                            <circle cx="75" cy="75" r="60" fill="none" stroke="#e9ecef" stroke-width="12"></circle>
                            <circle cx="75" cy="75" r="60" fill="none" stroke="#28a745" stroke-width="12"
                                    stroke-dasharray="{{ 2 * 3.14159 * 60 }}"
                                    stroke-dashoffset="{{ 2 * 3.14159 * 60 * (1 - $achievementPercentage/100) }}"
                                    transform="rotate(-90 75 75)"></circle>
                        </svg>
                        <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                            <h3 class="mb-0">{{ number_format($achievementPercentage, 1) }}%</h3>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <p class="mb-1"><strong>
                        @if(request('period') == 'daily')
                            {{translate('Daily Target')}}
                        @elseif(request('period') == 'weekly')
                            {{translate('Weekly Target')}}
                        @elseif(request('period') == 'yearly')
                            {{translate('Yearly Target')}}
                        @else
                            {{translate('Monthly Target')}}
                        @endif
                    </strong></p>
                    <p class="text-muted mb-2">{{ single_price($targetAmount) }}</p>
                    <p class="mb-1"><strong>{{translate('Achieved')}}</strong></p>
                    <p class="text-success mb-0">{{ single_price($currentSales) }}</p>
                    @if($targetAmount > $currentSales)
                        <small class="text-muted">
                            {{translate('Remaining')}}: {{ single_price($targetAmount - $currentSales) }}
                        </small>
                    @endif
                </div>
                
                @if($achievementPercentage >= 100)
                    <div class="alert alert-success mt-3 py-2 text-center">
                        <i class="las la-trophy"></i> {{translate('Target Exceeded!')}} 🎉
                    </div>
                @elseif($achievementPercentage >= 80)
                    <div class="alert alert-warning mt-3 py-2 text-center">
                        <i class="las la-fire"></i> {{translate('Almost there!')}}
                    </div>
                @else
                    <div class="alert alert-info mt-3 py-2 text-center">
                        <i class="las la-bullseye"></i> {{translate('Keep pushing!')}}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Detailed Statistics -->
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Visit Statistics')}}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td>{{translate('Total Scheduled')}}</td>
                                <td class="text-right font-weight-bold">{{ number_format($metrics['visits'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <td>{{translate('Completed Successfully')}}</td>
                                <td class="text-right font-weight-bold text-success">{{ number_format($metrics['completed_visits'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <td>{{translate('Success Rate')}}</td>
                                <td class="text-right font-weight-bold">{{ $metrics['success_rate'] }}%</td>
                            </tr>
                            <tr class="border-top">
                                <td>{{translate('Orders Generated')}}</td>
                                <td class="text-right font-weight-bold text-info">{{ number_format($metrics['orders'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <td>{{translate('Conversion Rate')}}</td>
                                <td class="text-right font-weight-bold">
                                    {{ $metrics['conversion_rate'] ?? 0 }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Sales Statistics')}}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td>{{translate('Total Sales')}}</td>
                                <td class="text-right font-weight-bold">{{ single_price($metrics['sales_amount'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <td>{{translate('Average Order Value')}}</td>
                                <td class="text-right font-weight-bold">{{ single_price($metrics['avg_order_value'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <td>{{translate('Commission Earned')}}</td>
                                <td class="text-right font-weight-bold text-success">{{ single_price($metrics['commission_earned'] ?? 0) }}</td>
                            </tr>
                            <tr class="border-top">
                                <td>{{translate('Commission Rate')}}</td>
                                <td class="text-right font-weight-bold">{{ $salesRep->commission_rate ?? 0 }}%</td>
                            </tr>
                            <tr>
                                <td>{{translate('Sales per Visit')}}</td>
                                <td class="text-right font-weight-bold">
                                    {{ single_price($metrics['sales_per_visit'] ?? 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if(config('app.debug') && request()->has('debug'))
<!-- Debug Information -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Debug Information (Raw Calculations)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Raw Value</th>
                                <th>Formatted Value</th>
                                <th>Calculation Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total Visits</td>
                                <td>{{ $metrics['visits'] }}</td>
                                <td>{{ number_format($metrics['visits'] ?? 0) }}</td>
                                <td><span class="badge badge-success">OK</span></td>
                            </tr>
                            <tr>
                                <td>Completed Visits</td>
                                <td>{{ $metrics['completed_visits'] }}</td>
                                <td>{{ number_format($metrics['completed_visits'] ?? 0) }}</td>
                                <td><span class="badge badge-inline badge-success">OK</span></td>
                            </tr>
                            <tr>
                                <td>Success Rate</td>
                                <td>{{ $metrics['success_rate'] }}</td>
                                <td>{{ $metrics['success_rate'] ?? 0 }}%</td>
                                <td>
                                    @if($metrics['visits'] > 0)
                                        <span class="badge badge-inline badge-success">Calculated</span>
                                    @else
                                        <span class="badge badge-inline badge-warning">No visits</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Orders</td>
                                <td>{{ $metrics['orders'] }}</td>
                                <td>{{ number_format($metrics['orders'] ?? 0) }}</td>
                                <td><span class="badge badge-inline badge-success">OK</span></td>
                            </tr>
                            <tr>
                                <td>Sales Amount</td>
                                <td>{{ $metrics['sales_amount'] }}</td>
                                <td>{{ single_price($metrics['sales_amount'] ?? 0) }}</td>
                                <td><span class="badge badge-inline badge-success">OK</span></td>
                            </tr>
                            <tr>
                                <td>Average Order Value</td>
                                <td>{{ $metrics['avg_order_value'] }}</td>
                                <td>{{ single_price($metrics['avg_order_value'] ?? 0) }}</td>
                                <td>
                                    @if($metrics['orders'] > 0)
                                        <span class="badge badge-inline badge-success">Calculated</span>
                                    @else
                                        <span class="badge badge-inline badge-warning">No orders</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Conversion Rate</td>
                                <td>{{ $metrics['conversion_rate'] }}</td>
                                <td>{{ $metrics['conversion_rate'] ?? 0 }}%</td>
                                <td>
                                    @if($metrics['visits'] > 0)
                                        <span class="badge badge-inline badge-success">Calculated</span>
                                    @else
                                        <span class="badge badge-inline badge-warning">No visits</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Sales per Visit</td>
                                <td>{{ $metrics['sales_per_visit'] }}</td>
                                <td>{{ single_price($metrics['sales_per_visit'] ?? 0) }}</td>
                                <td>
                                    @if($metrics['visits'] > 0)
                                        <span class="badge badge-inline badge-success">Calculated</span>
                                    @else
                                        <span class="badge badge-inline badge-warning">No visits</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

        </div><!-- End .aiz-content -->
    </div><!-- End .aiz-content-wrap -->
</div><!-- End .aiz-main-wrapper -->

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Initialize performance trends chart
        initPerformanceTrendChart();
    });

    function initPerformanceTrendChart() {
        const ctx = document.getElementById('performanceTrendChart');
        if (!ctx) {
            console.error('Performance trend chart canvas not found');
            return;
        }

        // Get trends data from backend with fallback
        const trendsData = @json($trends ?? []);
        console.log('Trends data:', trendsData);

        // Prepare chart data
        const labels = [];
        const visitsData = [];
        const salesData = [];
        const ordersData = [];

        if (trendsData && trendsData.length > 0) {
            trendsData.forEach(function(trend) {
                labels.push(trend.date || 'N/A');
                visitsData.push(trend.visits || 0);
                salesData.push(trend.sales || 0);
                ordersData.push(trend.orders || 0);
            });
        } else {
            // Fallback data if no trends available
            const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            days.forEach(function(day) {
                labels.push(day);
                visitsData.push(0);
                salesData.push(0);
                ordersData.push(0);
            });
        }

        const chartData = {
            labels: labels,
            datasets: [{
                label: '{{translate("Visits")}}',
                data: visitsData,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: false
            }, {
                label: '{{translate("Orders")}}',
                data: ordersData,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4,
                fill: false
            }]
        };

        const config = {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '{{translate("Performance Trends Over Time")}}'
                    },
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        };

        try {
            new Chart(ctx, config);
            console.log('Performance trend chart created successfully');
        } catch (error) {
            console.error('Error creating performance trend chart:', error);
            // Display fallback message
            const chartContainer = ctx.parentElement;
            chartContainer.innerHTML = '<div class="text-center py-5"><i class="las la-chart-line fs-40 text-muted"></i><br><p class="text-muted mt-2">{{translate("Chart data not available")}}</p></div>';
        }
    }

    // Add interactivity to period buttons
    $('.btn-outline-primary').on('click', function() {
        // Add loading state
        $(this).html('<i class="fas fa-spinner fa-spin"></i> {{translate("Loading...")}}');
    });

    // Add refresh functionality
    function refreshAnalytics() {
        window.location.reload();
    }

    // Export data functionality
    function exportData(format) {
        const currentPeriod = '{{ request("period", "daily") }}';
        const exportUrl = '{{ route("sales_representatives.analytics") }}' + `?period=${currentPeriod}&export=${format}`;
        window.open(exportUrl, '_blank');
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
    
    /* Card Styling */
    .card { 
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        border: none;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 25px rgba(0,0,0,0.15);
    }
    
    .card.h-100 {
        height: 100% !important;
    }
    
    /* Button Styling */
    .btn-outline-primary.active {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }
    
    .btn-group .btn {
        border-radius: 0;
    }
    
    .btn-group .btn:first-child {
        border-top-left-radius: 0.375rem;
        border-bottom-left-radius: 0.375rem;
    }
    
    .btn-group .btn:last-child {
        border-top-right-radius: 0.375rem;
        border-bottom-right-radius: 0.375rem;
    }
    
    /* Chart Styling */
    #performanceTrendChart {
        max-height: 400px;
    }
    
    /* Table Styling */
    .table-borderless tbody tr td {
        border: none;
        padding: 0.75rem 0;
    }
    
    .table-borderless tbody tr.border-top td {
        border-top: 1px solid #dee2e6 !important;
        padding-top: 1rem;
    }
    
    /* Alert Styling */
    .alert {
        border-radius: 8px;
        border: none;
    }
    
    /* SVG Circle Animation */
    .card svg circle {
        transition: stroke-dashoffset 1s ease-in-out;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .d-flex.flex-wrap {
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-group {
            width: 100%;
        }
        
        .btn-group .btn {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
        
        .card .fs-30 {
            font-size: 2rem !important;
        }
        
        .card h3 {
            font-size: 1.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .aiz-titlebar .row {
            text-align: center;
        }
        
        .aiz-titlebar .col-md-6:last-child {
            margin-top: 1rem;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .btn-group .btn {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }
    }
    
    /* Loading States */
    .btn .fas.fa-spinner {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Gap utility for older browsers */
    .gap-2 > * + * {
        margin-left: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .gap-2 > * + * {
            margin-left: 0;
            margin-top: 0.5rem;
        }
    }
</style>
@endsection