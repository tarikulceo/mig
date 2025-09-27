@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Product-Based Commissions') }} - {{ $salesRep->user->name }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sales_representatives.show', $salesRep->id) }}" class="btn btn-circle btn-info">
                <span>{{ translate('Back to Sales Rep') }}</span>
            </a>
        </div>
    </div>
</div>

<!-- Commission Summary Cards -->
<div class="row gutters-10 mb-3">
    <div class="col-lg-3 col-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-primary bg-primary">
                <i class="las la-coins"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{ translate('Total Commissions') }}</h4>
                </div>
                <div class="card-body">
                    {{ $summary['total_commissions'] }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-success bg-success">
                <i class="las la-dollar-sign"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{ translate('Total Amount') }}</h4>
                </div>
                <div class="card-body">
                    {{ format_price($summary['total_amount']) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-warning bg-warning">
                <i class="las la-clock"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{ translate('Pending Amount') }}</h4>
                </div>
                <div class="card-body">
                    {{ format_price($summary['pending_amount']) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-info bg-info">
                <i class="las la-check-circle"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{ translate('Approved Amount') }}</h4>
                </div>
                <div class="card-body">
                    {{ format_price($summary['approved_amount']) }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col">
            <h5 class="mb-md-0 h6">{{ translate('Commission History') }}</h5>
        </div>
        <div class="col-md-3">
            <div class="form-group mb-0">
                <button type="button" class="btn btn-primary btn-sm" id="bulk-approve-btn" disabled>
                    <i class="las la-check"></i> {{ translate('Approve Selected') }}
                </button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="checkAll">
                            <label class="form-check-label" for="checkAll"></label>
                        </div>
                    </th>
                    <th>{{ translate('Order') }}</th>
                    <th>{{ translate('Product') }}</th>
                    <th>{{ translate('Commission %') }}</th>
                    <th>{{ translate('Base Amount') }}</th>
                    <th>{{ translate('Commission Amount') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Date') }}</th>
                    <th class="text-right">{{ translate('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($commissions as $commission)
                    <tr>
                        <td>
                            @if($commission->status == 'pending')
                                <div class="form-check">
                                    <input class="form-check-input commission-checkbox" type="checkbox" value="{{ $commission->id }}">
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($commission->order)
                                <a href="{{ route('all_orders.show', $commission->order->id) }}" class="text-reset">
                                    {{ $commission->order->code }}
                                </a>
                            @else
                                <span class="text-muted">{{ translate('Order Deleted') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($commission->product)
                                <div class="d-flex align-items-center">
                                    <img src="{{ uploaded_asset($commission->product->thumbnail_img) }}" 
                                         alt="{{ $commission->product->getTranslation('name') }}" 
                                         class="size-40px rounded mr-2">
                                    <span>{{ $commission->product->getTranslation('name') }}</span>
                                </div>
                            @else
                                <span class="text-muted">{{ translate('Product Deleted') }}</span>
                            @endif
                        </td>
                        <td>{{ $commission->commission_percentage }}%</td>
                        <td>{{ format_price($commission->base_amount) }}</td>
                        <td>{{ format_price($commission->commission_amount) }}</td>
                        <td>
                            @switch($commission->status)
                                @case('pending')
                                    <span class="badge badge-inline badge-warning">{{ translate('Pending') }}</span>
                                    @break
                                @case('approved')
                                    <span class="badge badge-inline badge-success">{{ translate('Approved') }}</span>
                                    @break
                                @case('paid')
                                    <span class="badge badge-inline badge-primary">{{ translate('Paid') }}</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge badge-inline badge-secondary">{{ translate('Cancelled') }}</span>
                                    @break
                                @default
                                    <span class="badge badge-inline badge-info">{{ ucfirst($commission->status) }}</span>
                            @endswitch
                        </td>
                        <td>{{ $commission->commission_date->format('d M, Y H:i') }}</td>
                        <td class="text-right">
                            @if($commission->status == 'pending')
                                <form action="{{ route('sales_representatives.approve_commission') }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    <input type="hidden" name="commission_id" value="{{ $commission->id }}">
                                    <button type="submit" class="btn btn-soft-success btn-icon btn-circle btn-sm" 
                                            onclick="return confirm('{{ translate('Are you sure you want to approve this commission?') }}')"
                                            data-toggle="tooltip" title="{{ translate('Approve Commission') }}">
                                        <i class="las la-check"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $commissions->links() }}
        </div>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function(){
        // Handle "Check All" functionality
        $('#checkAll').change(function(){
            $('.commission-checkbox').prop('checked', $(this).prop('checked'));
            updateBulkApproveButton();
        });

        // Handle individual checkbox changes
        $('.commission-checkbox').change(function(){
            updateBulkApproveButton();
            
            // Update "Check All" state
            if ($('.commission-checkbox:checked').length === $('.commission-checkbox').length) {
                $('#checkAll').prop('checked', true);
            } else {
                $('#checkAll').prop('checked', false);
            }
        });

        // Bulk approve button click
        $('#bulk-approve-btn').click(function(){
            var selectedIds = [];
            $('.commission-checkbox:checked').each(function(){
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0) {
                if (confirm('{{ translate("Are you sure you want to approve selected commissions?") }}')) {
                    var form = $('<form method="POST" action="{{ route('sales_representatives.bulk_approve_commissions') }}">');
                    form.append('@csrf');
                    
                    selectedIds.forEach(function(id) {
                        form.append('<input type="hidden" name="commission_ids[]" value="' + id + '">');
                    });
                    
                    $('body').append(form);
                    form.submit();
                }
            }
        });

        function updateBulkApproveButton() {
            var checkedCount = $('.commission-checkbox:checked').length;
            $('#bulk-approve-btn').prop('disabled', checkedCount === 0);
        }
    });
</script>
@endsection
