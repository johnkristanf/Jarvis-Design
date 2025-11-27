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
        Schema::create('fabric_adjust_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials');
            $table->string('action');
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->date('delivery_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fabric_adjust_logs');
    }
};
