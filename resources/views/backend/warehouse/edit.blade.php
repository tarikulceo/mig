@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h1 class="mb-0 h6">{{translate('Edit Warehouse')}}</h1>
</div>

<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body p-0">
            <form class="p-4" action="{{ route('warehouses.update', $warehouse->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="name">{{translate('Warehouse Name')}} <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Warehouse Name')}}" id="name" name="name" value="{{ $warehouse->name }}" class="form-control" required>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="address">{{translate('Address')}} <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <textarea placeholder="{{translate('Address')}}" id="address" name="address" class="form-control" rows="4" required>{{ $warehouse->address }}</textarea>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="manager_name">{{translate('Manager Name')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Manager Name')}}" id="manager_name" name="manager_name" value="{{ $warehouse->manager_name }}" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="email">{{translate('Email')}}</label>
                    <div class="col-sm-9">
                        <input type="email" placeholder="{{translate('Email')}}" id="email" name="email" value="{{ $warehouse->email }}" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="phone">{{translate('Phone')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Phone')}}" id="phone" name="phone" value="{{ $warehouse->phone }}" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="city">{{translate('City')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('City')}}" id="city" name="city" value="{{ $warehouse->city }}" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="state">{{translate('State')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('State')}}" id="state" name="state" value="{{ $warehouse->state }}" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="country">{{translate('Country')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Country')}}" id="country" name="country" value="{{ $warehouse->country }}" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="postal_code">{{translate('Postal Code')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Postal Code')}}" id="postal_code" name="postal_code" value="{{ $warehouse->postal_code }}" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label">{{translate('Coordinates')}}</label>
                    <div class="col-sm-9">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="number" step="any" placeholder="{{translate('Latitude')}}" name="latitude" value="{{ $warehouse->latitude }}" class="form-control" min="-90" max="90">
                            </div>
                            <div class="col-md-6">
                                <input type="number" step="any" placeholder="{{translate('Longitude')}}" name="longitude" value="{{ $warehouse->longitude }}" class="form-control" min="-180" max="180">
                            </div>
                        </div>
                        <small class="text-muted">{{translate('Optional: For location-based order assignment')}}</small>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label">{{translate('Assign to Sellers')}}</label>
                    <div class="col-sm-9">
                        <select name="managed_by[]" class="form-control aiz-selectpicker" multiple data-live-search="true" data-placeholder="{{translate('Select Sellers')}}">
                            @foreach(\App\Models\User::where('user_type', 'seller')->get() as $seller)
                                <option value="{{ $seller->id }}" 
                                    @if(is_array($warehouse->managed_by) && in_array($seller->id, $warehouse->managed_by)) selected @endif>
                                    {{ $seller->name }} ({{ $seller->email }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{translate('Select which sellers can manage this warehouse')}}</small>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label">{{translate('Status')}}</label>
                    <div class="col-sm-9">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input value="1" name="is_active" type="checkbox" @if($warehouse->is_active) checked @endif>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary">{{translate('Update')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
