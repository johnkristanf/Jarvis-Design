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
        Schema::create('designables_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('quantity_used');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade'); 

            // POLYMORPHIC RELATIOSHIP BETWEEN DESIGNS AND UPLOADED DESIGNS
            $table->morphs('designable');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designables_materials');
    }
};
