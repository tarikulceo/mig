@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <h1 class="mb-0 h6">{{translate('Add Sales Representative')}}</h1>
</div>

<div class="col-lg-8 mx-auto">
    <!-- Display validation errors -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <h5>❌ Validation Errors:</h5>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('sales_representatives.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">{{translate('Name')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" placeholder="{{translate('Name')}}" value="{{old('name')}}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">{{translate('Email')}} <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" placeholder="{{translate('Email')}}" value="{{old('email')}}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">{{translate('Password')}} <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" id="password" placeholder="{{translate('Password')}}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">{{translate('Phone')}}</label>
                            <input type="text" class="form-control" name="phone" id="phone" placeholder="{{translate('Phone')}}" value="{{old('phone')}}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="employee_id">{{translate('Employee ID')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('employee_id') is-invalid @enderror" name="employee_id" id="employee_id" placeholder="{{translate('Employee ID')}}" value="{{old('employee_id')}}" required>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="designation">{{translate('Designation')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="designation" id="designation" placeholder="{{translate('Designation')}}" value="{{old('designation')}}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="hire_date">{{translate('Hire Date')}} <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="hire_date" id="hire_date" value="{{old('hire_date')}}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="territory_id">{{translate('Territory')}} <span class="text-danger">*</span></label>
                            <select class="form-control aiz-selectpicker @error('territory_id') is-invalid @enderror" name="territory_id" id="territory_id" required>
                                <option value="">{{translate('Select Territory')}}</option>
                                @foreach($territories as $territory)
                                    <option value="{{ $territory->id }}" {{ old('territory_id') == $territory->id ? 'selected' : '' }}>
                                        {{ $territory->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('territory_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if(count($territories) == 0)
                                <small class="text-danger">No territories available. <a href="/mgo/setup_sales_data.php" target="_blank">Create territories first</a></small>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="commission_rate">{{translate('Commission Rate')}} (%) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="commission_rate" id="commission_rate" placeholder="0.00" value="{{old('commission_rate')}}" step="0.01" min="0" max="100" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="base_salary">{{translate('Base Salary')}} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="base_salary" id="base_salary" placeholder="0.00" value="{{old('base_salary')}}" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sales_target_monthly">{{translate('Monthly Target')}}</label>
                            <input type="number" class="form-control" name="sales_target_monthly" id="sales_target_monthly" placeholder="0.00" value="{{old('sales_target_monthly')}}" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sales_target_quarterly">{{translate('Quarterly Target')}}</label>
                            <input type="number" class="form-control" name="sales_target_quarterly" id="sales_target_quarterly" placeholder="0.00" value="{{old('sales_target_quarterly')}}" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sales_target_yearly">{{translate('Yearly Target')}}</label>
                            <input type="number" class="form-control" name="sales_target_yearly" id="sales_target_yearly" placeholder="0.00" value="{{old('sales_target_yearly')}}" step="0.01" min="0">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="manager_id">{{translate('Manager')}}</label>
                            <select class="form-control aiz-selectpicker" name="manager_id" id="manager_id">
                                <option value="">{{translate('Select Manager (Optional)')}}</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}" {{ old('manager_id') == $manager->id ? 'selected' : '' }}>
                                        {{ $manager->display_name }}
                                    </option>
                                @endforeach
                                @if(count($managers) == 0)
                                    <option value="" disabled>{{translate('No managers available - can be assigned later')}}</option>
                                @endif
                            </select>
                            <small class="form-text text-muted">{{translate('Manager can be assigned later if none available')}}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">{{translate('Status')}}</label>
                            <select class="form-control aiz-selectpicker" name="status" id="status">
                                <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>{{translate('Active')}}</option>
                                <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>{{translate('Inactive')}}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">{{translate('Address')}}</label>
                    <textarea class="form-control" name="address" id="address" rows="3" placeholder="{{translate('Address')}}">{{old('address')}}</textarea>
                </div>

                <div class="form-group">
                    <label for="notes">{{translate('Notes')}}</label>
                    <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="{{translate('Notes')}}">{{old('notes')}}</textarea>
                </div>

                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary" id="submitBtn">{{translate('Save')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('submitBtn');
    
    // Add debugging for form submission
    form.addEventListener('submit', function(e) {
        console.log('Form submission started...');
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Saving...';
        
        // Validate required fields
        const requiredFields = form.querySelectorAll('[required]');
        let hasErrors = false;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                console.log('Empty required field:', field.name);
                field.classList.add('is-invalid');
                hasErrors = true;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Check territory selection
        const territorySelect = document.getElementById('territory_id');
        if (!territorySelect.value) {
            console.log('No territory selected');
            territorySelect.classList.add('is-invalid');
            hasErrors = true;
        }
        
        if (hasErrors) {
            e.preventDefault();
            submitBtn.disabled = false;
            submitBtn.innerHTML = '{{translate("Save")}}';
            alert('Please fill in all required fields and select a territory.');
            return false;
        }
        
        console.log('Form validation passed, submitting...');
    });
    
    // Debug territory count
    const territories = document.querySelectorAll('#territory_id option[value!=""]');
    console.log('Available territories:', territories.length);
    if (territories.length === 0) {
        console.error('NO TERRITORIES AVAILABLE! This will cause validation error.');
        document.getElementById('territory_id').style.borderColor = 'red';
    }
});
</script>
@endpush

@endsection
