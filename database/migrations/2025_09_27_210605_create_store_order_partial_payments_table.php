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
        Schema::create('store_order_partial_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_order_id')->constrained('store_orders')->onDelete('cascade');
            $table->foreignId('retail_store_id')->constrained('retail_stores')->onDelete('cascade');
            $table->decimal('payment_amount', 15, 2);
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();
            $table->timestamp('payment_date');
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('completed'); // completed, cancelled, failed
            $table->timestamps();

            // Indexes for better performance
            $table->index(['store_order_id']);
            $table->index(['retail_store_id']);
            $table->index(['payment_date']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_order_partial_payments');
    }
};
