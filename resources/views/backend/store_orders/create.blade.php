@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Create Store Order')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('store_orders.index') }}" class="btn btn-light">
                <i class="las la-arrow-left"></i>
                <span>{{translate('Back to Orders')}}</span>
            </a>
        </div>
    </div>
</div>

<form action="{{ route('store_orders.store') }}" method="POST" id="storeOrderForm">
    @csrf
    
    <div class="row">
        <!-- Order Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Order Information')}}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="retail_store_id">{{translate('Select Store')}} <span class="text-danger">*</span></label>
                                <select class="form-control aiz-selectpicker" name="retail_store_id" id="retail_store_id" data-live-search="true" required>
                                    <option value="">{{translate('Select a store')}}</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" data-tokens="{{ $store->name }} {{ $store->store_code }}">
                                            {{ $store->name }} ({{ $store->store_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="store_visit_id">{{translate('Related Store Visit')}} (Optional)</label>
                                <select class="form-control aiz-selectpicker" name="store_visit_id" id="store_visit_id" disabled>
                                    <option value="">{{translate('No visit selected')}}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="order_date">{{translate('Order Date')}} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="order_date" id="order_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expected_delivery_date">{{translate('Expected Delivery Date')}}</label>
                                <input type="date" class="form-control" name="expected_delivery_date" id="expected_delivery_date" min="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">{{translate('Order Notes')}}</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="{{translate('Enter any special notes or instructions')}}"></textarea>
                    </div>
                </div>
            </div>

            <!-- Products Selection -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Select Products')}}</h5>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addProductRow()">
                        <i class="las la-plus"></i> {{translate('Add Product')}}
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="productsTable">
                            <thead>
                                <tr>
                                    <th width="40%">{{translate('Product')}}</th>
                                    <th width="15%">{{translate('Price')}}</th>
                                    <th width="15%">{{translate('Quantity')}}</th>
                                    <th width="15%">{{translate('Discount')}}</th>
                                    <th width="15%">{{translate('Total')}}</th>
                                    <th width="10%">{{translate('Action')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Product rows will be added here dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Order Summary')}}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <label>{{translate('Subtotal')}}:</label>
                        </div>
                        <div class="col-6 text-right">
                            <span id="subtotalAmount">RM0.00</span>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-6">
                            <label for="tax_amount">{{translate('Tax')}} (%):</label>
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm text-right" name="tax_amount" id="tax_amount" value="0" min="0" max="100" step="0.01" onchange="calculateTotal()">
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-6">
                            <label for="shipping_amount">{{translate('Shipping')}}:</label>
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm text-right" name="shipping_amount" id="shipping_amount" value="0" min="0" step="0.01" onchange="calculateTotal()">
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-6">
                            <label for="discount_amount">{{translate('Discount')}}:</label>
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm text-right" name="discount_amount" id="discount_amount" value="0" min="0" step="0.01" onchange="calculateTotal()">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-6">
                            <strong>{{translate('Grand Total')}}:</strong>
                        </div>
                        <div class="col-6 text-right">
                            <strong><span id="grandTotalAmount">RM0.00</span></strong>
                            <input type="hidden" name="grand_total" id="grand_total" value="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Payment Information')}}</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="payment_method">{{translate('Payment Method')}}</label>
                        <select class="form-control" name="payment_method" id="payment_method">
                            <option value="cash">{{translate('Cash')}}</option>
                            <option value="card">{{translate('Card')}}</option>
                            <option value="bank_transfer">{{translate('Bank Transfer')}}</option>
                            <option value="cheque">{{translate('Cheque')}}</option>
                            <option value="credit">{{translate('Store Credit')}}</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_status">{{translate('Payment Status')}}</label>
                        <select class="form-control" name="payment_status" id="payment_status">
                            <option value="pending">{{translate('Pending')}}</option>
                            <option value="paid">{{translate('Paid')}}</option>
                            <option value="partial">{{translate('Partial')}}</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="paidAmountGroup" style="display: none;">
                        <label for="paid_amount">{{translate('Paid Amount')}}</label>
                        <input type="number" class="form-control" name="paid_amount" id="paid_amount" value="0" min="0" step="0.01">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="las la-save"></i> {{translate('Create Order')}}
                    </button>
                    <a href="{{ route('store_orders.index') }}" class="btn btn-light btn-block mt-2">
                        {{translate('Cancel')}}
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@section('script')
<script type="text/javascript">
    let productRowIndex = 0;
    let products = @json($products);

    // Product row template
    function getProductRowTemplate(index) {
        let productOptions = '<option value="">Select Product</option>';
        products.forEach(function(product) {
            // Use the correct price field - check if it exists, otherwise use default
            const price = product.unit_price || product.price || 0;
            productOptions += `<option value="${product.id}" data-price="${price}">${product.name}</option>`;
        });

        return `
            <tr id="productRow${index}">
                <td>
                    <select class="form-control product-select" name="products[${index}][product_id]" onchange="updateProductPrice(${index})" required>
                        ${productOptions}
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control product-price" name="products[${index}][unit_price]" 
                           id="productPrice${index}" step="0.01" min="0" onchange="calculateRowTotal(${index})">
                </td>
                <td>
                    <input type="number" class="form-control product-quantity" name="products[${index}][quantity]" 
                           id="productQuantity${index}" value="1" min="1" onchange="calculateRowTotal(${index})">
                </td>
                <td>
                    <input type="number" class="form-control product-discount" name="products[${index}][discount_amount]" 
                           id="productDiscount${index}" value="0" min="0" step="0.01" onchange="calculateRowTotal(${index})">
                </td>
                <td>
                    <input type="number" class="form-control row-total" id="rowTotal${index}" step="0.01" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow(${index})">
                        <i class="las la-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    function addProductRow() {
        const tbody = document.querySelector('#productsTable tbody');
        tbody.insertAdjacentHTML('beforeend', getProductRowTemplate(productRowIndex));
        productRowIndex++;
    }

    function removeProductRow(index) {
        const row = document.getElementById(`productRow${index}`);
        if (row) {
            row.remove();
            calculateTotal();
        }
    }

    function updateProductPrice(index) {
        const select = document.querySelector(`#productRow${index} .product-select`);
        const priceInput = document.getElementById(`productPrice${index}`);
        
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            priceInput.value = price.toFixed(2);
            calculateRowTotal(index);
        } else {
            priceInput.value = '0.00';
            document.getElementById(`rowTotal${index}`).value = '0.00';
            calculateTotal();
        }
    }

    function calculateRowTotal(index) {
        const price = parseFloat(document.getElementById(`productPrice${index}`).value) || 0;
        const quantity = parseInt(document.getElementById(`productQuantity${index}`).value) || 0;
        const discount = parseFloat(document.getElementById(`productDiscount${index}`).value) || 0;
        
        // Calculate subtotal for this row (price * quantity)
        const lineSubtotal = price * quantity;
        // Apply discount to get final total for this row
        const total = Math.max(0, lineSubtotal - discount);
        
        document.getElementById(`rowTotal${index}`).value = total.toFixed(2);
        
        calculateTotal();
    }

    function calculateTotal() {
        let subtotal = 0;
        
        // Sum all row totals (these already have discounts applied)
        document.querySelectorAll('.row-total').forEach(function(input) {
            const value = parseFloat(input.value) || 0;
            subtotal += value;
        });
        
        const tax = parseFloat(document.getElementById('tax_amount').value) || 0;
        const shipping = parseFloat(document.getElementById('shipping_amount').value) || 0;
        const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
        
        // Calculate tax amount based on subtotal
        const taxAmount = (subtotal * tax) / 100;
        // Calculate grand total
        const grandTotal = Math.max(0, subtotal + taxAmount + shipping - discount);
        
        // Update display values with proper currency formatting
        const currencySymbol = 'RM';  // Using RM as shown in your examples
        document.getElementById('subtotalAmount').textContent = currencySymbol + subtotal.toFixed(2);
        document.getElementById('grandTotalAmount').textContent = currencySymbol + grandTotal.toFixed(2);
        document.getElementById('grand_total').value = grandTotal.toFixed(2);
        
        // Update paid amount if payment is full
        const paymentStatus = document.getElementById('payment_status').value;
        if (paymentStatus === 'paid') {
            document.getElementById('paid_amount').value = grandTotal.toFixed(2);
        }
    }

    // Store selection change handler
    document.getElementById('retail_store_id').addEventListener('change', function() {
        const storeId = this.value;
        const visitSelect = document.getElementById('store_visit_id');
        
        // Clear and disable visit select
        visitSelect.innerHTML = '<option value="">{{translate("Loading visits...")}}</option>';
        visitSelect.disabled = true;
        
        if (storeId) {
            // Fetch recent visits for the selected store
            fetch(`/admin/store-visits/by-store/${storeId}`)
                .then(response => response.json())
                .then(data => {
                    visitSelect.innerHTML = '<option value="">{{translate("No visit selected")}}</option>';
                    data.visits.forEach(visit => {
                        visitSelect.innerHTML += `<option value="${visit.id}">
                            ${visit.visit_date} - ${visit.purpose}
                        </option>`;
                    });
                    visitSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    visitSelect.innerHTML = '<option value="">{{translate("Error loading visits")}}</option>';
                });
        }
    });

    // Payment status change handler
    document.getElementById('payment_status').addEventListener('change', function() {
        const paidAmountGroup = document.getElementById('paidAmountGroup');
        const paidAmountInput = document.getElementById('paid_amount');
        const grandTotal = parseFloat(document.getElementById('grand_total').value) || 0;
        
        if (this.value === 'partial' || this.value === 'paid') {
            paidAmountGroup.style.display = 'block';
            if (this.value === 'paid') {
                paidAmountInput.value = grandTotal.toFixed(2);
            } else if (this.value === 'partial') {
                paidAmountInput.value = '0.00';
                paidAmountInput.focus();
            }
        } else {
            paidAmountGroup.style.display = 'none';
            paidAmountInput.value = '0.00';
        }
    });

    // Initialize with one product row
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Products loaded:', products.length);
        addProductRow();
        
        // Initial calculation
        calculateTotal();
    });

    // Form validation
    document.getElementById('storeOrderForm').addEventListener('submit', function(e) {
        const productRows = document.querySelectorAll('#productsTable tbody tr');
        if (productRows.length === 0) {
            e.preventDefault();
            alert('{{translate("Please add at least one product to the order.")}}');
            return false;
        }
        
        let hasValidProducts = false;
        productRows.forEach(function(row) {
            const select = row.querySelector('.product-select');
            if (select && select.value) {
                hasValidProducts = true;
            }
        });
        
        if (!hasValidProducts) {
            e.preventDefault();
            alert('{{translate("Please select at least one valid product.")}}');
            return false;
        }
    });
</script>
@endsection