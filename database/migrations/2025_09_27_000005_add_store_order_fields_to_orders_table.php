<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStoreOrderFieldsToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add fields to connect main orders with retail stores
            $table->foreignId('retail_store_id')->nullable()->constrained('retail_stores')->onDelete('set null');
            $table->enum('order_source', ['online', 'retail_store', 'phone', 'sales_rep'])->default('online');
            $table->foreignId('store_visit_id')->nullable()->constrained('store_visits')->onDelete('set null');
            $table->boolean('is_store_order')->default(false);
            $table->json('store_delivery_info')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['retail_store_id']);
            $table->dropForeign(['store_visit_id']);
            $table->dropColumn(['retail_store_id', 'order_source', 'store_visit_id', 'is_store_order', 'store_delivery_info']);
        });
    }
}