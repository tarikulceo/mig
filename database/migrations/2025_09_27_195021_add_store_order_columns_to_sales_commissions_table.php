<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_commissions', function (Blueprint $table) {
            // Check if column doesn't exist before adding it
            if (!Schema::hasColumn('sales_commissions', 'store_order_id')) {
                $table->unsignedBigInteger('store_order_id')->nullable();
                
                // Add foreign key for store_order_id
                $table->foreign('store_order_id')->references('id')->on('store_orders')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_commissions', function (Blueprint $table) {
            if (Schema::hasColumn('sales_commissions', 'store_order_id')) {
                // Drop foreign key first
                $table->dropForeign(['store_order_id']);
                
                // Drop the added columns
                $table->dropColumn(['store_order_id']);
            }
        });
    }
};
