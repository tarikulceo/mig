<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retail_store_id')->constrained('retail_stores')->onDelete('cascade');
            $table->foreignId('sales_rep_id')->constrained('sales_representatives')->onDelete('cascade');
            $table->timestamp('visit_date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->json('photos')->nullable(); // Photos taken during visit
            $table->decimal('order_amount', 15, 2)->default(0); // Orders placed during visit
            $table->enum('visit_status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->json('products_discussed')->nullable(); // Products shown/discussed
            $table->text('feedback')->nullable(); // Store owner feedback
            $table->text('next_action')->nullable(); // Follow-up actions
            $table->timestamp('next_visit_date')->nullable();
            $table->decimal('travel_distance', 8, 2)->nullable(); // Distance traveled in km
            $table->integer('duration_minutes')->nullable(); // Visit duration
            $table->json('competitor_info')->nullable(); // Competitor analysis
            $table->boolean('order_placed')->default(false); // Whether order was placed
            $table->string('visit_type')->default('regular'); // regular, follow_up, emergency, demo
            $table->timestamps();

            // Indexes for performance
            $table->index('retail_store_id');
            $table->index('sales_rep_id');
            $table->index('visit_date');
            $table->index('visit_status');
            $table->index(['visit_date', 'sales_rep_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_visits');
    }
}