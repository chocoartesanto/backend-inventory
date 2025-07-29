<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Calcula cuántas unidades de cada producto se pueden hacer con los insumos disponibles.
     * 
     * @return array Lista de productos con su stock disponible
     */
    public static function calculateProductStock(): array
    {
        try {
            Log::info('DEBUG - Ejecutando cálculo de stock de productos');
            
            $results = DB::select("
                            SELECT
                                p.id AS producto_id,
                                p.nombre_producto,
                                p.variant,
                                p.precio AS precio,
                                c.nombre_categoria AS categoria_nombre,
                                COALESCE(FLOOR(MIN(
                                    (i.cantidad_unitaria - i.cantidad_utilizada) / 
                                    CASE 
                                        WHEN i.cantidad_por_producto > 0 THEN i.cantidad_por_producto
                                        WHEN pr.cantidad IS NOT NULL AND pr.cantidad > 0 THEN pr.cantidad
                                        ELSE 1
                                    END
                                )), 0) AS stock_disponible
                            FROM products p
                            LEFT JOIN categories c ON p.categoria_id = c.id
                            LEFT JOIN product_recipes pr ON p.id = pr.product_id
                            LEFT JOIN insumos i ON pr.insumo_id = i.id
                            WHERE p.is_active = TRUE
                            GROUP BY p.id, p.nombre_producto, p.variant, p.precio, c.nombre_categoria
                            ORDER BY p.nombre_producto;

            ");

            // Transformar resultados
            $stockData = [];
            foreach ($results as $row) {
                $stockInfo = [
                    'producto_id' => $row->producto_id,
                    'nombre_producto' => $row->nombre_producto,
                    'variant' => $row->variant ?? '',
                    'precio' => (float) $row->precio,
                    'categoria_nombre' => $row->categoria_nombre ?? 'Sin categoría',
                    'stock_disponible' => (int) ($row->stock_disponible ?? 0),
                    'tipo' => 'producto'
                ];
                
                Log::info("DEBUG - Producto: {$stockInfo['nombre_producto']} - Stock disponible: {$stockInfo['stock_disponible']}");
                $stockData[] = $stockInfo;
            }

            return $stockData;

        } catch (\Exception $e) {
            Log::error("Error calculando stock de productos: " . $e->getMessage());
            Log::info("DEBUG - Error calculando stock: " . $e->getMessage());
            return [];
        }
    }
}