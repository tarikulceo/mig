<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRetailStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retail_stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('store_code')->unique();
            $table->text('description')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('store_type', ['retail', 'wholesale', 'dealer'])->default('retail');
            $table->decimal('store_size', 10, 2)->nullable(); // Square feet
            $table->integer('staff_count')->default(1);
            $table->string('image')->nullable();
            $table->json('images')->nullable(); // Multiple store images
            $table->foreignId('sales_rep_id')->constrained('sales_representatives')->onDelete('cascade');
            $table->foreignId('territory_id')->nullable()->constrained('sales_territories')->onDelete('set null');
            $table->decimal('monthly_target', 15, 2)->default(0);
            $table->decimal('yearly_target', 15, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->json('business_hours')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('established_date')->nullable();
            $table->timestamp('last_visit')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes for performance
            $table->index('sales_rep_id');
            $table->index('territory_id');
            $table->index('status');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('retail_stores');
    }
}