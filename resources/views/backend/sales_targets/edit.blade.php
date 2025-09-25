@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Edit Sales Target') }}</h1>
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
        <form action="{{ route('sales_targets.update', $target->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sales_representative_id">{{ translate('Sales Representative') }} <span class="text-danger">*</span></label>
                        <select class="form-control aiz-selectpicker" name="sales_representative_id" required>
                            <option value="">{{ translate('Select Sales Representative') }}</option>
                            @foreach($salesRepresentatives as $rep)
                                <option value="{{ $rep->id }}" {{ $target->sales_representative_id == $rep->id ? 'selected' : '' }}>
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
                            <option value="monthly" {{ $target->period == 'monthly' ? 'selected' : '' }}>{{ translate('Monthly') }}</option>
                            <option value="quarterly" {{ $target->period == 'quarterly' ? 'selected' : '' }}>{{ translate('Quarterly') }}</option>
                            <option value="yearly" {{ $target->period == 'yearly' ? 'selected' : '' }}>{{ translate('Yearly') }}</option>
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
                        <input type="date" class="form-control" name="start_date" value="{{ $target->start_date }}" required>
                        @error('start_date')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="end_date">{{ translate('End Date') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="end_date" value="{{ $target->end_date }}" required>
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
                        <input type="number" step="0.01" class="form-control" name="target_amount" value="{{ $target->target_amount }}" placeholder="0.00" required>
                        @error('target_amount')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="achieved_amount">{{ translate('Achieved Amount') }}</label>
                        <input type="number" step="0.01" class="form-control" name="achieved_amount" value="{{ $target->achieved_amount }}" placeholder="0.00">
                        <small class="form-text text-muted">{{ translate('This is usually auto-calculated from orders, but can be manually adjusted') }}</small>
                        @error('achieved_amount')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status">{{ translate('Status') }}</label>
                        <select class="form-control" name="status">
                            <option value="active" {{ $target->status == 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                            <option value="inactive" {{ $target->status == 'inactive' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                        </select>
                        @error('status')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ translate('Current Achievement') }}</label>
                        <div class="form-control-static">
                            @php
                                $percentage = $target->target_amount > 0 ? ($target->achieved_amount / $target->target_amount) * 100 : 0;
                            @endphp
                            <div class="progress">
                                <div class="progress-bar 
                                    @if($percentage >= 100) bg-success 
                                    @elseif($percentage >= 75) bg-info 
                                    @elseif($percentage >= 50) bg-warning 
                                    @else bg-danger @endif" 
                                    role="progressbar" style="width: {{ min($percentage, 100) }}%">
                                    {{ number_format($percentage, 1) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">{{ translate('Description') }}</label>
                <textarea class="form-control" name="description" rows="4" placeholder="{{ translate('Enter target description...') }}">{{ $target->description }}</textarea>
                @error('description')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary">{{ translate('Update Sales Target') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        // Date validation
        $('input[name="end_date"]').on('change', function() {
            var startDate = $('input[name="start_date"]').val();
            var endDate = $(this).val();
            
            if (startDate && endDate && endDate <= startDate) {
                alert('{{ translate("End date must be after start date") }}');
                $(this).val('{{ $target->end_date }}');
            }
        });

        // Target amount change to recalculate percentage
        $('input[name="target_amount"], input[name="achieved_amount"]').on('input', function() {
            var targetAmount = parseFloat($('input[name="target_amount"]').val()) || 0;
            var achievedAmount = parseFloat($('input[name="achieved_amount"]').val()) || 0;
            var percentage = targetAmount > 0 ? (achievedAmount / targetAmount) * 100 : 0;
            
            var progressBar = $('.progress-bar');
            progressBar.css('width', Math.min(percentage, 100) + '%');
            progressBar.text(percentage.toFixed(1) + '%');
            
            // Update color class
            progressBar.removeClass('bg-success bg-info bg-warning bg-danger');
            if (percentage >= 100) {
                progressBar.addClass('bg-success');
            } else if (percentage >= 75) {
                progressBar.addClass('bg-info');
            } else if (percentage >= 50) {
                progressBar.addClass('bg-warning');
            } else {
                progressBar.addClass('bg-danger');
            }
        });
    });
</script>
@endsection
