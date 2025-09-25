@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h1 class="mb-0 h6">{{translate('Add New Warehouse')}}</h1>
</div>

<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body p-0">
            <form class="p-4" action="{{ route('warehouses.store') }}" method="POST">
                @csrf
                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="name">{{translate('Warehouse Name')}} <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Warehouse Name')}}" id="name" name="name" class="form-control" required>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="address">{{translate('Address')}} <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <textarea placeholder="{{translate('Address')}}" id="address" name="address" class="form-control" rows="4" required></textarea>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="manager_name">{{translate('Manager Name')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Manager Name')}}" id="manager_name" name="manager_name" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="email">{{translate('Email')}}</label>
                    <div class="col-sm-9">
                        <input type="email" placeholder="{{translate('Email')}}" id="email" name="email" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="phone">{{translate('Phone')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Phone')}}" id="phone" name="phone" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="city">{{translate('City')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('City')}}" id="city" name="city" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="state">{{translate('State')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('State')}}" id="state" name="state" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="country">{{translate('Country')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Country')}}" id="country" name="country" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="postal_code">{{translate('Postal Code')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Postal Code')}}" id="postal_code" name="postal_code" class="form-control">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label">{{translate('Coordinates')}}</label>
                    <div class="col-sm-9">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="number" step="any" placeholder="{{translate('Latitude')}}" name="latitude" class="form-control" min="-90" max="90">
                            </div>
                            <div class="col-md-6">
                                <input type="number" step="any" placeholder="{{translate('Longitude')}}" name="longitude" class="form-control" min="-180" max="180">
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
                                <option value="{{ $seller->id }}">
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
                            <input value="1" name="is_active" type="checkbox" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary">{{translate('Save')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
