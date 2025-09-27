<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_representative_id');
            $table->string('target_type'); // sales, orders, customers, visits
            $table->string('period'); // monthly, quarterly, yearly, custom
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('target_value', 15, 2);
            $table->decimal('achieved_value', 15, 2)->default(0);
            $table->decimal('achievement_percentage', 5, 2)->default(0);
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('sales_representative_id')->references('id')->on('sales_representatives')->onDelete('cascade');
            
            // Indexes
            $table->index(['sales_representative_id', 'period']);
            $table->index(['start_date', 'end_date']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_targets');
    }
}