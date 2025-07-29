<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QueryPurchaseController extends Controller
{
    protected $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    /**
     * Lista todas las compras
     * Equivalente al endpoint GET api/v1/services/purchases de FastAPI
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            
            $purchases = Purchase::with(['details', 'seller'])
                ->orderBy('invoice_date', 'desc')
                ->orderBy('invoice_time', 'desc')
                ->paginate($perPage);

            return response()->json($purchases);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener compras: ' . $e->getMessage()
            ], 500);
        }
    }

 

    /**
     * Obtiene compras por cliente
     * Equivalente al endpoint GET api/v1/services/purchases/client/{client_name} de FastAPI
     * 
     * @param string $clientName
     * @return JsonResponse
     */
    public function getByClient(string $clientName): JsonResponse
    {
        try {
            $purchases = $this->purchaseService->getPurchasesByClient($clientName);
            
            return response()->json([
                'client_name' => $clientName,
                'purchases' => $purchases,
                'total_purchases' => count($purchases)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener compras del cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de compras
     * Equivalente al endpoint GET api/v1/services/purchases/statistics de FastAPI
     * 
     * @return JsonResponse
     */
    public function getStatistics(): JsonResponse
    {
        try {
            // Estadísticas generales
            $totalPurchases = Purchase::count();
            $totalRevenue = Purchase::sum('total_amount');
            $averageTicket = Purchase::avg('total_amount');
            $totalDeliveries = Purchase::where('has_delivery', true)->count();
            
            // Top clientes
            $topClients = Purchase::select('client_name')
                ->selectRaw('COUNT(*) as total_purchases')
                ->selectRaw('SUM(total_amount) as total_spent')
                ->groupBy('client_name')
                ->orderByDesc('total_spent')
                ->limit(10)
                ->get();

            // Ventas por método de pago
            $paymentMethods = Purchase::select('payment_method')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(total_amount) as total')
                ->groupBy('payment_method')
                ->orderByDesc('count')
                ->get();

            return response()->json([
                'general_stats' => [
                    'total_purchases' => $totalPurchases,
                    'total_revenue' => floatval($totalRevenue),
                    'average_ticket' => floatval($averageTicket),
                    'total_deliveries' => $totalDeliveries
                ],
                'top_clients' => $topClients,
                'payment_methods' => $paymentMethods
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene resumen de ventas por período
     * Equivalente al endpoint GET api/v1/services/purchases/summary/{period} de FastAPI
     * 
     * @param string $period
     * @return JsonResponse
     */
    public function getSalesSummary(string $period): JsonResponse
    {
        try {
            $summary = $this->purchaseService->getSalesSummary($period);
            
            return response()->json($summary);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener resumen de ventas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene una compra específica por ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $purchase = Purchase::with(['details', 'seller', 'deliveryPerson'])
                ->findOrFail($id);

            return response()->json($purchase);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Compra no encontrada'
            ], 404);
        }
    }




}