@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Due Amounts Management') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('store_orders.export_due_amounts', request()->query()) }}" class="btn btn-circle btn-success">
                <span>{{ translate('Export CSV') }}</span>
            </a>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row gutters-5 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="las la-money-bill text-primary mb-2" style="font-size: 2rem;"></i>
                <h6 class="fw-600 fs-13 text-secondary mb-0">{{ translate('Total Due Amount') }}</h6>
                <span class="fw-600 fs-17">{{ single_price($totalDue) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="las la-exclamation-triangle text-danger mb-2" style="font-size: 2rem;"></i>
                <h6 class="fw-600 fs-13 text-secondary mb-0">{{ translate('Overdue Amount') }}</h6>
                <span class="fw-600 fs-17 text-danger">{{ single_price($overdueDue) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="las la-store text-info mb-2" style="font-size: 2rem;"></i>
                <h6 class="fw-600 fs-13 text-secondary mb-0">{{ translate('Stores with Due') }}</h6>
                <span class="fw-600 fs-17">{{ $storeWiseDue->count() }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="las la-receipt text-warning mb-2" style="font-size: 2rem;"></i>
                <h6 class="fw-600 fs-13 text-secondary mb-0">{{ translate('Due Orders') }}</h6>
                <span class="fw-600 fs-17">{{ $orders->total() }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Filter Due Orders') }}</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('store_orders.due_amounts') }}">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('Retail Store') }}</label>
                        <select class="form-control aiz-selectpicker" name="retail_store_id" data-live-search="true">
                            <option value="">{{ translate('All Stores') }}</option>
                            @foreach(\App\Models\RetailStore::all() as $store)
                                <option value="{{ $store->id }}" {{ request('retail_store_id') == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }} ({{ $store->store_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('Sales Representative') }}</label>
                        <select class="form-control aiz-selectpicker" name="sales_rep_id" data-live-search="true">
                            <option value="">{{ translate('All Sales Reps') }}</option>
                            @foreach(\App\Models\SalesRepresentative::with('user')->get() as $rep)
                                <option value="{{ $rep->id }}" {{ request('sales_rep_id') == $rep->id ? 'selected' : '' }}>
                                    {{ $rep->user->name }} ({{ $rep->employee_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ translate('Status') }}</label>
                        <select class="form-control" name="overdue">
                            <option value="">{{ translate('All') }}</option>
                            <option value="1" {{ request('overdue') == '1' ? 'selected' : '' }}>{{ translate('Overdue Only') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Store-wise Due Summary -->
@if($storeWiseDue->count() > 0)
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Store-wise Due Summary') }}</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Store Name') }}</th>
                        <th>{{ translate('Store Code') }}</th>
                        <th>{{ translate('Orders Count') }}</th>
                        <th>{{ translate('Total Due Amount') }}</th>
                        <th>{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($storeWiseDue->take(10) as $storeDue)
                        @php
                            $store = \App\Models\RetailStore::find($storeDue->retail_store_id);
                        @endphp
                        <tr>
                            <td>{{ $store->name ?? 'Store #'.$storeDue->retail_store_id }}</td>
                            <td>{{ $store->store_code ?? 'N/A' }}</td>
                            <td>{{ $storeDue->order_count }}</td>
                            <td><strong class="text-danger">{{ single_price($storeDue->total_due) }}</strong></td>
                            <td>
                                <a href="{{ route('store_orders.due_amounts', ['retail_store_id' => $storeDue->retail_store_id]) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    {{ translate('View Orders') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Due Orders List -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Due Orders') }}</h5>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Order Code') }}</th>
                    <th>{{ translate('Store') }}</th>
                    <th>{{ translate('Sales Rep') }}</th>
                    <th>{{ translate('Order Date') }}</th>
                    <th>{{ translate('Due Date') }}</th>
                    <th>{{ translate('Amounts') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $key => $order)
                    <tr>
                        <td>{{ ($key+1) + ($orders->currentPage() - 1)*$orders->perPage() }}</td>
                        <td>
                            <a href="{{ route('store_orders.show', $order->id) }}" class="text-primary font-weight-bold">
                                {{ $order->order_code }}
                            </a>
                        </td>
                        <td>
                            @php
                                $store = \App\Models\RetailStore::find($order->retail_store_id);
                            @endphp
                            <div>
                                <div class="fs-14 fw-600">{{ $store->name ?? 'Store #'.$order->retail_store_id }}</div>
                                <div class="fs-12 opacity-60">{{ $store->store_code ?? 'N/A' }}</div>
                            </div>
                        </td>
                        <td>
                            @php
                                $salesRep = \App\Models\SalesRepresentative::find($order->sales_rep_id);
                                $user = $salesRep ? \App\Models\User::find($salesRep->user_id) : null;
                            @endphp
                            @if($salesRep && $user)
                                <div class="fs-13">{{ $user->name }}</div>
                                <div class="fs-12 opacity-60">{{ $salesRep->employee_id }}</div>
                            @else
                                <div class="fs-12 opacity-60">Rep #{{ $order->sales_rep_id ?? 'N/A' }}</div>
                            @endif
                        </td>
                        <td>{{ $order->created_at->format('M d, Y') }}</td>
                        <td>
                            @if($order->due_date)
                                <div>{{ \Carbon\Carbon::parse($order->due_date)->format('M d, Y') }}</div>
                                @php
                                    $isOverdue = \Carbon\Carbon::parse($order->due_date)->isPast();
                                    $daysOverdue = $isOverdue ? \Carbon\Carbon::parse($order->due_date)->diffInDays(now()) : 0;
                                @endphp
                                @if($isOverdue)
                                    <small class="text-danger">{{ $daysOverdue }} days overdue</small>
                                @endif
                            @else
                                <span class="text-muted">No due date</span>
                            @endif
                        </td>
                        <td>
                            <div>
                                <div><strong>Total: {{ single_price($order->grand_total) }}</strong></div>
                                <div class="text-success">Paid: {{ single_price($order->paid_amount) }}</div>
                                <div class="text-danger"><strong>Due: {{ single_price($order->due_amount) }}</strong></div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <span class="badge badge-inline badge-{{ $order->payment_status === 'partial' ? 'warning' : 'danger' }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                                @if($order->isOverdue())
                                    <br><span class="badge badge-inline badge-danger">Overdue</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    {{ translate('Actions') }}
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('store_orders.show', $order->id) }}">
                                        <i class="las la-eye"></i> {{ translate('View Details') }}
                                    </a>
                                    <a class="dropdown-item" href="#" onclick="addPartialPayment({{ $order->id }})">
                                        <i class="las la-plus-circle"></i> {{ translate('Add Payment') }}
                                    </a>
                                    @if($order->partialPayments && $order->partialPayments->count() > 0)
                                        <a class="dropdown-item" href="#" onclick="viewPaymentHistory({{ $order->id }})">
                                            <i class="las la-history"></i> {{ translate('Payment History') }}
                                        </a>
                                    @endif
                                    <a class="dropdown-item" href="{{ route('store_orders.invoice', $order->id) }}" target="_blank">
                                        <i class="las la-print"></i> {{ translate('Print Invoice') }}
                                    </a>
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

<!-- Add Payment Modal (reuse from index) -->
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
<script>
    function addPartialPayment(orderId) {
        // Get order details from the table row
        var orderRow = $('tr').has('a[href*="store-orders/' + orderId + '"]');
        var orderCode = orderRow.find('a').first().text().trim();
        var totalAmount = orderRow.find('td').eq(6).find('strong').first().text();
        var paidAmount = orderRow.find('.text-success').text();
        var dueAmount = orderRow.find('.text-danger strong').text();
        
        var actionUrl = '{{ url("admin/store-orders") }}/' + orderId + '/add-payment';
        $('#addPaymentForm').attr('action', actionUrl);
        
        // Update order summary
        $('#orderSummary').html(
            '<strong>Order: ' + orderCode + '</strong><br>' +
            'Total Amount: ' + totalAmount + '<br>' +
            'Paid Amount: ' + paidAmount + '<br>' +
            '<strong>Due Amount: ' + dueAmount + '</strong>'
        );
        
        // Set max payment amount
        var dueAmountNumber = parseFloat(dueAmount.replace(/[^0-9.-]+/g,""));
        $('#addPaymentForm input[name="payment_amount"]').attr('max', dueAmountNumber);
        
        $('#addPaymentModal').modal('show');
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
        // Reset forms when modals are closed
        $('.modal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
        });
    });
</script>
@endsection