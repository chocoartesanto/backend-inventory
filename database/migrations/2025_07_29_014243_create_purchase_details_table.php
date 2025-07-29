<?php


// database/migrations/xxxx_xx_xx_create_purchase_details_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->string('product_name');
            $table->string('product_variant')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            // Índices para mejorar rendimiento
            $table->index('purchase_id');
            $table->index('product_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_details');
    }
};