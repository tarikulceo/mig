@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Store Details')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('retail_stores.index') }}" class="btn btn-info mr-2">
                <span>{{translate('Back to list')}}</span>
            </a>
            @if(auth()->user()->user_type === 'sales_rep' || auth()->user()->can('edit_retail_store'))
                <a href="{{ route('retail_stores.edit', $store->id) }}" class="btn btn-primary">
                    <span>{{translate('Edit Store')}}</span>
                </a>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Store Information')}}</h5>
            </div>
            <div class="card-body text-center">
                @if($store->image)
                    <img src="{{ uploaded_asset($store->image) }}" alt="Store Image" class="img-fluid rounded mb-3" style="max-height: 200px;">
                @else
                    <div class="bg-soft-primary rounded p-4 mb-3">
                        <i class="las la-store text-primary" style="font-size: 4rem;"></i>
                    </div>
                @endif
                
                <h5 class="fw-600 mb-1">{{ $store->name }}</h5>
                <p class="opacity-60 mb-3">{{ $store->store_code }}</p>
                
                <span class="badge badge-inline badge-{{ $store->status_badge }} mb-3">{{ ucfirst($store->status) }}</span>
                
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-left">{{ translate('Owner') }}:</td>
                        <td class="text-right">{{ $store->owner_name ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">{{ translate('Phone') }}:</td>
                        <td class="text-right">{{ $store->phone ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">{{ translate('Email') }}:</td>
                        <td class="text-right">{{ $store->email ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">{{ translate('Type') }}:</td>
                        <td class="text-right">
                            <span class="badge badge-inline badge-success mb-3">{{ ucfirst($store->store_type) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left">{{ translate('Size') }}:</td>
                        <td class="text-right">{{ $store->store_size ? number_format($store->store_size) . ' sq ft' : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">{{ translate('Staff') }}:</td>
                        <td class="text-right">{{ $store->staff_count }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">{{ translate('Established') }}:</td>
                        <td class="text-right">{{ $store->established_date ? $store->established_date->format('M d, Y') : 'N/A' }}</td>
                    </tr>
                    <tr class="bg-soft-danger">
                        <td class="text-left font-weight-bold">{{ translate('Due Amount') }}:</td>
                        <td class="text-right">
                            @if($monthlyStats['total_due_amount'] > 0)
                                <span class="text-danger font-weight-bold">{{ single_price($monthlyStats['total_due_amount']) }}</span>
                            @else
                                <span class="text-success">{{ translate('No Due') }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Assignment')}}</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>{{ translate('Sales Representative') }}:</strong><br>
                    @if($store->salesRepresentative)
                        <div class="d-flex align-items-center mt-2">
                            <div class="avatar avatar-xs mr-2">
                                <img src="{{ $store->salesRepresentative->user->avatar ?? static_asset('assets/img/placeholder.jpg') }}" alt="Avatar" class="rounded-circle">
                            </div>
                            <div>
                                <div class="fs-14 fw-600">{{ $store->salesRepresentative->user->name }}</div>
                                <div class="fs-12 opacity-60">{{ $store->salesRepresentative->employee_id }}</div>
                            </div>
                        </div>
                    @else
                        <span class="text-muted">Not assigned</span>
                    @endif
                </div>

                <div class="mb-3">
                    <strong>{{ translate('Territory') }}:</strong><br>
                    @if($store->territory)
                        <span class="badge badge-inline badge-success mb-3">{{ $store->territory->name }}</span>
                    @else
                        <span class="text-muted">Not assigned</span>
                    @endif
                </div>

                <div>
                    <strong>{{ translate('Created By') }}:</strong><br>
                    @if($store->createdBy)
                        {{ $store->createdBy->name }}
                    @else
                        <span class="text-muted">Unknown</span>
                    @endif
                    <div class="fs-12 opacity-60">{{ $store->created_at->format('M d, Y h:i A') }}</div>
                </div>
            </div>
        </div>

        <!-- Due Amounts Section -->
        @if($monthlyStats['total_due_amount'] > 0)
        <div class="card mt-3">
            <div class="card-header bg-soft-danger">
                <h5 class="mb-0 h6 text-danger">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{translate('Due Payments')}}
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="display-4 text-danger font-weight-bold">
                        {{ single_price($monthlyStats['total_due_amount']) }}
                    </div>
                    <small class="text-muted">{{translate('Total Outstanding Amount')}}</small>
                </div>
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-right">
                            <div class="font-weight-bold text-warning">{{ $monthlyStats['orders_with_due'] }}</div>
                            <small class="text-muted">{{translate('Orders')}}</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="font-weight-bold text-info">{{ $monthlyStats['total_orders'] }}</div>
                        <small class="text-muted">{{translate('Total Orders')}}</small>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="{{ route('store_orders.due_amounts', ['retail_store_id' => $store->id]) }}" 
                       class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-eye mr-1"></i>
                        {{translate('View Due Orders')}}
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-8">
        <!-- Monthly Statistics -->
        <div class="row gutters-10 mb-3">
            <div class="col-md-3">
                <div class="card card-statistic-2">
                    <div class="card-icon shadow-primary bg-primary">
                        <i class="fas fa-walking"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>{{translate('Visits')}}</h4>
                        </div>
                        <div class="card-body">
                            {{ $monthlyStats['visits_count'] }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic-2">
                    <div class="card-icon shadow-primary bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>{{translate('Completed')}}</h4>
                        </div>
                        <div class="card-body">
                            {{ $monthlyStats['completed_visits'] }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic-2">
                    <div class="card-icon shadow-primary bg-warning">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>{{translate('Orders')}}</h4>
                        </div>
                        <div class="card-body">
                            {{ $monthlyStats['total_orders'] }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic-2">
                    <div class="card-icon shadow-primary bg-info">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>{{translate('Sales')}}</h4>
                        </div>
                        <div class="card-body">
                            {{ single_price($monthlyStats['monthly_sales']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Due Amount Information -->
        <div class="row gutters-10 mb-3">
            <div class="col-md-6">
                <div class="card card-statistic-2">
                    <div class="card-icon shadow-primary bg-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>{{translate('Total Due Amount')}}</h4>
                        </div>
                        <div class="card-body">
                            <span class="text-danger font-weight-bold">{{ single_price($monthlyStats['total_due_amount'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-statistic-2">
                    <div class="card-icon shadow-primary bg-warning">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>{{translate('Orders with Due')}}</h4>
                        </div>
                        <div class="card-body">
                            <span class="text-warning font-weight-bold">{{ $monthlyStats['orders_with_due'] ?? 0 }}</span>
                            @if($monthlyStats['orders_with_due'] > 0)
                                <small class="text-muted d-block">
                                    <a href="{{ route('store_orders.due_amounts', ['retail_store_id' => $store->id]) }}" class="text-primary">
                                        {{translate('View Details')}}
                                    </a>
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location & Map -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Location')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>{{ translate('Address') }}:</strong><br>
                        {{ $store->full_address }}
                        
                        @if($store->latitude && $store->longitude)
                            <div class="mt-2">
                                <strong>{{ translate('GPS Coordinates') }}:</strong><br>
                                <small class="text-muted">{{ $store->latitude }}, {{ $store->longitude }}</small>
                                <a href="{{ $store->map_url }}" target="_blank" class="btn btn-xs btn-info ml-2">
                                    <i class="las la-external-link-alt"></i> {{ translate('Open in Maps') }}
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        @if($store->latitude && $store->longitude)
                            <div id="store-map" style="height: 250px; border-radius: 8px;"></div>
                        @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 250px;">
                                <div class="text-center text-muted">
                                    <i class="las la-map-marker-alt" style="font-size: 3rem;"></i>
                                    <p class="mb-0">{{ translate('No GPS coordinates available') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Targets -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Sales Targets')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="text-center p-3 border rounded">
                            <h4 class="text-primary">{{ single_price($store->monthly_target) }}</h4>
                            <p class="mb-0">{{ translate('Monthly Target') }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center p-3 border rounded">
                            <h4 class="text-success">{{ single_price($store->yearly_target) }}</h4>
                            <p class="mb-0">{{ translate('Yearly Target') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Visits -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Recent Visits')}}</h5>
                <a href="{{ route('store_visits.create') }}?store_id={{ $store->id }}" class="btn btn-sm btn-primary">
                    <i class="las la-plus"></i> {{ translate('Add Visit') }}
                </a>
            </div>
            <div class="card-body">
                @if($recentVisits->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ translate('Date') }}</th>
                                    <th>{{ translate('Purpose') }}</th>
                                    <th>{{ translate('Sales Rep') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                    <th>{{ translate('Order Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentVisits as $visit)
                                    <tr>
                                        <td>{{ $visit->visit_date->format('M d, Y') }}</td>
                                        <td>{{ $visit->purpose ?: 'General visit' }}</td>
                                        <td>{{ $visit->salesRepresentative->user->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $visit->status_badge }}">
                                                {{ ucfirst($visit->visit_status) }}
                                            </span>
                                        </td>
                                        <td>{{ single_price($visit->order_amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="las la-calendar-times" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="text-muted mb-0">{{ translate('No visits recorded yet') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Store Gallery -->
        @if($store->images && count($store->images) > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Store Gallery')}}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($store->images as $image)
                            <div class="col-md-4 mb-3">
                                <img src="{{ uploaded_asset($image) }}" alt="Store Image" class="img-fluid rounded">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Notes -->
        @if($store->notes)
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Notes')}}</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $store->notes }}</p>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@section('script')
    @if($store->latitude && $store->longitude && env('GOOGLE_MAPS_API_KEY'))
        <script>
            function initMap() {
                const storeLocation = { 
                    lat: {{ $store->latitude }}, 
                    lng: {{ $store->longitude }} 
                };
                
                const map = new google.maps.Map(document.getElementById('store-map'), {
                    zoom: 15,
                    center: storeLocation
                });
                
                const marker = new google.maps.Marker({
                    position: storeLocation,
                    map: map,
                    title: '{{ $store->name }}'
                });
                
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div>
                            <h6>{{ $store->name }}</h6>
                            <p class="mb-0"><small>{{ $store->full_address }}</small></p>
                        </div>
                    `
                });
                
                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
            }
        </script>
        
        <script async defer 
            src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&callback=initMap">
        </script>
    @endif
@endsection