<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the ingredients column if it exists
            if (Schema::hasColumn('products', 'ingredients')) {
                $table->dropColumn('ingredients');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Optional: Restore the column if needed
            $table->json('ingredients')->nullable();
        });
    }
};
