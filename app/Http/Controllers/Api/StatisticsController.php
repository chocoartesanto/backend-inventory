<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    protected $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Obtiene estadísticas generales de la aplicación
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $statistics = $this->statisticsService->getAppStatistics();
            
            if (isset($statistics['error']) && count($statistics) === 1) {
                return response()->json([
                    'message' => $statistics['error']
                ], 500);
            }
            
            return response()->json($statistics);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de ventas por tiempo
     * 
     * @param string $timeRange
     * @return JsonResponse
     */
    public function getSalesByTime(string $timeRange): JsonResponse
    {
        try {
            $validRanges = ['day', 'week', 'month', 'year'];
            
            if (!in_array($timeRange, $validRanges)) {
                return response()->json([
                    'message' => 'Rango de tiempo inválido. Debe ser uno de: ' . implode(', ', $validRanges)
                ], 400);
            }
            
            $statistics = $this->statisticsService->getSalesByTime($timeRange);
            
            if (isset($statistics['error']) && count($statistics) === 1) {
                return response()->json([
                    'message' => $statistics['error']
                ], 500);
            }
            
            return response()->json($statistics);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener estadísticas por tiempo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de productos más vendidos
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTopProducts(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            // Validar fechas si se proporcionan
            if ($startDate) {
                try {
                    $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->toDateString();
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Formato de fecha inicial inválido. Use YYYY-MM-DD'
                    ], 400);
                }
            }
            
            if ($endDate) {
                try {
                    $endDate = Carbon::createFromFormat('Y-m-d', $endDate)->toDateString();
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Formato de fecha final inválido. Use YYYY-MM-DD'
                    ], 400);
                }
            }
            
            $statistics = $this->statisticsService->getTopProducts($startDate, $endDate);
            
            if (isset($statistics['error']) && count($statistics) === 1) {
                return response()->json([
                    'message' => $statistics['error']
                ], 500);
            }
            
            return response()->json($statistics);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener estadísticas de productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene métricas de entrega y servicio
     * 
     * @return JsonResponse
     */
    public function getDeliveryMetrics(): JsonResponse
    {
        try {
            $statistics = $this->statisticsService->getDeliveryMetrics();
            
            if (isset($statistics['error']) && count($statistics) === 1) {
                return response()->json([
                    'message' => $statistics['error']
                ], 500);
            }
            
            return response()->json($statistics);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener métricas de entrega: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene resumen de ventas por fecha
     * 
     * @return JsonResponse
     */
    public function getSalesSummaryByDate(): JsonResponse
    {
        try {
            $summary = $this->statisticsService->getSalesSummaryByDate();
            return response()->json($summary);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener resumen de ventas: ' . $e->getMessage()
            ], 500);
        }
    }
}