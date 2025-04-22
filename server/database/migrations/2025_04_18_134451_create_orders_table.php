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
            $table->string('order_id');
            $table->string('option');

            $table->decimal('paid_amount', 15, 2);
            $table->unsignedInteger('quantity');

            $table->foreignId('color_id')->constrained('colors');
            $table->foreignId('size_id')->constrained('sizes');

            $table->foreignId('type_id')->constrained('order_types');
            $table->foreignId('design_id')->constrained('designs');

            $table->foreignId('status_id')->constrained('order_status')->default(1);
            $table->foreignId('user_id')->constrained('users');

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
