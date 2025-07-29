<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('insumo_id');
            $table->decimal('cantidad', 10, 3);
            $table->timestamps();

            // Índices
            $table->unique(['product_id', 'insumo_id'], 'unique_product_insumo');
            $table->index('product_id', 'product_recipes_product_id_index');
            $table->index('insumo_id', 'product_recipes_insumo_id_index');

            // Claves foráneas
            $table->foreign('product_id', 'product_recipes_product_id_foreign')
                  ->references('id')->on('products')
                  ->onDelete('cascade');
                  
            $table->foreign('insumo_id', 'product_recipes_insumo_id_foreign')
                  ->references('id')->on('insumos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
