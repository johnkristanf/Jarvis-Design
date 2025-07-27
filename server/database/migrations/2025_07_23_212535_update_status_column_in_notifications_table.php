<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop foreign key constraint first (if it exists)
            $table->dropForeign(['status_id']);

            // Drop the old integer column
            $table->dropColumn('status_id');

            // Add new string column
            $table->string('status')->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Remove the string column
            $table->dropColumn('status');

            // Re-add the foreign key integer column
            $table->foreignId('status_id')->constrained('order_status');
        });
    }
};
