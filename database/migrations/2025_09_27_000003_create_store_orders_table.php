<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('retail_store_id')->constrained('retail_stores')->onDelete('cascade');
            $table->foreignId('sales_rep_id')->constrained('sales_representatives')->onDelete('cascade');
            $table->foreignId('store_visit_id')->nullable()->constrained('store_visits')->onDelete('set null');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->enum('order_status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'partial', 'failed', 'refunded'])->default('pending');
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'credit', 'cheque'])->default('cash');
            $table->text('order_notes')->nullable();
            $table->json('delivery_address')->nullable();
            $table->timestamp('delivery_date')->nullable();
            $table->timestamp('expected_delivery')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('commission_paid')->default(false);
            $table->json('order_items')->nullable(); // Simplified order items storage
            $table->timestamps();

            // Indexes for performance
            $table->index('retail_store_id');
            $table->index('sales_rep_id');
            $table->index('store_visit_id');
            $table->index('order_status');
            $table->index('payment_status');
            $table->index(['order_status', 'sales_rep_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_orders');
    }
}