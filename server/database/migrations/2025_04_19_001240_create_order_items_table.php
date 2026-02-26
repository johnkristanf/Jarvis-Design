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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('color')->nullable();
            $table->decimal('product_unit_price', 15, 2);
            $table->unsignedInteger('total_quantity');
            $table->decimal('total_price', 15, 2);
            $table->string('own_design_url')->nullable();
            $table->string('business_design_url')->nullable();
            $table->unsignedInteger('solo_quantity')->nullable();
            $table->unsignedBigInteger('fabric_type_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
