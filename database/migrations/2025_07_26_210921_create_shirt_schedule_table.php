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
        Schema::create('shirt_schedule', function (Blueprint $table) {
            $table->string('day', 20)->primary(); // PRIMARY KEY para evitar duplicados
            $table->string('day_name', 20);
            $table->string('color', 20);
            $table->string('color_name', 50);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('updated_by', 100);
            
            // Índices
            $table->index('updated_at');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shirt_schedule');
    }
};