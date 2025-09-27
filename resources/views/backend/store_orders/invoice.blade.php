<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{translate('Invoice')}} - {{ $order->order_code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-info h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
        }
        .company-info p {
            margin: 5px 0;
            color: #666;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .invoice-info p {
            margin: 5px 0;
        }
        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .billing-info {
            width: 45%;
        }
        .billing-info h3 {
            margin: 0 0 10px 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .billing-info p {
            margin: 5px 0;
        }
        .order-details {
            margin-bottom: 30px;
        }
        .order-details h3 {
            margin: 0 0 15px 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .summary-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #ddd;
        }
        .summary-table .total-row {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #333;
        }
        .payment-info {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .payment-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .status-badges {
            margin: 20px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-confirmed { background-color: #17a2b8; color: #fff; }
        .status-processing { background-color: #fd7e14; color: #fff; }
        .status-shipped { background-color: #6f42c1; color: #fff; }
        .status-delivered { background-color: #28a745; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }
        .status-paid { background-color: #28a745; color: #fff; }
        .status-partial { background-color: #ffc107; color: #000; }
        .status-failed { background-color: #dc3545; color: #fff; }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        @media print {
            body { margin: 0; padding: 0; }
            .invoice-container { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>{{ get_setting('site_name', 'Your Company') }}</h1>
                <p>{{ get_setting('contact_address', '') }}</p>
                <p>Phone: {{ get_setting('contact_phone', '') }}</p>
                <p>Email: {{ get_setting('contact_email', '') }}</p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>Invoice #:</strong> {{ $order->order_code }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('M d, Y') }}</p>
                <p><strong>Due Date:</strong> {{ $order->expected_delivery_date ? $order->expected_delivery_date->format('M d, Y') : 'N/A' }}</p>
            </div>
        </div>

        <!-- Status Badges -->
        <div class="status-badges">
            <span class="status-badge status-{{ $order->order_status }}">
                {{ ucfirst($order->order_status) }}
            </span>
            <span class="status-badge status-{{ $order->payment_status }}">
                Payment: {{ ucfirst($order->payment_status) }}
            </span>
        </div>

        <!-- Billing Section -->
        <div class="billing-section">
            <div class="billing-info">
                <h3>Bill To:</h3>
                <p><strong>{{ $order->retailStore->name }}</strong></p>
                <p>Store Code: {{ $order->retailStore->store_code }}</p>
                <p>{{ $order->retailStore->address }}</p>
                <p>{{ $order->retailStore->city }}, {{ $order->retailStore->state }}</p>
                @if($order->retailStore->contact_person)
                    <p>Contact: {{ $order->retailStore->contact_person }}</p>
                @endif
                <p>Phone: {{ $order->retailStore->phone }}</p>
                @if($order->retailStore->email)
                    <p>Email: {{ $order->retailStore->email }}</p>
                @endif
            </div>
            <div class="billing-info">
                <h3>Sales Representative:</h3>
                @if($order->salesRepresentative)
                    <p><strong>{{ $order->salesRepresentative->user->name }}</strong></p>
                    <p>ID: {{ $order->salesRepresentative->employee_id }}</p>
                    <p>Phone: {{ $order->salesRepresentative->user->phone ?? 'N/A' }}</p>
                    <p>Email: {{ $order->salesRepresentative->user->email }}</p>
                @else
                    <p>N/A</p>
                @endif
            </div>
        </div>

        <!-- Order Details -->
        <div class="order-details">
            <h3>Order Details</h3>
            <p><strong>Order Date:</strong> {{ $order->order_date ? $order->order_date->format('M d, Y') : 'Not specified' }}</p>
            @if($order->expected_delivery_date)
                <p><strong>Expected Delivery:</strong> {{ $order->expected_delivery_date->format('M d, Y') }}</p>
            @endif
            @if($order->storeVisit)
                <p><strong>Related Store Visit:</strong> {{ $order->storeVisit->visit_date->format('M d, Y') }} - {{ $order->storeVisit->purpose }}</p>
            @endif
            @if($order->notes)
                <p><strong>Notes:</strong> {{ $order->notes }}</p>
            @endif
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->orderItems ?? [] as $key => $item)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->product && $item->product->brand)
                            <br><small>{{ $item->product->brand->name }}</small>
                        @endif
                    </td>
                    <td>{{ $item->product ? $item->product->sku : 'N/A' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ single_price($item->unit_price) }}</td>
                    <td class="text-right">{{ single_price($item->discount_amount) }}</td>
                    <td class="text-right">{{ single_price($item->total_amount) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">
                        <em>No order items found</em>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary -->
        <table class="summary-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">{{ single_price($order->subtotal) }}</td>
            </tr>
            @if($order->tax_amount > 0)
            <tr>
                <td>Tax:</td>
                <td class="text-right">{{ single_price($order->tax_amount) }}</td>
            </tr>
            @endif
            @if($order->shipping_amount > 0)
            <tr>
                <td>Shipping:</td>
                <td class="text-right">{{ single_price($order->shipping_amount) }}</td>
            </tr>
            @endif
            @if($order->discount_amount > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">-{{ single_price($order->discount_amount) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total:</td>
                <td class="text-right">{{ single_price($order->grand_total) }}</td>
            </tr>
        </table>

        <!-- Payment Information -->
        <div class="payment-info">
            <h3>Payment Information</h3>
            <p><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</p>
            <p><strong>Payment Status:</strong> {{ ucfirst($order->payment_status) }}</p>
            @if($order->payment_status === 'partial')
                <p><strong>Paid Amount:</strong> {{ single_price($order->paid_amount) }}</p>
                <p><strong>Due Amount:</strong> {{ single_price($order->grand_total - $order->paid_amount) }}</p>
            @elseif($order->payment_status === 'paid')
                <p><strong>Paid Amount:</strong> {{ single_price($order->grand_total) }}</p>
            @endif
        </div>

        <!-- Commission Information (if any) -->
        @if($order->commission_amount > 0)
        <div class="payment-info" style="margin-top: 20px;">
            <h3>Commission Details</h3>
            <p>Store Order Commission: {{ $order->commission_rate ?? 0 }}% = {{ single_price($order->commission_amount ?? 0) }}</p>
            <p><strong>Total Commission: {{ single_price($order->commission_amount ?? 0) }}</strong></p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated invoice.</p>
        </div>
    </div>

    <script>
        // Auto-print when loaded (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>