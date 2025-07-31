<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class StatisticsService
{
    /**
     * Obtiene estadísticas generales de la aplicación usando Eloquent
     */
    public function getAppStatistics(): array
    {
        try {
            $statistics = [];

            // 1. Total de productos activos
            $statistics['total_products'] = DB::table('products')->count();

            // 2. Total de ventas y ingresos usando el modelo
            $salesData = Purchase::notCancelled()
                ->selectRaw('COUNT(*) as total_sales, SUM(total_amount) as monthly_revenue')
                ->first();

            $statistics['monthly_sales'] = [
                'count' => $salesData->total_sales ?? 0,
                'revenue' => (float) ($salesData->monthly_revenue ?? 0.0)
            ];

            // 3. Ventas por día (últimos 30 días)
            $weeklySales = Purchase::selectRaw('invoice_date as sale_date, COUNT(*) as sales_count, SUM(total_amount) as daily_revenue')
                ->groupBy('invoice_date')
                ->limit(30)
                ->get()
                ->map(function ($row) {
                    return [
                        'date' => $row->sale_date,
                        'count' => $row->sales_count,
                        'revenue' => (float) ($row->daily_revenue ?? 0.0)
                    ];
                })
                ->toArray();

            $statistics['weekly_sales'] = $weeklySales;

            // 4. Productos más vendidos usando relaciones
            $topProducts = PurchaseDetail::with('purchase')
                ->whereHas('purchase', function ($query) {
                    $query->notCancelled();
                })
                ->topSelling(5)
                ->get()
                ->map(function ($row) {
                    return [
                        'product_name' => $row->product_name,
                        'product_variant' => $row->product_variant ?? '',
                        'quantity_sold' => $row->total_quantity,
                        'revenue' => (float) ($row->total_revenue ?? 0.0),
                        'numero_ordenes' => $row->number_of_orders
                    ];
                })
                ->toArray();

            $statistics['top_products'] = $topProducts;

            return $statistics;

        } catch (Exception $e) {
            \Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            return [
                'error' => 'Error al obtener estadísticas: ' . $e->getMessage(),
                'total_products' => 0,
                'monthly_sales' => ['count' => 0, 'revenue' => 0],
                'weekly_sales' => [],
                'top_products' => []
            ];
        }
    }

    /**
     * Obtiene estadísticas por rango de tiempo
     */
    public function getSalesByTimeRange(string $timeRange): array
    {
        try {
            switch ($timeRange) {
                case 'day':
                    return $this->getDailyStatistics();
                case 'week':
                    return $this->getWeeklyStatistics();
                case 'month':
                    return $this->getMonthlyStatistics();
                case 'year':
                    return $this->getYearlyStatistics();
                default:
                    throw new Exception('Rango de tiempo inválido');
            }
        } catch (Exception $e) {
            \Log::error('Error obteniendo estadísticas por tiempo: ' . $e->getMessage());
            return [
                'error' => 'Error al obtener estadísticas por tiempo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Estadísticas diarias (últimos 30 días)
     */
    private function getDailyStatistics(): array
    {
        $today = Carbon::now();
        $monthAgo = $today->copy()->subDays(30);

        $dailySales = Purchase::notCancelled()
            ->byDateRange($monthAgo->format('Y-m-d'), $today->format('Y-m-d'))
            ->selectRaw('
                invoice_date as fecha,
                COUNT(*) as total_ventas,
                SUM(total_amount) as ingresos_dia,
                AVG(total_amount) as ticket_promedio
            ')
            ->groupBy('invoice_date')
            ->orderByDesc(DB::raw("STR_TO_DATE(invoice_date, '%d/%m/%Y')"))
            ->get()
            ->map(function ($row) {
                return [
                    'fecha' => $row->fecha,
                    'total_ventas' => $row->total_ventas,
                    'ingresos' => (float) ($row->ingresos_dia ?? 0.0),
                    'ticket_promedio' => (float) ($row->ticket_promedio ?? 0.0)
                ];
            })
            ->toArray();

        return ['ventas_por_dia' => $dailySales];
    }

    /**
     * Estadísticas semanales (últimas 12 semanas)
     */
    private function getWeeklyStatistics(): array
    {
        $weeklySales = Purchase::notCancelled()
            ->selectRaw('
                YEARWEEK(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as semana,
                MIN(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as inicio_semana,
                MAX(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as fin_semana,
                COUNT(*) as ventas_semana,
                SUM(total_amount) as ingresos_semana
            ')
            ->groupBy(DB::raw('YEARWEEK(STR_TO_DATE(invoice_date, "%d/%m/%Y"))'))
            ->orderByDesc('semana')
            ->limit(12)
            ->get()
            ->map(function ($row) {
                return [
                    'semana' => $row->semana,
                    'inicio_semana' => $row->inicio_semana ? Carbon::parse($row->inicio_semana)->format('d/m/Y') : '',
                    'fin_semana' => $row->fin_semana ? Carbon::parse($row->fin_semana)->format('d/m/Y') : '',
                    'total_ventas' => $row->ventas_semana,
                    'ingresos' => (float) ($row->ingresos_semana ?? 0.0)
                ];
            })
            ->toArray();

        return ['ventas_por_semana' => $weeklySales];
    }

    /**
     * Estadísticas mensuales (últimos 12 meses)
     */
    private function getMonthlyStatistics(): array
    {
        $monthlySales = Purchase::notCancelled()
            ->selectRaw('
                YEAR(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as año,
                MONTH(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as mes,
                COUNT(*) as total_ventas,
                SUM(total_amount) as ingresos_mes,
                SUM(delivery_fee) as ingresos_domicilio
            ')
            ->groupBy(DB::raw('YEAR(STR_TO_DATE(invoice_date, "%d/%m/%Y"))'))
            ->groupBy(DB::raw('MONTH(STR_TO_DATE(invoice_date, "%d/%m/%Y"))'))
            ->orderByDesc('año')
            ->orderByDesc('mes')
            ->limit(12)
            ->get()
            ->map(function ($row) {
                return [
                    'año' => $row->año,
                    'mes' => $row->mes,
                    'total_ventas' => $row->total_ventas,
                    'ingresos' => (float) ($row->ingresos_mes ?? 0.0),
                    'ingresos_domicilio' => (float) ($row->ingresos_domicilio ?? 0.0)
                ];
            })
            ->toArray();

        return ['ventas_por_mes' => $monthlySales];
    }

    /**
     * Estadísticas anuales
     */
    private function getYearlyStatistics(): array
    {
        $yearlySales = Purchase::notCancelled()
            ->selectRaw('
                YEAR(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as año,
                COUNT(*) as total_ventas,
                SUM(total_amount) as ingresos_año,
                AVG(total_amount) as ticket_promedio,
                SUM(delivery_fee) as ingresos_domicilio
            ')
            ->groupBy(DB::raw('YEAR(STR_TO_DATE(invoice_date, "%d/%m/%Y"))'))
            ->orderByDesc('año')
            ->get()
            ->map(function ($row) {
                return [
                    'año' => $row->año,
                    'total_ventas' => $row->total_ventas,
                    'ingresos' => (float) ($row->ingresos_año ?? 0.0),
                    'ticket_promedio' => (float) ($row->ticket_promedio ?? 0.0),
                    'ingresos_domicilio' => (float) ($row->ingresos_domicilio ?? 0.0)
                ];
            })
            ->toArray();

        return ['ventas_por_año' => $yearlySales];
    }

    /**
     * Obtiene productos más vendidos con filtro de fechas
     */
    public function getTopProductsStatistics(?string $startDate, ?string $endDate): array
    {
        try {
            // Si no se especifican fechas, usar todo el histórico
            if (!$startDate) {
                $startDate = '2020-01-01';
            }
            if (!$endDate) {
                $endDate = Carbon::now()->format('Y-m-d');
            }

            $topProducts = PurchaseDetail::with('purchase')
                ->whereHas('purchase', function ($query) use ($startDate, $endDate) {
                    $query->notCancelled()
                          ->byDateRange($startDate, $endDate);
                })
                ->selectRaw('
                    product_name,
                    product_variant,
                    SUM(quantity) as total_vendido,
                    SUM(subtotal) as ingresos_producto,
                    COUNT(DISTINCT purchase_id) as numero_ordenes
                ')
                ->groupBy('product_name', 'product_variant')
                ->orderByDesc('total_vendido')
                ->limit(20)
                ->get()
                ->map(function ($row) {
                    return [
                        'producto' => $row->product_name,
                        'variante' => $row->product_variant ?? '',
                        'cantidad_vendida' => $row->total_vendido,
                        'ingresos' => (float) ($row->ingresos_producto ?? 0.0),
                        'numero_ordenes' => $row->numero_ordenes
                    ];
                })
                ->toArray();

            return [
                'productos_mas_vendidos' => $topProducts,
                'periodo' => [
                    'fecha_inicio' => Carbon::parse($startDate)->format('d/m/Y'),
                    'fecha_fin' => Carbon::parse($endDate)->format('d/m/Y')
                ]
            ];

        } catch (Exception $e) {
            \Log::error('Error obteniendo estadísticas de productos: ' . $e->getMessage());
            return [
                'error' => 'Error al obtener estadísticas de productos: ' . $e->getMessage(),
                'productos_mas_vendidos' => []
            ];
        }
    }

    /**
     * Obtiene métricas de entrega y método de pago
     */
    public function getDeliveryStatistics(): array
    {
        try {
            // Análisis de domicilios vs venta directa
            $deliveryStats = Purchase::notCancelled()
                ->selectRaw('
                    has_delivery,
                    COUNT(*) as total_ordenes,
                    SUM(total_amount) as ingresos_total,
                    AVG(total_amount) as ticket_promedio,
                    SUM(delivery_fee) as total_domicilios
                ')
                ->groupBy('has_delivery')
                ->get()
                ->map(function ($row) {
                    return [
                        'tipo' => $row->has_delivery ? 'Domicilio' : 'Venta directa',
                        'total_ordenes' => $row->total_ordenes,
                        'ingresos_total' => (float) ($row->ingresos_total ?? 0.0),
                        'ticket_promedio' => (float) ($row->ticket_promedio ?? 0.0),
                        'total_domicilios' => (float) ($row->total_domicilios ?? 0.0)
                    ];
                })
                ->toArray();

            // Análisis por método de pago
            $paymentStats = Purchase::notCancelled()
                ->selectRaw('
                    payment_method,
                    COUNT(*) as cantidad_transacciones,
                    SUM(total_amount) as valor_total,
                    AVG(total_amount) as valor_promedio
                ')
                ->groupBy('payment_method')
                ->get()
                ->map(function ($row) {
                    return [
                        'metodo_pago' => $row->payment_method,
                        'cantidad_transacciones' => $row->cantidad_transacciones,
                        'valor_total' => (float) ($row->valor_total ?? 0.0),
                        'valor_promedio' => (float) ($row->valor_promedio ?? 0.0)
                    ];
                })
                ->toArray();

            return [
                'domicilios_vs_directa' => $deliveryStats,
                'metodos_pago' => $paymentStats
            ];

        } catch (Exception $e) {
            \Log::error('Error obteniendo métricas de entrega: ' . $e->getMessage());
            return [
                'error' => 'Error al obtener métricas de entrega: ' . $e->getMessage(),
                'domicilios_vs_directa' => [],
                'metodos_pago' => []
            ];
        }
    }

    /**
     * Obtiene resumen de ventas por fecha
     */
    public function getSalesSummary(): array
    {
        try {
            $salesSummary = Purchase::notCancelled()
                ->selectRaw('
                    invoice_date AS fecha_texto,
                    SUM(total_amount) AS total
                ')
                ->groupBy('fecha_texto')
                ->orderBy('fecha_texto')
                ->get()
                ->map(function ($row) {
                    return [
                        'fecha' => $row->fecha_texto,
                        'total' => (float) ($row->total ?? 0.0)
                    ];
                })
                ->toArray();

            return ['sales_summary' => $salesSummary];

        } catch (Exception $e) {
            \Log::error('Error en la consulta de resumen: ' . $e->getMessage());
            
            // Consulta de respaldo
            try {
                $totalGeneral = Purchase::notCancelled()->sum('total_amount');

                return [
                    'message' => 'No se pudieron agrupar las ventas por fecha debido a problemas con los datos',
                    'total_general' => (float) $totalGeneral
                ];

            } catch (Exception $fallbackError) {
                \Log::error('Error en consulta de respaldo: ' . $fallbackError->getMessage());
                return [
                    'error' => 'Error al obtener resumen de ventas por fecha: ' . $e->getMessage(),
                    'sales_summary' => []
                ];
            }
        }
    }
}