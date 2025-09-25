@extends('seller.layouts.app')

@section('panel_content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Edit Warehouse')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('seller.warehouses.index') }}" class="btn btn-secondary">
                <i class="las la-arrow-left"></i>
                {{ translate('Back to Warehouses') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{translate('Warehouse Information')}}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('seller.warehouses.update', $warehouse->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="name">{{translate('Warehouse Name')}} <span class="text-danger">*</span></label>
                        <input type="text" placeholder="{{translate('Warehouse Name')}}" id="name" name="name" class="form-control" value="{{ old('name', $warehouse->name) }}" required>
                        @error('name')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="manager_name">{{translate('Manager Name')}}</label>
                        <input type="text" placeholder="{{translate('Manager Name')}}" id="manager_name" name="manager_name" class="form-control" value="{{ old('manager_name', $warehouse->manager_name) }}">
                        @error('manager_name')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label for="address">{{translate('Address')}} <span class="text-danger">*</span></label>
                <textarea name="address" id="address" rows="3" class="form-control" placeholder="{{translate('Complete Address')}}" required>{{ old('address', $warehouse->address) }}</textarea>
                @error('address')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="email">{{translate('Email')}}</label>
                        <input type="email" placeholder="{{translate('Email Address')}}" id="email" name="email" class="form-control" value="{{ old('email', $warehouse->email) }}">
                        @error('email')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="phone">{{translate('Phone')}}</label>
                        <input type="text" placeholder="{{translate('Phone Number')}}" id="phone" name="phone" class="form-control" value="{{ old('phone', $warehouse->phone) }}">
                        @error('phone')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="city">{{translate('City')}}</label>
                        <input type="text" placeholder="{{translate('City')}}" id="city" name="city" class="form-control" value="{{ old('city', $warehouse->city) }}">
                        @error('city')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="state">{{translate('State')}}</label>
                        <input type="text" placeholder="{{translate('State')}}" id="state" name="state" class="form-control" value="{{ old('state', $warehouse->state) }}">
                        @error('state')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="postal_code">{{translate('Postal Code')}}</label>
                        <input type="text" placeholder="{{translate('Postal Code')}}" id="postal_code" name="postal_code" class="form-control" value="{{ old('postal_code', $warehouse->postal_code) }}">
                        @error('postal_code')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="country">{{translate('Country')}}</label>
                        <input type="text" placeholder="{{translate('Country')}}" id="country" name="country" class="form-control" value="{{ old('country', $warehouse->country) }}">
                        @error('country')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="contact_info">{{translate('Additional Contact Info')}}</label>
                        <input type="text" placeholder="{{translate('Additional Contact Info')}}" id="contact_info" name="contact_info" class="form-control" value="{{ old('contact_info', $warehouse->contact_info) }}">
                        @error('contact_info')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary">{{translate('Update Warehouse')}}</button>
            </div>
        </form>
    </div>
</div>
@endsection
