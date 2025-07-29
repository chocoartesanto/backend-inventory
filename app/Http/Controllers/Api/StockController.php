<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    /**
     * Obtiene el stock disponible de todos los productos basándose en los insumos disponibles.
     * 
     * Calcula cuántas unidades de cada producto se pueden producir con los insumos actuales.
     * 
     * @return JsonResponse
     */
    public function getProductStock(): JsonResponse
    {
        try {
            $stockData = StockService::calculateProductStock();
            
            return response()->json([
                'total_productos' => count($stockData),
                'productos' => $stockData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al calcular el stock',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}