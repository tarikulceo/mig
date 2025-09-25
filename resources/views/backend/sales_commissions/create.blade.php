@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Add New Sales Commission') }}</h1>
        </div>
        <div class="col text-right">
            <a href="{{ route('sales_commissions.index') }}" class="btn btn-link text-info">
                <i class="las la-arrow-left"></i>
                {{ translate('Back to list') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Sales Commission Information') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('sales_commissions.store') }}" method="POST">
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
                        <label for="order_id">{{ translate('Order') }}</label>
                        <select class="form-control aiz-selectpicker" name="order_id" data-live-search="true">
                            <option value="">{{ translate('Select Order (Optional)') }}</option>
                            @foreach($orders as $order)
                                <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                                    #{{ $order->code }} - {{ format_price($order->grand_total) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">{{ translate('Leave empty for manual commission entry') }}</small>
                        @error('order_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="order_amount">{{ translate('Order Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="order_amount" value="{{ old('order_amount') }}" placeholder="0.00" required>
                        <small class="form-text text-muted">{{ translate('Auto-filled when order is selected') }}</small>
                        @error('order_amount')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="commission_rate">{{ translate('Commission Rate (%)') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" name="commission_rate" value="{{ old('commission_rate') }}" placeholder="0.00" required>
                        @error('commission_rate')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="commission_amount">{{ translate('Commission Amount') }}</label>
                        <input type="number" step="0.01" class="form-control" name="commission_amount" value="{{ old('commission_amount') }}" placeholder="0.00" readonly>
                        <small class="form-text text-muted">{{ translate('Auto-calculated based on order amount and rate') }}</small>
                        @error('commission_amount')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status">{{ translate('Status') }}</label>
                        <select class="form-control" name="status">
                            <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                            <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>{{ translate('Paid') }}</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>{{ translate('Cancelled') }}</option>
                        </select>
                        @error('status')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="notes">{{ translate('Notes') }}</label>
                <textarea class="form-control" name="notes" rows="3" placeholder="{{ translate('Additional notes about this commission...') }}">{{ old('notes') }}</textarea>
                @error('notes')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary">{{ translate('Save Commission') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        // Order selection handler
        $('select[name="order_id"]').on('change', function() {
            var orderId = $(this).val();
            if (orderId) {
                // Get order details via AJAX (if needed)
                var selectedOption = $(this).find('option:selected');
                var orderText = selectedOption.text();
                var amount = orderText.match(/[\d,]+\.?\d*/);
                if (amount) {
                    var cleanAmount = amount[0].replace(/,/g, '');
                    $('input[name="order_amount"]').val(cleanAmount);
                    calculateCommission();
                }
            } else {
                $('input[name="order_amount"]').val('');
                $('input[name="commission_amount"]').val('');
            }
        });

        // Commission calculation
        function calculateCommission() {
            var orderAmount = parseFloat($('input[name="order_amount"]').val()) || 0;
            var commissionRate = parseFloat($('input[name="commission_rate"]').val()) || 0;
            var commissionAmount = (orderAmount * commissionRate) / 100;
            $('input[name="commission_amount"]').val(commissionAmount.toFixed(2));
        }

        $('input[name="order_amount"], input[name="commission_rate"]').on('input', calculateCommission);

        // Sales rep selection handler to get default commission rate
        $('select[name="sales_representative_id"]').on('change', function() {
            var repId = $(this).val();
            if (repId && !$('input[name="commission_rate"]').val()) {
                // Set default commission rate if available
                // This would typically come from the sales representative model
                $('input[name="commission_rate"]').val('5.00'); // Default 5%
                calculateCommission();
            }
        });
    });
</script>
@endsection
