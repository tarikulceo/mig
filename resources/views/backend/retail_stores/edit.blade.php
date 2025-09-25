@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Edit Retail Store')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('retail_stores.index') }}" class="btn btn-info">
                <span>{{translate('Back to list')}}</span>
            </a>
            <a href="{{ route('retail_stores.show', $store->id) }}" class="btn btn-secondary">
                <span>{{translate('View Store')}}</span>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Store Information')}}</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle"></i> {{ translate('Please fix the following errors:') }}</h6>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <form action="{{ route('retail_stores.update', $store->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">{{ translate('Store Name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $store->name) }}" required>
                                @error('name')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="owner_name">{{ translate('Owner Name') }}</label>
                                <input type="text" class="form-control @error('owner_name') is-invalid @enderror" name="owner_name" value="{{ old('owner_name', $store->owner_name) }}">
                                @error('owner_name')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">{{ translate('Phone') }}</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $store->phone) }}">
                                @error('phone')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">{{ translate('Email') }}</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $store->email) }}">
                                @error('email')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="form-group">
                        <label for="address">{{ translate('Address') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3" required>{{ old('address', $store->address) }}</textarea>
                        @error('address')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="getCoordinates()">
                            <i class="fas fa-map-marker-alt"></i> {{ translate('Get Coordinates') }}
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="city">{{ translate('City') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $store->city) }}" required>
                                @error('city')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="state">{{ translate('State') }}</label>
                                <input type="text" class="form-control @error('state') is-invalid @enderror" name="state" value="{{ old('state', $store->state) }}">
                                @error('state')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="country">{{ translate('Country') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('country') is-invalid @enderror" name="country" value="{{ old('country', $store->country) }}" required>
                                @error('country')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="postal_code">{{ translate('Postal Code') }}</label>
                                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" name="postal_code" value="{{ old('postal_code', $store->postal_code) }}">
                                @error('postal_code')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- GPS Coordinates -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="latitude">{{ translate('Latitude') }}</label>
                                <input type="number" step="any" class="form-control @error('latitude') is-invalid @enderror" name="latitude" id="latitude" value="{{ old('latitude', $store->latitude) }}">
                                @error('latitude')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="longitude">{{ translate('Longitude') }}</label>
                                <input type="number" step="any" class="form-control @error('longitude') is-invalid @enderror" name="longitude" id="longitude" value="{{ old('longitude', $store->longitude) }}">
                                @error('longitude')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Map Preview -->
                    <div id="map-preview" style="display: {{ $store->latitude && $store->longitude ? 'block' : 'none' }};">
                        <div id="map" style="height: 300px; margin-bottom: 20px;"></div>
                    </div>

                    <!-- Store Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="store_type">{{ translate('Store Type') }} <span class="text-danger">*</span></label>
                                <select class="form-control @error('store_type') is-invalid @enderror" name="store_type" required>
                                    <option value="retail" {{ old('store_type', $store->store_type) == 'retail' ? 'selected' : '' }}>{{ translate('Retail') }}</option>
                                    <option value="wholesale" {{ old('store_type', $store->store_type) == 'wholesale' ? 'selected' : '' }}>{{ translate('Wholesale') }}</option>
                                    <option value="dealer" {{ old('store_type', $store->store_type) == 'dealer' ? 'selected' : '' }}>{{ translate('Dealer') }}</option>
                                </select>
                                @error('store_type')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="store_size">{{ translate('Store Size (sq ft)') }}</label>
                                <input type="number" step="any" class="form-control @error('store_size') is-invalid @enderror" name="store_size" value="{{ old('store_size', $store->store_size) }}">
                                @error('store_size')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="staff_count">{{ translate('Staff Count') }} <span class="text-danger">*</span></label>
                                <input type="number" min="1" class="form-control @error('staff_count') is-invalid @enderror" name="staff_count" value="{{ old('staff_count', $store->staff_count) }}" required>
                                @error('staff_count')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="established_date">{{ translate('Established Date') }}</label>
                                <input type="date" class="form-control @error('established_date') is-invalid @enderror" name="established_date" value="{{ old('established_date', $store->established_date ? $store->established_date->format('Y-m-d') : '') }}">
                                @error('established_date')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Sales Targets -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monthly_target">{{ translate('Monthly Target') }}</label>
                                <input type="number" step="any" class="form-control @error('monthly_target') is-invalid @enderror" name="monthly_target" value="{{ old('monthly_target', $store->monthly_target) }}">
                                @error('monthly_target')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="yearly_target">{{ translate('Yearly Target') }}</label>
                                <input type="number" step="any" class="form-control @error('yearly_target') is-invalid @enderror" name="yearly_target" value="{{ old('yearly_target', $store->yearly_target) }}">
                                @error('yearly_target')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="notes">{{ translate('Notes') }}</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="4">{{ old('notes', $store->notes) }}</textarea>
                        @error('notes')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Assignment & Images')}}</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="sales_rep_id">{{ translate('Sales Representative') }} <span class="text-danger">*</span></label>
                    <select class="form-control @error('sales_rep_id') is-invalid @enderror" name="sales_rep_id" required>
                        <option value="">{{ translate('Select Sales Rep') }}</option>
                        @foreach($salesReps as $rep)
                            <option value="{{ $rep->id }}" {{ old('sales_rep_id', $store->sales_rep_id) == $rep->id ? 'selected' : '' }}>
                                {{ $rep->user->name }} ({{ $rep->employee_id }})
                            </option>
                        @endforeach
                    </select>
                    @error('sales_rep_id')
                        <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="territory_id">{{ translate('Territory') }}</label>
                    <select class="form-control @error('territory_id') is-invalid @enderror" name="territory_id">
                        <option value="">{{ translate('Select Territory') }}</option>
                        @foreach($territories as $territory)
                            <option value="{{ $territory->id }}" {{ old('territory_id', $store->territory_id) == $territory->id ? 'selected' : '' }}>
                                {{ $territory->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('territory_id')
                        <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="status">{{ translate('Status') }} <span class="text-danger">*</span></label>
                    <select class="form-control @error('status') is-invalid @enderror" name="status" required>
                        <option value="">{{ translate('Select Status') }}</option>
                        <option value="active" {{ old('status', $store->status) == 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                        <option value="inactive" {{ old('status', $store->status) == 'inactive' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                        <option value="pending" {{ old('status', $store->status) == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                    </select>
                    @error('status')
                        <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="image">{{ translate('Store Image') }}</label>
                    @if($store->image)
                        <div class="mb-2">
                            <img src="{{ uploaded_asset($store->image) }}" alt="Store Image" style="max-width: 200px; max-height: 150px;" class="img-thumbnail">
                        </div>
                    @endif
                    <div class="input-group" data-toggle="aizuploader" data-type="image">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                        <input type="hidden" name="image" class="selected-files" value="{{ $store->image }}">
                    </div>
                    <div class="file-preview box sm">
                    </div>
                </div>

                <div class="form-group">
                    <label for="images">{{ translate('Store Gallery') }}</label>
                    @if($store->images && count($store->images) > 0)
                        <div class="mb-2 row">
                            @foreach($store->images as $image)
                                <div class="col-6 mb-2">
                                    <img src="{{ uploaded_asset($image) }}" alt="Store Gallery" style="width: 100%; height: 100px; object-fit: cover;" class="img-thumbnail">
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose Files') }}</div>
                        <input type="hidden" name="images" class="selected-files" value="{{ $store->images ? implode(',', $store->images) : '' }}">
                    </div>
                    <div class="file-preview box sm">
                    </div>
                </div>
                
                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary btn-lg">{{ translate('Update Store') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function getCoordinates() {
        const address = $('textarea[name="address"]').val();
        const city = $('input[name="city"]').val();
        const state = $('input[name="state"]').val();
        const country = $('input[name="country"]').val();
        
        if (!address || !city || !country) {
            alert('{{ translate("Please fill in address, city, and country first") }}');
            return;
        }
        
        const fullAddress = `${address}, ${city}${state ? ', ' + state : ''}, ${country}`;
        
        $.post('{{ route("retail_stores.get_coordinates") }}', {
            _token: '{{ csrf_token() }}',
            address: fullAddress
        })
        .done(function(data) {
            $('#latitude').val(data.latitude);
            $('#longitude').val(data.longitude);
            showMap(data.latitude, data.longitude);
            alert('{{ translate("Coordinates found successfully!") }}');
        })
        .fail(function(xhr) {
            const response = xhr.responseJSON;
            alert('{{ translate("Error: ") }}' + (response ? response.error : 'Unable to get coordinates'));
        });
    }
    
    function showMap(lat, lng) {
        $('#map-preview').show();
        
        @if(env('GOOGLE_MAPS_API_KEY'))
        // Initialize map if Google Maps API is available
        if (typeof google !== 'undefined') {
            const map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: { lat: parseFloat(lat), lng: parseFloat(lng) }
            });
            
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(lat), lng: parseFloat(lng) },
                map: map,
                title: 'Store Location'
            });
        }
        @endif
    }
    
    // Auto-update coordinates when manually entered
    $('#latitude, #longitude').on('input', function() {
        const lat = $('#latitude').val();
        const lng = $('#longitude').val();
        
        if (lat && lng) {
            showMap(lat, lng);
        }
    });
    
    // Show map on page load if coordinates exist
    $(document).ready(function() {
        const lat = $('#latitude').val();
        const lng = $('#longitude').val();
        
        if (lat && lng) {
            showMap(lat, lng);
        }
    });
</script>

<!-- Google Maps API -->
@if(env('GOOGLE_MAPS_API_KEY'))
    <script async defer 
        src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places">
    </script>
@endif

@endsection