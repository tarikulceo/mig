<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTerritoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_territories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('region');
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->json('cities')->nullable(); // Array of cities
            $table->json('zip_codes')->nullable(); // Array of zip codes
            $table->boolean('is_active')->default(1);
            
            // Geographic boundaries
            $table->decimal('north_boundary', 10, 8)->nullable();
            $table->decimal('south_boundary', 10, 8)->nullable();
            $table->decimal('east_boundary', 11, 8)->nullable();
            $table->decimal('west_boundary', 11, 8)->nullable();
            
            // Territory targets and metrics
            $table->decimal('monthly_target', 15, 2)->default(0);
            $table->decimal('yearly_target', 15, 2)->default(0);
            $table->integer('customer_count')->default(0);
            $table->integer('store_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['is_active', 'region']);
            $table->index(['country_id', 'state_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_territories');
    }
}