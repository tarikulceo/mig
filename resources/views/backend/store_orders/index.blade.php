@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Store Orders')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('store_orders.create') }}" class="btn btn-primary">
                <i class="las la-plus"></i>
                <span>{{translate('Create Store Order')}}</span>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <form class="" action="" id="sort_orders" method="GET">
        <div class="card-header row gutters-5">
            <div class="col text-center text-md-left">
                <h5 class="mb-md-0 h6">{{ translate('All Store Orders') }}</h5>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <input type="text" class="form-control form-control-sm" id="search" name="search" @isset(request()->search) value="{{ request()->search }}" @endisset placeholder="{{ translate('Type order code & Enter') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control form-control-sm aiz-selectpicker" name="status">
                        <option value="">{{ translate('All Status') }}</option>
                        <option value="pending" @if(request()->status == 'pending') selected @endif>{{ translate('Pending') }}</option>
                        <option value="confirmed" @if(request()->status == 'confirmed') selected @endif>{{ translate('Confirmed') }}</option>
                        <option value="processing" @if(request()->status == 'processing') selected @endif>{{ translate('Processing') }}</option>
                        <option value="shipped" @if(request()->status == 'shipped') selected @endif>{{ translate('Shipped') }}</option>
                        <option value="delivered" @if(request()->status == 'delivered') selected @endif>{{ translate('Delivered') }}</option>
                        <option value="cancelled" @if(request()->status == 'cancelled') selected @endif>{{ translate('Cancelled') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control form-control-sm aiz-selectpicker" name="payment_status">
                        <option value="">{{ translate('All Payments') }}</option>
                        <option value="pending" @if(request()->payment_status == 'pending') selected @endif>{{ translate('Pending') }}</option>
                        <option value="paid" @if(request()->payment_status == 'paid') selected @endif>{{ translate('Paid') }}</option>
                        <option value="partial" @if(request()->payment_status == 'partial') selected @endif>{{ translate('Partial') }}</option>
                        <option value="failed" @if(request()->payment_status == 'failed') selected @endif>{{ translate('Failed') }}</option>
                    </select>
                </div>
            </div>
            @if(auth()->user()->user_type !== 'sales_rep')
            <div class="col-md-2">
                <div class="form-group mb-0">
                    <select class="form-control form-control-sm aiz-selectpicker" name="sales_rep_id">
                        <option value="">{{ translate('All Sales Reps') }}</option>
                        @foreach($salesReps as $rep)
                            <option value="{{ $rep->id }}" @if(request()->sales_rep_id == $rep->id) selected @endif>
                                {{ $rep->user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
            <div class="col-md-1">
                <button class="btn btn-primary btn-sm" type="submit">
                    {{ translate('Filter') }}
                </button>
            </div>
        </div>
    </form>
    
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{translate('Order Code')}}</th>
                    <th>{{translate('Store')}}</th>
                    @if(auth()->user()->user_type !== 'sales_rep')
                        <th>{{translate('Sales Rep')}}</th>
                    @endif
                    <th>{{translate('Amount')}}</th>
                    <th>{{translate('Order Status')}}</th>
                    <th>{{translate('Payment Status')}}</th>
                    <th>{{translate('Date')}}</th>
                    <th width="10%">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $key => $order)
                    <tr>
                        <td>{{ ($key+1) + ($orders->currentPage() - 1)*$orders->perPage() }}</td>
                        <td>
                            <a href="{{ route('store_orders.show', $order->id) }}" class="text-primary">
                                {{ $order->order_code }}
                            </a>
                            @if($order->storeVisit)
                                <br><small class="text-muted">{{ translate('From Visit') }}</small>
                            @endif
                        </td>
                        <td>
                            <div>
                                <div class="fs-14 fw-600">{{ $order->retailStore->name ?? 'N/A' }}</div>
                                <div class="fs-12 opacity-60">{{ $order->retailStore->store_code ?? '' }}</div>
                            </div>
                        </td>
                        @if(auth()->user()->user_type !== 'sales_rep')
                        <td>
                            @if($order->salesRepresentative)
                                <div class="fs-13">{{ $order->salesRepresentative->user->name }}</div>
                                <div class="fs-12 opacity-60">{{ $order->salesRepresentative->employee_id }}</div>
                            @endif
                        </td>
                        @endif
                        <td>
                            <div>
                                <div><strong>{{ single_price($order->grand_total) }}</strong></div>
                                @if($order->payment_status === 'partial')
                                    <small class="text-success">Paid: {{ single_price($order->paid_amount) }}</small><br>
                                    <small class="text-danger">Due: {{ single_price($order->due_amount) }}</small>
                                @elseif($order->payment_status === 'paid')
                                    <small class="text-success">Fully Paid</small>
                                @elseif($order->due_amount > 0)
                                    <small class="text-danger">Due: {{ single_price($order->due_amount) }}</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-inline badge-{{ $order->status_badge }}">
                                {{ $order->order_status_name }}
                            </span>
                        </td>
                        <td>
                            <div>
                                <span class="badge badge-inline badge-{{ $order->payment_badge }}">
                                    {{ $order->payment_status_name }}
                                </span>
                                @if($order->due_date && $order->isOverdue())
                                    <br><small class="text-danger">{{ $order->days_overdue }} days overdue</small>
                                @endif
                            </div>
                        </td>
                        <td>{{ $order->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    {{ translate('Actions') }}
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('store_orders.show', $order->id) }}">
                                        <i class="las la-eye"></i> {{ translate('View Details') }}
                                    </a>
                                    <a class="dropdown-item" href="{{ route('store_orders.invoice', $order->id) }}" target="_blank">
                                        <i class="las la-print"></i> {{ translate('Print Invoice') }}
                                    </a>
                                    @if($order->order_status !== 'delivered' && $order->order_status !== 'cancelled')
                                        <a class="dropdown-item" href="#" onclick="updateOrderStatus({{ $order->id }})">
                                            <i class="las la-edit"></i> {{ translate('Update Status') }}
                                        </a>
                                    @endif
                                    @if($order->payment_status !== 'paid')
                                        <a class="dropdown-item" href="#" onclick="updatePaymentStatus({{ $order->id }})">
                                            <i class="las la-money-bill"></i> {{ translate('Update Payment') }}
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="addPartialPayment({{ $order->id }})">
                                            <i class="las la-plus-circle"></i> {{ translate('Add Payment') }}
                                        </a>
                                    @endif
                                    @if($order->partialPayments && $order->partialPayments->count() > 0)
                                        <a class="dropdown-item" href="#" onclick="viewPaymentHistory({{ $order->id }})">
                                            <i class="las la-history"></i> {{ translate('Payment History') }}
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
            {{ $orders->appends(request()->input())->links() }}
        </div>
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
                            <option value="pending">{{ translate('Pending') }}</option>
                            <option value="confirmed">{{ translate('Confirmed') }}</option>
                            <option value="processing">{{ translate('Processing') }}</option>
                            <option value="shipped">{{ translate('Shipped') }}</option>
                            <option value="delivered">{{ translate('Delivered') }}</option>
                            <option value="cancelled">{{ translate('Cancelled') }}</option>
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
                            <option value="pending">{{ translate('Pending') }}</option>
                            <option value="paid">{{ translate('Paid') }}</option>
                            <option value="partial">{{ translate('Partial') }}</option>
                            <option value="failed">{{ translate('Failed') }}</option>
                            <option value="refunded">{{ translate('Refunded') }}</option>
                        </select>
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

<!-- Add Partial Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addPaymentForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Add Payment') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="orderSummary" class="alert alert-info">
                        <!-- Order summary will be populated here -->
                    </div>
                    <div class="form-group">
                        <label for="payment_amount">{{ translate('Payment Amount') }}</label>
                        <input type="number" class="form-control" name="payment_amount" step="0.01" min="0.01" required>
                        <small class="form-text text-muted">{{ translate('Enter the amount being paid') }}</small>
                    </div>
                    <div class="form-group">
                        <label for="payment_method">{{ translate('Payment Method') }}</label>
                        <select class="form-control" name="payment_method" required>
                            <option value="cash">{{ translate('Cash') }}</option>
                            <option value="bank_transfer">{{ translate('Bank Transfer') }}</option>
                            <option value="cheque">{{ translate('Cheque') }}</option>
                            <option value="credit_card">{{ translate('Credit Card') }}</option>
                            <option value="mobile_payment">{{ translate('Mobile Payment') }}</option>
                            <option value="store_credit">{{ translate('Store Credit') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="payment_reference">{{ translate('Reference/Transaction ID') }}</label>
                        <input type="text" class="form-control" name="payment_reference" placeholder="Enter reference number">
                    </div>
                    <div class="form-group">
                        <label for="payment_date">{{ translate('Payment Date') }}</label>
                        <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="payment_notes">{{ translate('Notes') }}</label>
                        <textarea class="form-control" name="payment_notes" rows="3" placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ translate('Add Payment') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment History Modal -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Payment History') }}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="paymentHistoryContent">
                    <!-- Payment history will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script type="text/javascript">
    function updateOrderStatus(orderId) {
        var actionUrl = '{{ url("admin/store-orders") }}/' + orderId + '/update-status';
        console.log('Status Update URL:', actionUrl);
        $('#statusUpdateForm').attr('action', actionUrl);
        $('#statusUpdateModal').modal('show');
    }

    function updatePaymentStatus(orderId) {
        var actionUrl = '{{ url("admin/store-orders") }}/' + orderId + '/update-payment';
        $('#paymentUpdateForm').attr('action', actionUrl);
        $('#paymentUpdateModal').modal('show');
    }

    function addPartialPayment(orderId) {
        // Get order details first
        $.get('{{ url("admin/store-orders") }}/' + orderId)
            .done(function(response) {
                // Extract order data from response or use the current page data
                var orderRow = $('tr').has('a[href*="store-orders/' + orderId + '"]');
                var orderCode = orderRow.find('a').text().trim();
                var totalAmount = orderRow.find('td').eq(4).find('strong').text();
                
                var actionUrl = '{{ url("admin/store-orders") }}/' + orderId + '/add-payment';
                $('#addPaymentForm').attr('action', actionUrl);
                
                // Update order summary
                $('#orderSummary').html(
                    '<strong>Order: ' + orderCode + '</strong><br>' +
                    'Total Amount: ' + totalAmount
                );
                
                $('#addPaymentModal').modal('show');
            })
            .fail(function() {
                var actionUrl = '{{ url("admin/store-orders") }}/' + orderId + '/add-payment';
                $('#addPaymentForm').attr('action', actionUrl);
                $('#addPaymentModal').modal('show');
            });
    }

    function viewPaymentHistory(orderId) {
        $('#paymentHistoryContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#paymentHistoryModal').modal('show');
        
        $.get('{{ url("admin/store-orders") }}/' + orderId + '/payment-history')
            .done(function(response) {
                var historyHtml = '<div class="row mb-3">';
                historyHtml += '<div class="col-md-4"><strong>Order Total:</strong> ' + response.order_total + '</div>';
                historyHtml += '<div class="col-md-4"><strong>Total Paid:</strong> <span class="text-success">' + response.total_paid + '</span></div>';
                historyHtml += '<div class="col-md-4"><strong>Remaining:</strong> <span class="text-danger">' + response.remaining_amount + '</span></div>';
                historyHtml += '</div><hr>';
                
                if (response.payments.length > 0) {
                    historyHtml += '<div class="table-responsive"><table class="table table-sm">';
                    historyHtml += '<thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Notes</th></tr></thead><tbody>';
                    
                    response.payments.forEach(function(payment) {
                        historyHtml += '<tr>';
                        historyHtml += '<td>' + new Date(payment.payment_date).toLocaleDateString() + '</td>';
                        historyHtml += '<td><span class="text-success">' + payment.payment_amount + '</span></td>';
                        historyHtml += '<td>' + payment.payment_method + '</td>';
                        historyHtml += '<td>' + (payment.payment_reference || '-') + '</td>';
                        historyHtml += '<td>' + (payment.payment_notes || '-') + '</td>';
                        historyHtml += '</tr>';
                    });
                    
                    historyHtml += '</tbody></table></div>';
                } else {
                    historyHtml += '<div class="text-center text-muted">No payment history found.</div>';
                }
                
                $('#paymentHistoryContent').html(historyHtml);
            })
            .fail(function() {
                $('#paymentHistoryContent').html('<div class="alert alert-danger">Error loading payment history.</div>');
            });
    }

    $(document).ready(function() {
        // Auto-submit form on filter change
        $('#sort_orders select').on('change', function() {
            $('#sort_orders').submit();
        });

        // Reset forms when modals are closed
        $('.modal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
        });
    });
</script>
@endsection