@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Log New Sales Activity') }}</h1>
        </div>
        <div class="col text-right">
            <a href="{{ route('sales_activities.index') }}" class="btn btn-link text-info">
                <i class="las la-arrow-left"></i>
                {{ translate('Back to list') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Sales Activity Information') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('sales_activities.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sales_representative_id">{{ translate('Sales Representative') }} <span class="text-danger">*</span></label>
                        <select class="form-control aiz-selectpicker" name="sales_representative_id" required>
                            <option value="">{{ translate('Select Sales Representative') }}</option>
                            @foreach($salesReps as $rep)
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
                        <label for="customer_id">{{ translate('Customer') }}</label>
                        <select class="form-control aiz-selectpicker" name="customer_id" data-live-search="true">
                            <option value="">{{ translate('Select Customer (Optional)') }}</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="activity_type">{{ translate('Activity Type') }} <span class="text-danger">*</span></label>
                        <select class="form-control" name="activity_type" required>
                            <option value="">{{ translate('Select Activity Type') }}</option>
                            <option value="call" {{ old('activity_type') == 'call' ? 'selected' : '' }}>{{ translate('Phone Call') }}</option>
                            <option value="email" {{ old('activity_type') == 'email' ? 'selected' : '' }}>{{ translate('Email') }}</option>
                            <option value="meeting" {{ old('activity_type') == 'meeting' ? 'selected' : '' }}>{{ translate('Meeting') }}</option>
                            <option value="demo" {{ old('activity_type') == 'demo' ? 'selected' : '' }}>{{ translate('Product Demo') }}</option>
                            <option value="follow_up" {{ old('activity_type') == 'follow_up' ? 'selected' : '' }}>{{ translate('Follow Up') }}</option>
                            <option value="presentation" {{ old('activity_type') == 'presentation' ? 'selected' : '' }}>{{ translate('Presentation') }}</option>
                            <option value="other" {{ old('activity_type') == 'other' ? 'selected' : '' }}>{{ translate('Other') }}</option>
                        </select>
                        @error('activity_type')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status">{{ translate('Status') }} <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" required>
                            <option value="scheduled" {{ old('status', 'scheduled') == 'scheduled' ? 'selected' : '' }}>{{ translate('Scheduled') }}</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>{{ translate('In Progress') }}</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>{{ translate('Completed') }}</option>
                        </select>
                        @error('status')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="activity_date">{{ translate('Activity Date & Time') }} <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" name="activity_date" value="{{ old('activity_date', date('Y-m-d\TH:i')) }}" required>
                        @error('activity_date')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="follow_up_date">{{ translate('Follow Up Date') }}</label>
                        <input type="datetime-local" class="form-control" name="follow_up_date" value="{{ old('follow_up_date') }}">
                        @error('follow_up_date')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">{{ translate('Activity Description') }} <span class="text-danger">*</span></label>
                <textarea class="form-control" name="description" rows="4" placeholder="{{ translate('Describe the activity details...') }}" required>{{ old('description') }}</textarea>
                @error('description')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="outcome">{{ translate('Outcome / Result') }}</label>
                <textarea class="form-control" name="outcome" rows="3" placeholder="{{ translate('What was the result of this activity?') }}">{{ old('outcome') }}</textarea>
                @error('outcome')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="notes">{{ translate('Additional Notes') }}</label>
                <textarea class="form-control" name="notes" rows="3" placeholder="{{ translate('Any additional notes or comments...') }}">{{ old('notes') }}</textarea>
                @error('notes')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary">{{ translate('Log Activity') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        // Status change handler
        $('select[name="status"]').on('change', function() {
            var status = $(this).val();
            var followUpGroup = $('input[name="follow_up_date"]').closest('.form-group');
            
            if (status === 'completed') {
                followUpGroup.hide();
                $('input[name="follow_up_date"]').val('');
            } else {
                followUpGroup.show();
            }
        });

        // Activity type change
        $('select[name="activity_type"]').on('change', function() {
            var type = $(this).val();
            var outcomeField = $('textarea[name="outcome"]');
            
            if (type === 'call') {
                outcomeField.attr('placeholder', '{{ translate("Call outcome: Connected, No answer, Busy, etc.") }}');
            } else if (type === 'email') {
                outcomeField.attr('placeholder', '{{ translate("Email outcome: Sent, Replied, Bounced, etc.") }}');
            } else if (type === 'meeting') {
                outcomeField.attr('placeholder', '{{ translate("Meeting outcome: Attended, Rescheduled, No-show, etc.") }}');
            } else {
                outcomeField.attr('placeholder', '{{ translate("What was the result of this activity?") }}');
            }
        });

        // Date validation
        $('input[name="follow_up_date"]').on('change', function() {
            var activityDate = $('input[name="activity_date"]').val();
            var followUpDate = $(this).val();
            
            if (activityDate && followUpDate && followUpDate <= activityDate) {
                alert('{{ translate("Follow up date should be after activity date") }}');
                $(this).val('');
            }
        });
    });
</script>
@endsection
