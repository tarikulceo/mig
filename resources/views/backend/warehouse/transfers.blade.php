@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Stock Transfers')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('warehouse.transfer.form') }}" class="btn btn-primary">
                <span>{{translate('New Transfer')}}</span>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col">
            <h5 class="mb-md-0 h6">{{ translate('All Stock Transfers') }}</h5>
        </div>
        <div class="col-md-3">
            <form class="" id="sort_orders" action="" method="GET">
                <div class="input-group input-group-sm">
                    <select class="form-control aiz-selectpicker" name="status" onchange="sort_orders()">
                        <option value="">{{translate('All Status')}}</option>
                        <option value="pending" @if($status == 'pending') selected @endif>{{translate('Pending')}}</option>
                        <option value="approved" @if($status == 'approved') selected @endif>{{translate('Approved')}}</option>
                        <option value="in_transit" @if($status == 'in_transit') selected @endif>{{translate('In Transit')}}</option>
                        <option value="completed" @if($status == 'completed') selected @endif>{{translate('Completed')}}</option>
                        <option value="cancelled" @if($status == 'cancelled') selected @endif>{{translate('Cancelled')}}</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>{{translate('Transfer ID')}}</th>
                    <th>{{translate('Product')}}</th>
                    <th>{{translate('From Warehouse')}}</th>
                    <th>{{translate('To Warehouse')}}</th>
                    <th>{{translate('Quantity')}}</th>
                    <th>{{translate('Status')}}</th>
                    <th>{{translate('Initiated By')}}</th>
                    <th>{{translate('Date')}}</th>
                    <th>{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfers as $transfer)
                    <tr>
                        <td>
                            <code>{{ $transfer->transfer_code }}</code>
                        </td>
                        <td>
                            <div class="form-group row">
                                <div class="col-auto">
                                    <img src="{{ uploaded_asset($transfer->product->thumbnail_img)}}" alt="Image" class="size-40px img-fit">
                                </div>
                                <div class="col">
                                    <span class="text-muted text-truncate-2">{{ $transfer->product->name }}</span>
                                    @if($transfer->productStock)
                                        <br><small class="text-secondary">{{ $transfer->productStock->variant }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $transfer->fromWarehouse->name }}</td>
                        <td>{{ $transfer->toWarehouse->name }}</td>
                        <td>{{ $transfer->quantity }}</td>
                        <td>
                            @if($transfer->status == 'pending')
                                <span class="badge badge-soft-info">{{translate('Pending')}}</span>
                            @elseif($transfer->status == 'approved')
                                <span class="badge badge-soft-warning">{{translate('Approved')}}</span>
                            @elseif($transfer->status == 'in_transit')
                                <span class="badge badge-soft-primary">{{translate('In Transit')}}</span>
                            @elseif($transfer->status == 'completed')
                                <span class="badge badge-soft-success">{{translate('Completed')}}</span>
                            @elseif($transfer->status == 'cancelled')
                                <span class="badge badge-soft-danger">{{translate('Cancelled')}}</span>
                            @endif
                        </td>
                        <td>{{ $transfer->initiatedBy->name }}</td>
                        <td>{{ $transfer->created_at->format('d M Y, h:i A') }}</td>
                        <td class="text-right">
                            <div class="dropdown">
                                <button class="btn btn-soft-secondary btn-icon btn-circle btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="las la-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    @if($transfer->status == 'pending')
                                        <a class="dropdown-item" href="{{ route('warehouse.transfer.approve', $transfer->id) }}" onclick="return confirm('{{translate('Are you sure to approve this transfer?')}}')">
                                            {{translate('Approve')}}
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="cancel_transfer('{{ $transfer->id }}')">
                                            {{translate('Cancel')}}
                                        </a>
                                    @elseif($transfer->status == 'approved')
                                        <a class="dropdown-item" href="{{ route('warehouse.transfer.complete', $transfer->id) }}" onclick="return confirm('{{translate('Are you sure to complete this transfer?')}}')">
                                            {{translate('Complete')}}
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="cancel_transfer('{{ $transfer->id }}')">
                                            {{translate('Cancel')}}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $transfers->appends(request()->input())->links() }}
        </div>
    </div>
</div>

<!-- Cancel Transfer Modal -->
<div class="modal fade" id="cancel_transfer_modal" tabindex="-1" role="dialog" aria-labelledby="cancelTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelTransferModalLabel">{{translate('Cancel Transfer')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST" id="cancel_transfer_form">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{translate('Cancellation Reason')}}</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="{{translate('Please specify the reason for cancellation')}}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{translate('Close')}}</button>
                    <button type="submit" class="btn btn-danger">{{translate('Cancel Transfer')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    function sort_orders() {
        $('#sort_orders').submit();
    }

    function cancel_transfer(transfer_id) {
        $('#cancel_transfer_form').attr('action', '{{ route("warehouse.transfer.cancel", ":id") }}'.replace(':id', transfer_id));
        $('#cancel_transfer_modal').modal('show');
    }
</script>
@endsection
