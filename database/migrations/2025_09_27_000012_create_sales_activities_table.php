<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_rep_id');
            $table->string('activity_type'); // visit, call, meeting, order, follow_up
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('related_to_id')->nullable(); // store_id, customer_id, order_id
            $table->string('related_to_type')->nullable(); // retail_store, customer, order
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->text('outcome')->nullable();
            $table->json('attachments')->nullable(); // photos, documents
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
            
            $table->foreign('sales_rep_id')->references('id')->on('sales_representatives')->onDelete('cascade');
            
            // Indexes
            $table->index(['sales_rep_id', 'status']);
            $table->index(['activity_type']);
            $table->index(['scheduled_at']);
            $table->index(['related_to_type', 'related_to_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_activities');
    }
}