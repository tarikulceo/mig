<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_rep_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('store_order_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('commission_type')->default('order'); // order, product, target_bonus, store_order
            $table->decimal('order_amount', 15, 2)->default(0);
            $table->decimal('sale_amount', 15, 2)->default(0);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->date('commission_date')->nullable();
            $table->string('status')->default('pending'); // pending, approved, paid
            $table->string('payment_status')->default('pending'); // pending, approved, paid
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('sales_rep_id')->references('id')->on('sales_representatives')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('store_order_id')->references('id')->on('store_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            
            // Indexes
            $table->index(['sales_rep_id', 'status']);
            $table->index(['commission_date']);
            $table->index(['commission_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_commissions');
    }
}