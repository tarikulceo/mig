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
        Schema::table('store_orders', function (Blueprint $table) {
            $table->decimal('total_amount', 15, 2)->nullable()->after('grand_total');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
            $table->date('due_date')->nullable()->after('delivery_date');
            $table->integer('credit_days')->default(0)->after('due_date');
            $table->boolean('is_credit_order')->default(false)->after('credit_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'paid_amount', 'due_amount', 'due_date', 'credit_days', 'is_credit_order']);
        });
    }
};
