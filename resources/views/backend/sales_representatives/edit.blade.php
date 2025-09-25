@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Edit Sales Representative')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sales_representatives.index') }}" class="btn btn-info">
                <span>{{translate('Back to list')}}</span>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{translate('Sales Representative Information')}}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('sales_representatives.update', $salesRep->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="user_id" class="form-label">{{translate('User')}} <span class="text-danger">*</span></label>
                        <select name="user_id" id="user_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                            <option value="">{{translate('Select User')}}</option>
                            @foreach($managers as $user)
                                <option value="{{ $user->id }}" {{ $salesRep->user_id == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="territory_id" class="form-label">{{translate('Territory')}}</label>
                        <select name="territory_id" id="territory_id" class="form-control aiz-selectpicker" data-live-search="true">
                            <option value="">{{translate('Select Territory')}}</option>
                            @foreach($territories as $territory)
                                <option value="{{ $territory->id }}" {{ $salesRep->territory_id == $territory->id ? 'selected' : '' }}>
                                    {{ $territory->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('territory_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="employee_id" class="form-label">{{translate('Employee ID')}} <span class="text-danger">*</span></label>
                        <input type="text" name="employee_id" id="employee_id" class="form-control" value="{{ $salesRep->employee_id }}" required>
                        @error('employee_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="hire_date" class="form-label">{{translate('Hire Date')}} <span class="text-danger">*</span></label>
                        <input type="date" name="hire_date" id="hire_date" class="form-control" value="{{ $salesRep->hire_date->format('Y-m-d') }}" required>
                        @error('hire_date')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="commission_rate" class="form-label">{{translate('Commission Rate (%)') }} <span class="text-danger">*</span></label>
                        <input type="number" name="commission_rate" id="commission_rate" class="form-control" step="0.01" min="0" max="100" value="{{ $salesRep->commission_rate }}" required>
                        @error('commission_rate')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="base_salary" class="form-label">{{translate('Base Salary')}}</label>
                        <input type="number" name="base_salary" id="base_salary" class="form-control" step="0.01" min="0" value="{{ $salesRep->base_salary }}">
                        @error('base_salary')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="sales_target_monthly" class="form-label">{{translate('Monthly Sales Target')}}</label>
                        <input type="number" name="sales_target_monthly" id="sales_target_monthly" class="form-control" step="0.01" min="0" value="{{ $salesRep->sales_target_monthly }}">
                        @error('sales_target_monthly')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="sales_target_quarterly" class="form-label">{{translate('Quarterly Sales Target')}}</label>
                        <input type="number" name="sales_target_quarterly" id="sales_target_quarterly" class="form-control" step="0.01" min="0" value="{{ $salesRep->sales_target_quarterly }}">
                        @error('sales_target_quarterly')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="sales_target_yearly" class="form-label">{{translate('Yearly Sales Target')}}</label>
                        <input type="number" name="sales_target_yearly" id="sales_target_yearly" class="form-control" step="0.01" min="0" value="{{ $salesRep->sales_target_yearly }}">
                        @error('sales_target_yearly')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="manager_id" class="form-label">{{translate('Manager')}}</label>
                        <select name="manager_id" id="manager_id" class="form-control aiz-selectpicker" data-live-search="true">
                            <option value="">{{translate('Select Manager (Optional)')}}</option>
                            @foreach($managers as $manager)
                                <option value="{{ $manager->id }}" {{ $salesRep->manager_id == $manager->id ? 'selected' : '' }}>
                                    {{ $manager->display_name }}
                                </option>
                            @endforeach
                            @if(count($managers) == 0)
                                <option value="" disabled>{{translate('No managers available - can be assigned later')}}</option>
                            @endif
                        </select>
                        <small class="form-text text-muted">{{translate('Manager can be assigned later if none available')}}</small>
                        @error('manager_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">{{translate('Status')}}</label>
                        <div class="form-check">
                            <input type="checkbox" name="status" id="status" class="form-check-input" value="1" {{ $salesRep->status ? 'checked' : '' }}>
                            <label for="status" class="form-check-label">{{translate('Active')}}</label>
                        </div>
                        @error('status')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes" class="form-label">{{translate('Notes')}}</label>
                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="{{translate('Additional notes about this sales representative')}}">{{ $salesRep->notes }}</textarea>
                @error('notes')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary">{{translate('Update Sales Representative')}}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        $('.aiz-selectpicker').selectpicker();
    });
</script>
@endsection
