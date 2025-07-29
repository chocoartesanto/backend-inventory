<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Insumo;
use App\Models\ProductRecipe;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockServiceEloquent
{
    /**
     * Versión mejorada usando Eloquent ORM
     * Mantiene la misma lógica que FastAPI pero aprovecha las relaciones de Laravel
     */
    
    /**
     * Obtiene un resumen general del estado del stock usando Eloquent
     * 
     * @return array
     */
    public function getStockSummary(): array
    {
        try {
            // Usando Eloquent con las relaciones definidas
            $products = Product::where('is_active', true)
                ->with(['recipes.insumo'])
                ->get();

            $totalProductos = $products->count();
            $productosSinStock = 0;
            $productosStockBajo = 0;
            $productosDisponibles = 0;

            foreach ($products as $product) {
                $stockDisponible = $this->calculateProductStock($product);
                
                if ($stockDisponible == 0) {
                    $productosSinStock++;
                } elseif ($stockDisponible <= 5) {
                    $productosStockBajo++;
                } else {
                    $productosDisponibles++;
                }
            }

            return [
                'total_productos' => $totalProductos,
                'productos_sin_stock' => $productosSinStock,
                'productos_stock_bajo' => $productosStockBajo,
                'productos_disponibles' => $productosDisponibles,
                'fecha_actualizacion' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo resumen de stock: " . $e->getMessage());
            
            return [
                'total_productos' => 0,
                'productos_sin_stock' => 0,
                'productos_stock_bajo' => 0,
                'productos_disponibles' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calcula el stock disponible de un producto basado en sus insumos
     * Mantiene exactamente la misma lógica que FastAPI
     * 
     * @param Product $product
     * @return int
     */
    private function calculateProductStock(Product $product): int
    {
        if ($product->recipes->isEmpty()) {
            return 0;
        }

        $minStock = PHP_INT_MAX;

        foreach ($product->recipes as $recipe) {
            $insumo = $recipe->insumo;
            
            if (!$insumo) {
                continue;
            }

            $stockDisponibleInsumo = $insumo->cantidad_unitaria - $insumo->cantidad_utilizada;
            
            // Usar la misma lógica que FastAPI
            $cantidadPorProducto = $insumo->cantidad_por_producto > 0 
                ? $insumo->cantidad_por_producto 
                : $recipe->cantidad;

            if ($cantidadPorProducto > 0) {
                $stockPosible = floor($stockDisponibleInsumo / $cantidadPorProducto);
                $minStock = min($minStock, $stockPosible);
            }
        }

        return $minStock == PHP_INT_MAX ? 0 : max(0, (int) $minStock);
    }

    /**
     * Obtiene productos con stock bajo usando Eloquent
     * 
     * @param int $minStockThreshold
     * @return array
     */
    public function getLowStockProducts(int $minStockThreshold = 5): array
    {
        try {
            $products = Product::where('is_active', true)
                ->with(['recipes.insumo', 'category'])
                ->get();

            $lowStockProducts = [];

            foreach ($products as $product) {
                $stockDisponible = $this->calculateProductStock($product);
                
                if ($stockDisponible <= $minStockThreshold) {
                    $lowStockProducts[] = [
                        'producto_id' => $product->id,
                        'nombre_producto' => $product->nombre_producto,
                        'variante' => $product->variante ?? '',
                        'precio' => floatval($product->price),
                        'categoria_nombre' => $product->category->nombre_categoria ?? 'Sin categoría',
                        'stock_disponible' => $stockDisponible,
                        'min_stock' => $product->min_stock,
                        'estado' => $stockDisponible == 0 ? 'crítico' : 'bajo'
                    ];
                }
            }

            // Ordenar igual que en FastAPI
            usort($lowStockProducts, function($a, $b) {
                if ($a['stock_disponible'] == $b['stock_disponible']) {
                    return strcmp($a['nombre_producto'], $b['nombre_producto']);
                }
                return $a['stock_disponible'] - $b['stock_disponible'];
            });

            return $lowStockProducts;
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo productos con stock bajo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el stock de un producto específico
     * 
     * @param int $productId
     * @return int
     */
    public function getProductStock(int $productId): int
    {
        try {
            $product = Product::where('id', $productId)
                ->where('is_active', true)
                ->with(['recipes.insumo'])
                ->first();

            if (!$product) {
                return 0;
            }

            return $this->calculateProductStock($product);
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo stock del producto {$productId}: " . $e->getMessage());
            return 0;
        }
    }
}