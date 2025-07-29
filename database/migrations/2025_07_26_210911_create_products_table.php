<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_producto')->comment('Nombre del producto');
            $table->string('variant')->nullable()->comment('Variante del producto');
            $table->decimal('precio', 10, 2)->comment('Precio en COP');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Índices y claves foráneas
            $table->index('categoria_id');
            // $table->index('name');
            $table->foreign('categoria_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};