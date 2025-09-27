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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('color');
            $table->decimal('product_unit_price', 15, 2);
            $table->enum('design_type', ['own-design', 'business-design', 'ai-generation']);
            $table->string('order_option');

            $table->unsignedInteger('total_quantity');
            $table->decimal('total_price', 10, 2);

            $table->unsignedInteger('solo_quantity')->nullable();

            $table->string('own_design_url')->nullable();
            $table->string('business_design_url')->nullable();

            $table->foreignId('product_id')->constrained('products');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
