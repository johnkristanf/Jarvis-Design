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
        Schema::create('designs_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('quantity_used');
            $table->foreignId('design_id')->constrained('designs')->onDelete('cascade'); 
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designs_materials');
    }
};
