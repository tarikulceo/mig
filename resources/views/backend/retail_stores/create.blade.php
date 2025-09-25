@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Add New Retail Store')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('retail_stores.index') }}" class="btn btn-info">
                <span>{{translate('Back to list')}}</span>
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
                
                <form action="{{ route('retail_stores.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">{{ translate('Store Name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="owner_name">{{ translate('Owner Name') }}</label>
                                <input type="text" class="form-control @error('owner_name') is-invalid @enderror" name="owner_name" value="{{ old('owner_name') }}">
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
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">{{ translate('Email') }}</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">{{ translate('Address') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3" required>{{ old('address') }}</textarea>
                        <button type="button" class="btn btn-sm btn-info mt-2" onclick="getCoordinatesFromAddress()">
                            <i class="las la-map-marker-alt"></i> {{ translate('Get GPS Coordinates') }}
                        </button>
                        @error('address')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="city">{{ translate('City') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city') }}" required>
                                @error('city')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="state">{{ translate('State') }}</label>
                                <input type="text" class="form-control @error('state') is-invalid @enderror" name="state" value="{{ old('state') }}">
                                @error('state')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="country">{{ translate('Country') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('country') is-invalid @enderror" name="country" value="{{ old('country', 'Bangladesh') }}" required>
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
                                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" name="postal_code" value="{{ old('postal_code') }}">
                                @error('postal_code')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- GPS Coordinates -->
                    <div class="form-group">
                        <label>{{ translate('GPS Coordinates') }}</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="number" step="any" class="form-control @error('latitude') is-invalid @enderror" name="latitude" id="latitude" value="{{ old('latitude') }}" placeholder="{{ translate('Latitude') }}">
                                @error('latitude')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <input type="number" step="any" class="form-control @error('longitude') is-invalid @enderror" name="longitude" id="longitude" value="{{ old('longitude') }}" placeholder="{{ translate('Longitude') }}">
                                @error('longitude')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Map Preview -->
                    <div id="map-preview" style="display: none;">
                        <div id="map" style="height: 300px; margin: 15px 0;"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="store_type">{{ translate('Store Type') }} <span class="text-danger">*</span></label>
                                <select class="form-control aiz-selectpicker @error('store_type') is-invalid @enderror" name="store_type" required>
                                    <option value="">{{ translate('Select Type') }}</option>
                                    <option value="retail" {{ old('store_type') == 'retail' ? 'selected' : '' }}>{{ translate('Retail') }}</option>
                                    <option value="wholesale" {{ old('store_type') == 'wholesale' ? 'selected' : '' }}>{{ translate('Wholesale') }}</option>
                                    <option value="dealer" {{ old('store_type') == 'dealer' ? 'selected' : '' }}>{{ translate('Dealer') }}</option>
                                </select>
                                @error('store_type')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="store_size">{{ translate('Store Size (sq ft)') }}</label>
                                <input type="number" step="any" class="form-control @error('store_size') is-invalid @enderror" name="store_size" value="{{ old('store_size') }}">
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
                                <input type="number" min="1" class="form-control @error('staff_count') is-invalid @enderror" name="staff_count" value="{{ old('staff_count', 1) }}" required>
                                @error('staff_count')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="established_date">{{ translate('Established Date') }}</label>
                                <input type="date" class="form-control @error('established_date') is-invalid @enderror" name="established_date" value="{{ old('established_date') }}">
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
                                <input type="number" step="any" class="form-control @error('monthly_target') is-invalid @enderror" name="monthly_target" value="{{ old('monthly_target', 0) }}">
                                @error('monthly_target')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="yearly_target">{{ translate('Yearly Target') }}</label>
                                <input type="number" step="any" class="form-control @error('yearly_target') is-invalid @enderror" name="yearly_target" value="{{ old('yearly_target', 0) }}">
                                @error('yearly_target')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">{{ translate('Notes') }}</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group text-right">
                        <button type="submit" class="btn btn-primary">{{ translate('Save Store') }}</button>
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
                            @php
                                $selected = (old('sales_rep_id') == $rep->id) || 
                                           ($selectedSalesRep && $selectedSalesRep->id == $rep->id) ||
                                           ($salesReps->count() == 1);
                            @endphp
                            <option value="{{ $rep->id }}" {{ $selected ? 'selected' : '' }}>
                                {{ $rep->user->name }} ({{ $rep->employee_id }})
                            </option>
                        @endforeach
                    </select>
                    @error('sales_rep_id')
                        <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                    @if($salesReps->count() == 0)
                        <small class="form-text text-warning">
                            <i class="fas fa-exclamation-triangle"></i> No active sales representatives found. Please create one first.
                        </small>
                    @endif
                </div>

                <div class="form-group">
                    <label for="territory_id">{{ translate('Territory') }}</label>
                    <select class="form-control @error('territory_id') is-invalid @enderror" name="territory_id">
                        <option value="">{{ translate('Select Territory') }}</option>
                        @foreach($territories as $territory)
                            <option value="{{ $territory->id }}" {{ old('territory_id') == $territory->id ? 'selected' : '' }}>
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
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                    </select>
                    @error('status')
                        <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="image">{{ translate('Store Image') }}</label>
                    <div class="input-group" data-toggle="aizuploader" data-type="image">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                        <input type="hidden" name="image" class="selected-files">
                    </div>
                    <div class="file-preview box sm">
                    </div>
                </div>

                <div class="form-group">
                    <label for="images">{{ translate('Store Gallery') }}</label>
                    <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose Files') }}</div>
                        <input type="hidden" name="images" class="selected-files">
                    </div>
                    <div class="file-preview box sm">
                    </div>
                </div>
                
                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary btn-lg">{{ translate('Save Store') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>
</div>

@endsection

@section('script')
    <script>
        function getCoordinatesFromAddress() {
            const address = $('textarea[name="address"]').val();
            const city = $('input[name="city"]').val();
            const country = $('input[name="country"]').val();
            
            if (!address || !city) {
                AIZ.plugins.notify('danger', '{{ translate("Please enter address and city first") }}');
                return;
            }
            
            const fullAddress = address + ', ' + city + ', ' + country;
            
            $.ajax({
                url: '{{ route("retail_stores.get_coordinates") }}',
                type: 'POST',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'address': fullAddress
                },
                beforeSend: function() {
                    $('.btn-info').prop('disabled', true).html('<i class="las la-spinner la-spin"></i> {{ translate("Getting coordinates...") }}');
                },
                success: function(response) {
                    $('#latitude').val(response.latitude);
                    $('#longitude').val(response.longitude);
                    showMap(response.latitude, response.longitude);
                    AIZ.plugins.notify('success', '{{ translate("Coordinates retrieved successfully") }}');
                },
                error: function(xhr) {
                    const error = xhr.responseJSON ? xhr.responseJSON.error : '{{ translate("Failed to get coordinates") }}';
                    AIZ.plugins.notify('danger', error);
                },
                complete: function() {
                    $('.btn-info').prop('disabled', false).html('<i class="las la-map-marker-alt"></i> {{ translate("Get GPS Coordinates") }}');
                }
            });
        }
        
        function showMap(lat, lng) {
            $('#map-preview').show();
            
            // Initialize map
            const map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: { lat: parseFloat(lat), lng: parseFloat(lng) }
            });
            
            // Add marker
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(lat), lng: parseFloat(lng) },
                map: map,
                title: 'Store Location'
            });
        }
        
        // Auto-update coordinates when manually entered
        $('#latitude, #longitude').on('input', function() {
            const lat = $('#latitude').val();
            const lng = $('#longitude').val();
            
            if (lat && lng) {
                showMap(lat, lng);
            }
        });

        // No JS for select fields, use plain HTML only
    </script>
    
    <!-- Google Maps API -->
    @if(env('GOOGLE_MAPS_API_KEY'))
        <script async defer 
            src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places">
        </script>
    @endif
@endsection