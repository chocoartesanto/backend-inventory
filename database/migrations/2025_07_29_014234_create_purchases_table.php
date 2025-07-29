<?php

// database/migrations/xxxx_xx_xx_create_purchases_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->time('invoice_time');
            $table->string('client_name');
            $table->string('seller_username');
            $table->string('client_phone')->nullable();
            $table->boolean('has_delivery')->default(false);
            $table->text('delivery_address')->nullable();
            $table->string('delivery_person')->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('subtotal_products', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('change_returned', 10, 2);
            $table->string('payment_method');
            $table->string('payment_reference')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->timestamps();

            // Índices para mejorar rendimiento
            $table->index('invoice_number');
            $table->index('seller_username');
            $table->index('invoice_date');
            $table->index('is_cancelled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
