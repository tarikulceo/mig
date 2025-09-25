@extends('backend.layouts.app')

@section('content')

<!-- Title section -->
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('My Commissions')}}</h1>
        </div>
    </div>
</div>

<!-- Commission Summary Cards -->
<div class="row mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-primary bg-primary">
                <i class="las la-coins"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('Total Commissions')}}</h4>
                </div>
                <div class="card-body">
                    {{ single_price($totalCommissions) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-success bg-success">
                <i class="las la-check-circle"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('Paid Commissions')}}</h4>
                </div>
                <div class="card-body">
                    {{ single_price($paidCommissions) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="card card-statistic-2">
            <div class="card-icon shadow-warning bg-warning">
                <i class="las la-clock"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>{{translate('Pending Commissions')}}</h4>
                </div>
                <div class="card-body">
                    {{ single_price($pendingCommissions) }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Commissions Filter and Table -->
<div class="card">
    <form class="" action="{{ route('personal.commissions') }}" id="sort_commissions" method="GET">
        <div class="card-header row gutters-5">
            <div class="col text-center text-md-left">
                <h5 class="mb-0 h6">{{ translate('Commission History') }}</h5>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <input type="text" class="form-control form-control-sm" id="search" name="search" @isset($search) value="{{ $search }}" @endisset placeholder="{{ translate('Search commissions...') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-0">
                    <input type="text" class="form-control form-control-sm aiz-date-range" 
                           name="date_range" 
                           value="{{ request('date_range') }}" 
                           placeholder="{{ translate('Select Date Range') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control form-control-sm aiz-selectpicker" name="payment_status" onchange="sort_commissions()">
                        <option value="">{{ translate('All Payment Status') }}</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>{{ translate('Paid') }}</option>
                        <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control form-control-sm aiz-selectpicker" name="status" onchange="sort_commissions()">
                        <option value="">{{ translate('All Status') }}</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ translate('Approved') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ translate('Cancelled') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </form>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th data-breakpoints="lg">#</th>
                    <th>{{translate('Commission Info')}}</th>
                    <th data-breakpoints="md">{{translate('Order Code')}}</th>
                    <th>{{translate('Commission Amount')}}</th>
                    <th data-breakpoints="md">{{translate('Sale Amount')}}</th>
                    <th data-breakpoints="lg">{{translate('Payment Status')}}</th>
                    <th>{{translate('Status')}}</th>
                    <th data-breakpoints="lg">{{translate('Commission Date')}}</th>
                    <th width="10%">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($commissions as $key => $commission)
                <tr>
                    <td>{{ ($key+1) + ($commissions->currentPage() - 1) * $commissions->perPage() }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="size-40px rounded mr-2 bg-soft-primary d-flex align-items-center justify-content-center">
                                <i class="las la-percentage text-primary"></i>
                            </div>
                            <div>
                                <div class="fs-14 fw-600">{{ $commission->commission_type_name }}</div>
                                <div class="fs-12 opacity-60">{{ $commission->commission_rate ? number_format($commission->commission_rate, 2) . '%' : 'N/A' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($commission->order)
                            <div class="fs-13">{{ $commission->order->code ?? 'N/A' }}</div>
                        @else
                            <span class="text-muted fs-13">{{ translate('N/A') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="fs-14 fw-600 text-success">{{ single_price($commission->commission_amount) }}</div>
                    </td>
                    <td>
                        <div class="fs-13">{{ single_price($commission->sale_amount) }}</div>
                    </td>
                    <td>
                        @if($commission->payment_status == 'paid')
                            <span class="badge badge-inline badge-success">{{ translate('Paid') }}</span>
                        @elseif($commission->payment_status == 'pending')
                            <span class="badge badge-inline badge-warning">{{ translate('Pending') }}</span>
                        @else
                            <span class="badge badge-inline badge-secondary">{{ $commission->payment_status_name }}</span>
                        @endif
                    </td>
                    <td>
                        @if($commission->status == 'approved')
                            <span class="badge badge-inline badge-success">{{ translate('Approved') }}</span>
                        @elseif($commission->status == 'pending')
                            <span class="badge badge-inline badge-warning">{{ translate('Pending') }}</span>
                        @elseif($commission->status == 'cancelled')
                            <span class="badge badge-inline badge-danger">{{ translate('Cancelled') }}</span>
                        @else
                            <span class="badge badge-inline badge-secondary">{{ $commission->status_name }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="fs-13">{{ $commission->commission_date ? $commission->commission_date->format('M d, Y') : $commission->created_at->format('M d, Y') }}</div>
                        <div class="fs-12 opacity-60">{{ $commission->commission_date ? $commission->commission_date->format('h:i A') : $commission->created_at->format('h:i A') }}</div>
                    </td>
                    <td class="text-right">
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-icon btn-circle btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="las la-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                @if($commission->order)
                                    <a class="dropdown-item" href="#" onclick="show_commission_details({{ $commission->id }})">
                                        <i class="las la-eye"></i> {{translate('View Details')}}
                                    </a>
                                @endif
                                @if($commission->notes)
                                    <a class="dropdown-item" href="#" onclick="show_commission_notes({{ $commission->id }})">
                                        <i class="las la-sticky-note"></i> {{translate('View Notes')}}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">
                        <div class="py-4">
                            <i class="las la-coins la-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ translate('No commissions found') }}</p>
                            <p class="small text-muted">{{ translate('Commission data will appear here once you make sales') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $commissions->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
<!-- Commission Details Modal -->
<div class="modal fade" id="commission_details_modal" tabindex="-1" role="dialog" aria-labelledby="commission_details_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commission_details_modal_label">{{ translate('Commission Details') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="commission_details_content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Commission Notes Modal -->
<div class="modal fade" id="commission_notes_modal" tabindex="-1" role="dialog" aria-labelledby="commission_notes_modal_label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commission_notes_modal_label">{{ translate('Commission Notes') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="commission_notes_content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    function sort_commissions(el) {
        $('#sort_commissions').submit();
    }
    
    $('#search').on('keyup', function(){
        if($(this).val().length > 0 || $(this).val().length == 0) {
            $('#sort_commissions').submit();
        }
    });

    function show_commission_details(commission_id) {
        $('#commission_details_modal').modal('show');
        $('#commission_details_content').html('<div class="text-center py-4"><i class="las la-spinner la-spin la-2x"></i></div>');
        
        $.get('{{ route("personal.commissions") }}/' + commission_id + '/details', function(data) {
            $('#commission_details_content').html(data);
        }).fail(function() {
            $('#commission_details_content').html('<div class="text-center py-4 text-danger">{{ translate("Error loading commission details") }}</div>');
        });
    }
    
    function show_commission_notes(commission_id) {
        $('#commission_notes_modal').modal('show');
        $('#commission_notes_content').html('<div class="text-center py-4"><i class="las la-spinner la-spin la-2x"></i></div>');
        
        $.get('{{ route("personal.commissions") }}/' + commission_id + '/notes', function(data) {
            $('#commission_notes_content').html(data);
        }).fail(function() {
            $('#commission_notes_content').html('<div class="text-center py-4 text-danger">{{ translate("Error loading commission notes") }}</div>');
        });
    }
</script>
@endsection