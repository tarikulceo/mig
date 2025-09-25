@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Add New Sales Target') }}</h1>
        </div>
        <div class="col text-right">
            <a href="{{ route('sales_targets.index') }}" class="btn btn-link text-info">
                <i class="las la-arrow-left"></i>
                {{ translate('Back to list') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Sales Target Information') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('sales_targets.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sales_representative_id">{{ translate('Sales Representative') }} <span class="text-danger">*</span></label>
                        <select class="form-control aiz-selectpicker" name="sales_representative_id" required>
                            <option value="">{{ translate('Select Sales Representative') }}</option>
                            @foreach($salesRepresentatives as $rep)
                                <option value="{{ $rep->id }}" {{ old('sales_representative_id') == $rep->id ? 'selected' : '' }}>
                                    {{ $rep->user->name }} ({{ $rep->employee_id }})
                                </option>
                            @endforeach
                        </select>
                        @error('sales_representative_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="period">{{ translate('Period') }} <span class="text-danger">*</span></label>
                        <select class="form-control" name="period" required>
                            <option value="">{{ translate('Select Period') }}</option>
                            <option value="monthly" {{ old('period') == 'monthly' ? 'selected' : '' }}>{{ translate('Monthly') }}</option>
                            <option value="quarterly" {{ old('period') == 'quarterly' ? 'selected' : '' }}>{{ translate('Quarterly') }}</option>
                            <option value="yearly" {{ old('period') == 'yearly' ? 'selected' : '' }}>{{ translate('Yearly') }}</option>
                        </select>
                        @error('period')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="start_date">{{ translate('Start Date') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="start_date" value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="end_date">{{ translate('End Date') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="end_date" value="{{ old('end_date') }}" required>
                        @error('end_date')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="target_amount">{{ translate('Target Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="target_amount" value="{{ old('target_amount') }}" placeholder="0.00" required>
                        @error('target_amount')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status">{{ translate('Status') }}</label>
                        <select class="form-control" name="status">
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                        </select>
                        @error('status')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">{{ translate('Description') }}</label>
                <textarea class="form-control" name="description" rows="4" placeholder="{{ translate('Enter target description...') }}">{{ old('description') }}</textarea>
                @error('description')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary">{{ translate('Save Sales Target') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        // Period change handler to auto-set dates
        $('select[name="period"]').on('change', function() {
            var period = $(this).val();
            var today = new Date();
            var startDate, endDate;

            if (period === 'monthly') {
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            } else if (period === 'quarterly') {
                var quarter = Math.floor(today.getMonth() / 3);
                startDate = new Date(today.getFullYear(), quarter * 3, 1);
                endDate = new Date(today.getFullYear(), quarter * 3 + 3, 0);
            } else if (period === 'yearly') {
                startDate = new Date(today.getFullYear(), 0, 1);
                endDate = new Date(today.getFullYear(), 11, 31);
            }

            if (startDate && endDate) {
                $('input[name="start_date"]').val(startDate.toISOString().split('T')[0]);
                $('input[name="end_date"]').val(endDate.toISOString().split('T')[0]);
            }
        });

        // Date validation
        $('input[name="end_date"]').on('change', function() {
            var startDate = $('input[name="start_date"]').val();
            var endDate = $(this).val();
            
            if (startDate && endDate && endDate <= startDate) {
                alert('{{ translate("End date must be after start date") }}');
                $(this).val('');
            }
        });
    });
</script>
@endsection
