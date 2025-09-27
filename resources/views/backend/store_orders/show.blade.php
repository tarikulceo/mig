@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3">{{translate('Store Order Details')}} - {{ $order->order_code }}</h1>
            <div class="mt-1">
                <span class="badge badge-inline badge-{{ $order->status_badge }} badge-lg mr-2">
                    <i class="fas fa-box mr-1"></i>
                    {{translate('Order')}}: {{ $order->order_status_name }}
                </span>
                <span class="badge badge-inline badge-{{ $order->payment_badge }} badge-lg">
                    <i class="fas fa-credit-card mr-1"></i>
                    {{translate('Payment')}}: {{ $order->payment_status_name }}
                </span>
            </div>
        </div>
        <div class="col-md-4 text-md-right">
            <a href="{{ route('store_orders.index') }}" class="btn btn-light">
                <i class="las la-arrow-left"></i>
                <span>{{translate('Back to Orders')}}</span>
            </a>
            <a href="{{ route('store_orders.invoice', $order->id) }}" class="btn btn-primary" target="_blank">
                <i class="las la-print"></i>
                <span>{{translate('Print Invoice')}}</span>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Order Information -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Order Information')}}</h5>
                <div class="float-right">
                    @if($order->order_status !== 'delivered' && $order->order_status !== 'cancelled')
                        <button class="btn btn-sm btn-primary" onclick="updateOrderStatus({{ $order->id }})">
                            <i class="las la-edit"></i> {{translate('Update Status')}}
                        </button>
                    @endif
                    @if($order->payment_status !== 'paid')
                        <button class="btn btn-sm btn-success ml-1" onclick="updatePaymentStatus({{ $order->id }})">
                            <i class="las la-money-bill"></i> {{translate('Update Payment')}}
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="w-50 fw-600">{{translate('Order Code')}}:</td>
                                <td>{{ $order->order_code }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Order Status')}}:</td>
                                <td>
                                    <span class="badge badge-{{ $order->status_badge }}">
                                        {{ $order->order_status_name }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Payment Status')}}:</td>
                                <td>
                                    <span class="badge badge-{{ $order->payment_badge }}">
                                        {{ $order->payment_status_name }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Order Date')}}:</td>
                                <td>{{ $order->order_date ? $order->order_date->format('M d, Y') : 'Not specified' }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Expected Delivery')}}:</td>
                                <td>{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('M d, Y') : 'Not specified' }}</td>
                            </tr>
                            @if($order->storeVisit)
                            <tr>
                                <td class="w-50 fw-600">{{translate('Related Visit')}}:</td>
                                <td>
                                    <a href="{{ route('store_visits.show', $order->storeVisit->id) }}" class="text-primary">
                                        {{ $order->storeVisit->visit_date ? $order->storeVisit->visit_date->format('M d, Y') : 'No date' }} - {{ $order->storeVisit->purpose ?? 'Visit' }}
                                    </a>
                                </td>
                            </tr>
                            @endif
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="w-50 fw-600">{{translate('Payment Method')}}:</td>
                                <td>{{ $order->payment_method ? ucfirst(str_replace('_', ' ', $order->payment_method)) : 'Not specified' }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Tax Amount')}}:</td>
                                <td>{{ single_price($order->tax_amount) }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Shipping Amount')}}:</td>
                                <td>{{ single_price($order->shipping_amount) }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Discount Amount')}}:</td>
                                <td>{{ single_price($order->discount_amount) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                @if($order->notes)
                <div class="row">
                    <div class="col-12">
                        <hr>
                        <h6>{{translate('Order Notes')}}</h6>
                        <p class="text-muted">{{ $order->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Store Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Store Information')}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="w-50 fw-600">{{translate('Store Name')}}:</td>
                                <td>{{ $order->retailStore->name }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Store Code')}}:</td>
                                <td>{{ $order->retailStore->store_code }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Contact Person')}}:</td>
                                <td>{{ $order->retailStore->contact_person }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="w-50 fw-600">{{translate('Phone')}}:</td>
                                <td>{{ $order->retailStore->phone }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Email')}}:</td>
                                <td>{{ $order->retailStore->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="w-50 fw-600">{{translate('Address')}}:</td>
                                <td>{{ $order->retailStore->address }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Order Items')}}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered aiz-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{translate('Product')}}</th>
                                <th>{{translate('SKU')}}</th>
                                <th>{{translate('Price')}}</th>
                                <th>{{translate('Quantity')}}</th>
                                <th>{{translate('Discount')}}</th>
                                <th>{{translate('Total')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->orderItems ?? [] as $key => $item)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($item->product && $item->product->thumbnail_img)
                                            <img src="{{ uploaded_asset($item->product->thumbnail_img) }}" 
                                                 class="size-40px img-fit mr-2" alt="">
                                        @endif
                                        <div>
                                            <div class="fs-14 fw-600">{{ $item->product_name }}</div>
                                            @if($item->product && $item->product->brand)
                                                <div class="fs-12 opacity-60">{{ $item->product->brand->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $item->product ? $item->product->sku : 'N/A' }}</td>
                                <td>{{ single_price($item->unit_price) }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ single_price($item->discount_amount) }}</td>
                                <td>{{ single_price($item->total_amount) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="las la-box fs-24 mb-2 d-block"></i>
                                        {{translate('No order items found')}}
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Timeline - Temporarily disabled until StoreOrderStatusHistory model is created -->
        {{-- Status history functionality can be re-enabled when StoreOrderStatusHistory model is implemented --}}
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Order Summary -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Order Summary')}}</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td>{{translate('Subtotal')}}:</td>
                        <td class="text-right">{{ single_price($order->subtotal) }}</td>
                    </tr>
                    <tr>
                        <td>{{translate('Tax')}}:</td>
                        <td class="text-right">{{ single_price($order->tax_amount) }}</td>
                    </tr>
                    <tr>
                        <td>{{translate('Shipping')}}:</td>
                        <td class="text-right">{{ single_price($order->shipping_amount) }}</td>
                    </tr>
                    <tr>
                        <td>{{translate('Discount')}}:</td>
                        <td class="text-right">-{{ single_price($order->discount_amount) }}</td>
                    </tr>
                    <tr class="border-top">
                        <td class="fw-600">{{translate('Total')}}:</td>
                        <td class="text-right fw-600">{{ single_price($order->grand_total) }}</td>
                    </tr>
                </table>
                
                @if($order->payment_status === 'partial')
                <div class="border-top pt-2 mt-2">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td>{{translate('Paid Amount')}}:</td>
                            <td class="text-right text-success fw-600">{{ single_price($order->paid_amount) }}</td>
                        </tr>
                        <tr>
                            <td>{{translate('Due Amount')}}:</td>
                            <td class="text-right text-danger fw-600">{{ single_price($order->grand_total - $order->paid_amount) }}</td>
                        </tr>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <!-- Sales Representative -->
        @if($order->salesRepresentative)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Sales Representative')}}</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    @if($order->salesRepresentative->user->avatar)
                        <img src="{{ uploaded_asset($order->salesRepresentative->user->avatar) }}" 
                             class="size-50px rounded-circle mr-3" alt="">
                    @else
                        <div class="size-50px rounded-circle bg-soft-primary d-flex align-items-center justify-content-center mr-3">
                            <i class="las la-user fs-20"></i>
                        </div>
                    @endif
                    <div>
                        <div class="fs-15 fw-600">{{ $order->salesRepresentative->user->name }}</div>
                        <div class="fs-12 opacity-60">{{ $order->salesRepresentative->employee_id }}</div>
                        <div class="fs-12 opacity-60">{{ $order->salesRepresentative->user->email }}</div>
                        @if($order->salesRepresentative->user->phone)
                            <div class="fs-12 opacity-60">{{ $order->salesRepresentative->user->phone }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Commission Information -->
        @if($order->commission_amount > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Commission Details')}}</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="fs-14">Store Order Commission</div>
                        <div class="fs-12 opacity-60">{{ $order->commission_rate ?? 0 }}% commission</div>
                    </div>
                    <div class="text-right">
                        <div class="fs-14 fw-600">{{ single_price($order->commission_amount ?? 0) }}</div>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-600">{{translate('Total Commission')}}:</div>
                    <div class="fw-600">{{ single_price($order->commission_amount ?? 0) }}</div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="statusUpdateForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Update Order Status') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="order_status">{{ translate('Order Status') }}</label>
                        <select class="form-control" name="order_status" required>
                            <option value="pending" @if($order->order_status == 'pending') selected @endif>{{ translate('Pending') }}</option>
                            <option value="confirmed" @if($order->order_status == 'confirmed') selected @endif>{{ translate('Confirmed') }}</option>
                            <option value="processing" @if($order->order_status == 'processing') selected @endif>{{ translate('Processing') }}</option>
                            <option value="shipped" @if($order->order_status == 'shipped') selected @endif>{{ translate('Shipped') }}</option>
                            <option value="delivered" @if($order->order_status == 'delivered') selected @endif>{{ translate('Delivered') }}</option>
                            <option value="cancelled" @if($order->order_status == 'cancelled') selected @endif>{{ translate('Cancelled') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notes">{{ translate('Notes') }}</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Update Status') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Update Modal -->
<div class="modal fade" id="paymentUpdateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="paymentUpdateForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Update Payment Status') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="payment_status">{{ translate('Payment Status') }}</label>
                        <select class="form-control" name="payment_status" required>
                            <option value="pending" @if($order->payment_status == 'pending') selected @endif>{{ translate('Pending') }}</option>
                            <option value="paid" @if($order->payment_status == 'paid') selected @endif>{{ translate('Paid') }}</option>
                            <option value="partial" @if($order->payment_status == 'partial') selected @endif>{{ translate('Partial') }}</option>
                            <option value="failed" @if($order->payment_status == 'failed') selected @endif>{{ translate('Failed') }}</option>
                            <option value="refunded" @if($order->payment_status == 'refunded') selected @endif>{{ translate('Refunded') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="paid_amount">{{ translate('Paid Amount') }}</label>
                        <input type="number" class="form-control" name="paid_amount" 
                               value="{{ $order->paid_amount }}" min="0" max="{{ $order->grand_total }}" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="payment_notes">{{ translate('Payment Notes') }}</label>
                        <textarea class="form-control" name="payment_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Update Payment') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    function updateOrderStatus(orderId) {
        var actionUrl = '{{ url("admin/store-orders") }}/' + orderId + '/update-status';
        $('#statusUpdateForm').attr('action', actionUrl);
        $('#statusUpdateModal').modal('show');
    }

    function updatePaymentStatus(orderId) {
        var actionUrl = '{{ url("admin/store-orders") }}/' + orderId + '/update-payment';
        $('#paymentUpdateForm').attr('action', actionUrl);
        $('#paymentUpdateModal').modal('show');
    }
</script>
@endsection