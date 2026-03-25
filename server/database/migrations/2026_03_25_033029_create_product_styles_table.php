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
        Schema::create('product_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('panel')->nullable();
            $table->string('attributes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_styles');
    }
};
