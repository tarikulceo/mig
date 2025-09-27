<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesRepresentativesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_representatives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('employee_id')->unique();
            $table->string('designation');
            $table->date('hire_date');
            $table->unsignedBigInteger('territory_id')->nullable();
            $table->decimal('sales_target_monthly', 15, 2)->default(0);
            $table->decimal('sales_target_quarterly', 15, 2)->default(0);
            $table->decimal('sales_target_yearly', 15, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->boolean('status')->default(1);
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('profile_image')->nullable();
            $table->text('notes')->nullable();
            
            // GPS and Mobile Features
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->timestamp('last_location_update')->nullable();
            $table->boolean('gps_enabled')->default(false);
            $table->json('device_info')->nullable();
            $table->string('fcm_token')->nullable(); // For push notifications
            
            // Performance Tracking
            $table->integer('total_visits')->default(0);
            $table->integer('successful_visits')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_commission_earned', 15, 2)->default(0);
            $table->date('last_active_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('territory_id')->references('id')->on('sales_territories')->onDelete('set null');
            $table->foreign('manager_id')->references('id')->on('sales_representatives')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['status', 'territory_id']);
            $table->index(['manager_id']);
            $table->index(['last_active_date']);
            $table->index(['current_latitude', 'current_longitude']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_representatives');
    }
}