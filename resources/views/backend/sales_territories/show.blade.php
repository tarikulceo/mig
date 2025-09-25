@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Territory Details')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sales_territories.index') }}" class="btn btn-info">
                <span>{{translate('Back to list')}}</span>
            </a>
            @can('manage_sales_territories')
                <a href="{{ route('sales_territories.edit', $territory->id) }}" class="btn btn-primary">
                    <span>{{translate('Edit Territory')}}</span>
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Territory Information')}}</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="30%">{{translate('Name')}}:</th>
                        <td>{{ $territory->name }}</td>
                    </tr>
                    <tr>
                        <th>{{translate('Region')}}:</th>
                        <td>{{ $territory->region ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>{{translate('Country')}}:</th>
                        <td>{{ $territory->country->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>{{translate('State')}}:</th>
                        <td>{{ $territory->state->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>{{translate('Cities')}}:</th>
                        <td>
                            @if($territory->cities)
                                <div class="d-flex flex-wrap">
                                    @foreach(explode(',', $territory->cities) as $city)
                                        <span class="badge badge-secondary mr-1 mb-1">{{ trim($city) }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">{{translate('All cities')}}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>{{translate('Description')}}:</th>
                        <td>{{ $territory->description ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>{{translate('Status')}}:</th>
                        <td>
                            @if($territory->is_active)
                                <span class="badge badge-success">{{translate('Active')}}</span>
                            @else
                                <span class="badge badge-secondary">{{translate('Inactive')}}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>{{translate('Created')}}:</th>
                        <td>{{ $territory->created_at->format('M d, Y \a\t h:i A') }}</td>
                    </tr>
                    <tr>
                        <th>{{translate('Last Updated')}}:</th>
                        <td>{{ $territory->updated_at->format('M d, Y \a\t h:i A') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Assigned Sales Representatives')}}</h5>
            </div>
            <div class="card-body">
                @if($territory->salesRepresentatives->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{translate('Name')}}</th>
                                    <th>{{translate('Employee ID')}}</th>
                                    <th>{{translate('Status')}}</th>
                                    <th>{{translate('Action')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($territory->salesRepresentatives as $rep)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs mr-2">
                                                    <img src="{{ uploaded_asset($rep->user->avatar ?? '') }}" alt="avatar" class="rounded-circle w-100 h-100" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $rep->user->name }}</h6>
                                                    <small class="text-muted">{{ $rep->user->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $rep->employee_id }}</td>
                                        <td>
                                            @if($rep->status)
                                                <span class="badge badge-success">{{translate('Active')}}</span>
                                            @else
                                                <span class="badge badge-secondary">{{translate('Inactive')}}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('sales_representatives.show', $rep->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('View') }}">
                                                <i class="las la-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <i class="las la-users text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="text-muted">{{translate('No sales representatives assigned')}}</h6>
                        <p class="text-muted">{{translate('Assign sales representatives to this territory to see them here')}}</p>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Territory Statistics')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center p-3 border rounded">
                            <h3 class="mb-1 text-primary">{{ $territory->salesRepresentatives->count() }}</h3>
                            <p class="mb-0 text-muted">{{translate('Sales Reps')}}</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 border rounded">
                            <h3 class="mb-1 text-success">{{ $territory->salesRepresentatives->where('status', 1)->count() }}</h3>
                            <p class="mb-0 text-muted">{{translate('Active Reps')}}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
