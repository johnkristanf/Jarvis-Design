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
        Schema::create('sublimation_attributes', function (Blueprint $table) {
            $table->id();

            $table->string('neck_type')->nullable();       // e.g., V-Neck, Round Neck
            $table->string('collar_type')->nullable();     // e.g., Regular, Chinese, Turtle Neck
            $table->string('sleeve_style')->nullable();    // e.g., Regular Arm Sleeve, Long Sleeve
            $table->string('back_cut')->nullable();        // e.g., NBA Cut, Regular Cut
            $table->boolean('with_arm_cuffs')->default(false); // True or False

            $table->foreignId('category_id')->constrained('design_categories')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sublimation_attributes');
    }
};
