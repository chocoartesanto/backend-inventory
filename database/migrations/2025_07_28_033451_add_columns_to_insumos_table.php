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
        Schema::table('insumos', function (Blueprint $table) {
            // Agregar todas las columnas que faltan
            $table->string('nombre_insumo')->unique()->after('id');
            $table->string('unidad', 50)->after('nombre_insumo');
            $table->decimal('cantidad_unitaria', 10, 2)->after('unidad');
            $table->decimal('precio_presentacion', 10, 2)->after('cantidad_unitaria');
            $table->decimal('cantidad_utilizada', 10, 2)->default(0)->after('precio_presentacion');
            $table->decimal('cantidad_por_producto', 10, 2)->default(0)->after('cantidad_utilizada');
            $table->decimal('stock_minimo', 10, 2)->default(0)->after('cantidad_por_producto');
            $table->string('sitio_referencia', 255)->nullable()->after('stock_minimo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insumos', function (Blueprint $table) {
            // Eliminar las columnas en orden inverso
            $table->dropColumn([
                'sitio_referencia',
                'stock_minimo',
                'cantidad_por_producto',
                'cantidad_utilizada',
                'precio_presentacion',
                'cantidad_unitaria',
                'unidad',
                'nombre_insumo'
            ]);
        });
    }
};