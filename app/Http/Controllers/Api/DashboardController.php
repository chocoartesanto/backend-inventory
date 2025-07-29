<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController
{
    protected $stockService;
    protected $purchaseService;

    public function __construct(StockService $stockService, PurchaseService $purchaseService)
    {
        $this->stockService = $stockService;
        $this->purchaseService = $purchaseService;
    }

    /**
     * Obtiene un resumen consolidado para el dashboard principal
     * EXACTAMENTE igual que la función get_dashboard_summary() de FastAPI
     * 
     * @return array Dict con información resumida de ventas, productos, stock y domicilios
     */
    public function getDashboardSummary(): array
    {
        try {
            // 1. Obtener resumen de ventas del día - igual que FastAPI
            $salesSummary = $this->purchaseService->getSalesSummary('today');
            
            // 2. Obtener resumen de stock de productos - igual que FastAPI
            $stockSummary = [
                'productos_disponibles' => 0,
                'total_productos' => 0,
                'productos_sin_stock' => 0
            ];
            $stockData = StockService::calculateProductStock();
            
            if (!empty($stockData)) {
                $stockSummary['productos_disponibles'] = count(array_filter($stockData, function($item) {
                    return $item['stock_disponible'] > 0;
                }));
                $stockSummary['total_productos'] = count($stockData);
                $stockSummary['productos_sin_stock'] = count(array_filter($stockData, function($item) {
                    return $item['stock_disponible'] <= 0;
                }));
            }
            
            // 3. Obtener productos con stock bajo - igual que FastAPI
            $lowStock = array_filter(StockService::calculateProductStock(), function($item) {
                return $item['stock_disponible'] <= 3; // Umbral de stock bajo
            });
            
            // 4. Obtener ventas recientes (últimas 10) - igual que FastAPI
            $today = Carbon::today();
            $weekAgo = $today->copy()->subDays(7);
            $recentSales = $this->purchaseService->getPurchasesByDateRange($weekAgo, $today);
            
            // Limitar a las 10 más recientes y formatear para el dashboard - igual que FastAPI
            $recentSales = array_slice($recentSales, 0, 10);
            $formattedRecentSales = [];
            
            foreach ($recentSales as $sale) {
                try {
                    // Obtener detalles de la venta para mostrar productos - igual que FastAPI
                    $saleDetails = $this->purchaseService->getPurchaseByInvoice($sale['invoice_number']);
                    
                    if ($saleDetails && isset($saleDetails['products']) && !empty($saleDetails['products'])) {
                        // Tomar solo el primer producto para la vista resumida - igual que FastAPI
                        $firstProduct = $saleDetails['products'][0];
                        
                        $formattedSale = [
                            'client_name' => $sale['client_name'] ?? 'Cliente',
                            'product_name' => $firstProduct['product_name'] ?? 'Producto',
                            'product_variant' => $firstProduct['product_variant'] ?? '',
                            'total_amount' => floatval($sale['total_amount'] ?? 0),
                            'has_delivery' => $sale['has_delivery'] ?? false,
                            'delivery_person' => ($sale['has_delivery'] ?? false) ? ($sale['delivery_person'] ?? '') : '',
                            'invoice_date' => $sale['invoice_date'] ?? '',
                            'invoice_time' => $sale['invoice_time'] ?? '',
                            'invoice_number' => $sale['invoice_number'] ?? ''
                        ];
                        
                        $formattedRecentSales[] = $formattedSale;
                    }
                } catch (\Exception $e) {
                    // Igual que en FastAPI: print(f"Error al procesar venta reciente: {str(e)}")
                    Log::error("Error al procesar venta reciente: " . $e->getMessage());
                    continue;
                }
            }
            
            // Manejar posibles valores None en los datos - igual que FastAPI
            $summaryData = $salesSummary['summary'] ?? [];
            
            // Construir respuesta consolidada con manejo seguro de valores None - EXACTAMENTE igual que FastAPI
            $dashboardData = [
                // Ventas del día
                'ventas_hoy' => floatval($summaryData['total_revenue'] ?? 0),
                
                // Productos vendibles (con stock disponible)
                'productos_vendibles' => $stockSummary['productos_disponibles'] ?? 0,
                
                // Productos con stock bajo
                'stock_bajo' => count($lowStock),
                'productos_stock_bajo' => $lowStock ?: [],
                
                // Domicilios del día
                'domicilios' => intval($summaryData['deliveries_count'] ?? 0),
                
                // Ventas recientes
                'ventas_recientes' => $formattedRecentSales,
                
                // Información adicional útil
                'total_productos' => $stockSummary['total_productos'] ?? 0,
                'productos_sin_stock' => $stockSummary['productos_sin_stock'] ?? 0,
                'ventas_cantidad' => intval($summaryData['total_purchases'] ?? 0),
                
                // Métodos de pago
                'metodos_pago' => $salesSummary['payment_methods'] ?? [],
                
                // Productos más vendidos
                'productos_top' => $salesSummary['top_products'] ?? []
            ];
            
            return $dashboardData;
            
        } catch (\Exception $e) {
            // Registrar el error y devolver un diccionario con información de error - igual que FastAPI
            // print(f"Error obteniendo resumen del dashboard: {str(e)}")
            Log::error("Error obteniendo resumen del dashboard: " . $e->getMessage());
            
            return [
                'error' => $e->getMessage(),
                'ventas_hoy' => 0,
                'productos_vendibles' => 0,
                'stock_bajo' => 0,
                'domicilios' => 0,
                'ventas_recientes' => []
            ];
        }
    }
}