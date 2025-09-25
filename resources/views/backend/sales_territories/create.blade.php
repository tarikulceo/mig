@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Add New Territory')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sales_territories.index') }}" class="btn btn-info">
                <span>{{translate('Back to list')}}</span>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{translate('Territory Information')}}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('sales_territories.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name" class="form-label">{{translate('Territory Name')}} <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="{{translate('Enter territory name')}}" required>
                        @error('name')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="region" class="form-label">{{translate('Region')}}</label>
                        <input type="text" name="region" id="region" class="form-control" value="{{ old('region') }}" placeholder="{{translate('Enter region name')}}">
                        @error('region')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="country_id" class="form-label">{{translate('Country')}} <span class="text-danger">*</span></label>
                        <select name="country_id" id="country_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                            <option value="">{{translate('Select Country')}}</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="state_id" class="form-label">{{translate('State')}}</label>
                        <select name="state_id" id="state_id" class="form-control aiz-selectpicker" data-live-search="true">
                            <option value="">{{translate('Select State')}}</option>
                        </select>
                        @error('state_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="cities" class="form-label">{{translate('Cities')}}</label>
                <input type="text" name="cities" id="cities" class="form-control" value="{{ old('cities') }}" placeholder="{{translate('Enter cities separated by commas (leave empty for all cities)')}}">
                <small class="form-text text-muted">{{translate('Enter specific cities separated by commas, or leave empty to include all cities in the state/country')}}</small>
                @error('cities')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">{{translate('Description')}}</label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="{{translate('Enter territory description')}}">{{ old('description') }}</textarea>
                @error('description')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label">{{translate('Active')}}</label>
                </div>
                @error('is_active')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary">{{translate('Save Territory')}}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        $('.aiz-selectpicker').selectpicker();
        
        $('#country_id').on('change', function() {
            var country_id = $(this).val();
            if(country_id && country_id !== '') {
                get_states(country_id);
            } else {
                $('#state_id').html('<option value="">{{translate("Select State")}}</option>');
                $('#state_id').selectpicker('refresh');
            }
        });
    });
    
    function get_states(country_id) {
        $.post('{{ route('get-state') }}', {
            _token: '{{ csrf_token() }}',
            country_id: country_id
        }, function(data) {
            try {
                // The response is HTML wrapped in JSON, so we need to parse it
                var htmlOptions = JSON.parse(data);
                $('#state_id').html(htmlOptions);
                $('#state_id').selectpicker('refresh');
            } catch (e) {
                $('#state_id').html('<option value="">{{translate("Select State")}}</option>');
                $('#state_id').selectpicker('refresh');
            }
        }).fail(function(xhr, status, error) {
            $('#state_id').html('<option value="">{{translate("Select State")}}</option>');
            $('#state_id').selectpicker('refresh');
        });
    }
</script>
@endsection
