@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Add New Store Visit')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('store_visits.index') }}" class="btn btn-info">
                <span>{{translate('Back to list')}}</span>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{translate('Visit Information')}}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('store_visits.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="retail_store_id">{{ translate('Select Store') }} <span class="text-danger">*</span></label>
                        <select class="form-control aiz-selectpicker @error('retail_store_id') is-invalid @enderror" name="retail_store_id" required>
                            <option value="">{{ translate('Select Store') }}</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" 
                                    {{ (old('retail_store_id') == $store->id || ($selectedStore && $selectedStore->id == $store->id)) ? 'selected' : '' }}>
                                    {{ $store->name }} ({{ $store->store_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('retail_store_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="visit_date">{{ translate('Visit Date') }} <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control @error('visit_date') is-invalid @enderror" 
                               name="visit_date" value="{{ old('visit_date') }}" required>
                        @error('visit_date')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="purpose">{{ translate('Visit Purpose') }}</label>
                        <input type="text" class="form-control @error('purpose') is-invalid @enderror" 
                               name="purpose" value="{{ old('purpose') }}" placeholder="e.g., New order, Follow-up, Stock check">
                        @error('purpose')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="visit_status">{{ translate('Status') }} <span class="text-danger">*</span></label>
                        <select class="form-control @error('visit_status') is-invalid @enderror" name="visit_status" required>
                            <option value="scheduled" {{ old('visit_status') == 'scheduled' ? 'selected' : '' }}>{{ translate('Scheduled') }}</option>
                            <option value="completed" {{ old('visit_status') == 'completed' ? 'selected' : '' }}>{{ translate('Completed') }}</option>
                            <option value="cancelled" {{ old('visit_status') == 'cancelled' ? 'selected' : '' }}>{{ translate('Cancelled') }}</option>
                        </select>
                        @error('visit_status')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="order_amount">{{ translate('Order Amount') }}</label>
                <input type="number" step="0.01" min="0" class="form-control @error('order_amount') is-invalid @enderror" 
                       name="order_amount" value="{{ old('order_amount', 0) }}" placeholder="0.00">
                @error('order_amount')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="notes">{{ translate('Notes') }}</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="4">{{ old('notes') }}</textarea>
                @error('notes')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group text-right">
                <button type="submit" class="btn btn-primary">{{ translate('Save Visit') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection