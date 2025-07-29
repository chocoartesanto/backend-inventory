<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsService
{
    /**
     * Obtiene estadísticas generales de la aplicación
     * 
     * @return array
     */
    public function getAppStatistics(): array
    {
        try {
            $statistics = [];

            // 1. Total de productos activos
            $totalProducts = DB::table('products')->count();
            $statistics['total_products'] = $totalProducts;

            // 2. Total de ventas en el mes actual
            $salesData = DB::table('purchases')
                ->selectRaw('COUNT(*) as total_sales, SUM(total_amount) as monthly_revenue')
                ->first();

            $statistics['monthly_sales'] = [
                'count' => $salesData->total_sales ?? 0,
                'revenue' => (float)($salesData->monthly_revenue ?? 0.0)
            ];

            // 3. Ventas por día (simplificado - últimos 30 registros)
            $weeklySales = DB::table('purchases')
                ->selectRaw('invoice_date as sale_date, COUNT(*) as sales_count, SUM(total_amount) as daily_revenue')
                ->groupBy('invoice_date')
                ->limit(30)
                ->get()
                ->map(function ($row) {
                    return [
                        'date' => $row->sale_date,
                        'count' => $row->sales_count,
                        'revenue' => (float)($row->daily_revenue ?? 0.0)
                    ];
                })
                ->toArray();

            $statistics['weekly_sales'] = $weeklySales;

            // 4. Productos más vendidos (simplificado)
            $topProducts = DB::table('purchase_details as pd')
                ->join('purchases as p', 'pd.purchase_id', '=', 'p.id')
                ->selectRaw('
                    pd.product_name,
                    pd.product_variant,
                    SUM(pd.quantity) as total_quantity,
                    SUM(pd.subtotal) as total_revenue,
                    COUNT(DISTINCT p.id) as numero_ordenes
                ')
                ->groupBy('pd.product_name', 'pd.product_variant')
                ->orderByDesc('total_quantity')
                ->limit(5)
                ->get()
                ->map(function ($row) {
                    return [
                        'product_name' => $row->product_name,
                        'product_variant' => $row->product_variant ?? '',
                        'quantity_sold' => $row->total_quantity,
                        'revenue' => (float)($row->total_revenue ?? 0.0),
                        'numero_ordenes' => $row->numero_ordenes
                    ];
                })
                ->toArray();

            $statistics['top_products'] = $topProducts;

            return $statistics;

        } catch (\Exception $e) {
            \Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
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
     * Obtiene estadísticas de ventas por tiempo
     * 
     * @param string $timeRange
     * @return array
     */
    public function getSalesByTime(string $timeRange = 'day'): array
    {
        try {
            $statistics = [];

            switch ($timeRange) {
                case 'day':
                    $today = Carbon::today();
                    $monthAgo = $today->copy()->subDays(30);

                    $dailySales = DB::table('purchases')
                        ->selectRaw('
                            invoice_date as fecha,
                            COUNT(*) as total_ventas,
                            SUM(total_amount) as ingresos_dia,
                            AVG(total_amount) as ticket_promedio
                        ')
                        ->where('is_cancelled', 0)
                        ->whereRaw("STR_TO_DATE(invoice_date, '%d/%m/%Y') BETWEEN ? AND ?", [
                            $monthAgo->format('Y-m-d'),
                            $today->format('Y-m-d')
                        ])
                        ->groupBy('invoice_date')
                        ->orderByRaw("STR_TO_DATE(invoice_date, '%d/%m/%Y') DESC")
                        ->get()
                        ->map(function ($row) {
                            return [
                                'fecha' => $row->fecha,
                                'total_ventas' => $row->total_ventas,
                                'ingresos' => (float)($row->ingresos_dia ?? 0.0),
                                'ticket_promedio' => (float)($row->ticket_promedio ?? 0.0)
                            ];
                        })
                        ->toArray();

                    $statistics['ventas_por_dia'] = $dailySales;
                    break;

                case 'month':
                    $monthlySales = DB::table('purchases')
                        ->selectRaw('
                            YEAR(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as año,
                            MONTH(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as mes,
                            COUNT(*) as total_ventas,
                            SUM(total_amount) as ingresos_mes,
                            SUM(delivery_fee) as ingresos_domicilio
                        ')
                        ->where('is_cancelled', 0)
                        ->groupByRaw('YEAR(STR_TO_DATE(invoice_date, "%d/%m/%Y")), MONTH(STR_TO_DATE(invoice_date, "%d/%m/%Y"))')
                        ->orderByRaw('año DESC, mes DESC')
                        ->limit(12)
                        ->get()
                        ->map(function ($row) {
                            return [
                                'año' => $row->año,
                                'mes' => $row->mes,
                                'total_ventas' => $row->total_ventas,
                                'ingresos' => (float)($row->ingresos_mes ?? 0.0),
                                'ingresos_domicilio' => (float)($row->ingresos_domicilio ?? 0.0)
                            ];
                        })
                        ->toArray();

                    $statistics['ventas_por_mes'] = $monthlySales;
                    break;

                case 'week':
                    $weeklySales = DB::table('purchases')
                        ->selectRaw('
                            YEARWEEK(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as semana,
                            MIN(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as inicio_semana,
                            MAX(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as fin_semana,
                            COUNT(*) as ventas_semana,
                            SUM(total_amount) as ingresos_semana
                        ')
                        ->where('is_cancelled', 0)
                        ->groupByRaw('YEARWEEK(STR_TO_DATE(invoice_date, "%d/%m/%Y"))')
                        ->orderByRaw('semana DESC')
                        ->limit(12)
                        ->get()
                        ->map(function ($row) {
                            return [
                                'semana' => $row->semana,
                                'inicio_semana' => $row->inicio_semana ? Carbon::parse($row->inicio_semana)->format('d/m/Y') : '',
                                'fin_semana' => $row->fin_semana ? Carbon::parse($row->fin_semana)->format('d/m/Y') : '',
                                'total_ventas' => $row->ventas_semana,
                                'ingresos' => (float)($row->ingresos_semana ?? 0.0)
                            ];
                        })
                        ->toArray();

                    $statistics['ventas_por_semana'] = $weeklySales;
                    break;

                case 'year':
                    $yearlySales = DB::table('purchases')
                        ->selectRaw('
                            YEAR(STR_TO_DATE(invoice_date, "%d/%m/%Y")) as año,
                            COUNT(*) as total_ventas,
                            SUM(total_amount) as ingresos_año,
                            AVG(total_amount) as ticket_promedio,
                            SUM(delivery_fee) as ingresos_domicilio
                        ')
                        ->where('is_cancelled', 0)
                        ->groupByRaw('YEAR(STR_TO_DATE(invoice_date, "%d/%m/%Y"))')
                        ->orderByRaw('año DESC')
                        ->get()
                        ->map(function ($row) {
                            return [
                                'año' => $row->año,
                                'total_ventas' => $row->total_ventas,
                                'ingresos' => (float)($row->ingresos_año ?? 0.0),
                                'ticket_promedio' => (float)($row->ticket_promedio ?? 0.0),
                                'ingresos_domicilio' => (float)($row->ingresos_domicilio ?? 0.0)
                            ];
                        })
                        ->toArray();

                    $statistics['ventas_por_año'] = $yearlySales;
                    break;
            }

            return $statistics;

        } catch (\Exception $e) {
            \Log::error('Error obteniendo estadísticas por tiempo: ' . $e->getMessage());
            return [
                'error' => 'Error al obtener estadísticas por tiempo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene estadísticas de productos más vendidos
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopProducts(?string $startDate = null, ?string $endDate = null): array
    {
        try {
            // Si no se especifican fechas, usar todo el histórico
            if (!$startDate) {
                $startDate = '2020-01-01';
            }
            if (!$endDate) {
                $endDate = Carbon::today()->format('Y-m-d');
            }

            $topProducts = DB::table('purchase_details as pd')
                ->join('purchases as p', 'pd.purchase_id', '=', 'p.id')
                ->selectRaw('
                    pd.product_name,
                    pd.product_variant,
                    SUM(pd.quantity) as total_vendido,
                    SUM(pd.subtotal) as ingresos_producto,
                    COUNT(DISTINCT p.id) as numero_ordenes
                ')
                ->where('p.is_cancelled', 0)
                ->whereRaw("STR_TO_DATE(p.invoice_date, '%d/%m/%Y') BETWEEN STR_TO_DATE(?, '%Y-%m-%d') AND STR_TO_DATE(?, '%Y-%m-%d')", 
                    [$startDate, $endDate])
                ->groupBy('pd.product_name', 'pd.product_variant')
                ->orderByDesc('total_vendido')
                ->limit(20)
                ->get()
                ->map(function ($row) {
                    return [
                        'producto' => $row->product_name,
                        'variante' => $row->product_variant ?? '',
                        'cantidad_vendida' => $row->total_vendido,
                        'ingresos' => (float)($row->ingresos_producto ?? 0.0),
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

        } catch (\Exception $e) {
            \Log::error('Error obteniendo estadísticas de productos: ' . $e->getMessage());
            return [
                'error' => 'Error al obtener estadísticas de productos: ' . $e->getMessage(),
                'productos_mas_vendidos' => []
            ];
        }
    }

    /**
     * Obtiene métricas de entrega y servicio
     * 
     * @return array
     */
    public function getDeliveryMetrics(): array
    {
        try {
            $statistics = [];

            // Análisis de domicilios vs venta directa
            $deliveryStats = DB::table('purchases')
                ->selectRaw('
                    has_delivery,
                    COUNT(*) as total_ordenes,
                    SUM(total_amount) as ingresos_total,
                    AVG(total_amount) as ticket_promedio,
                    SUM(delivery_fee) as total_domicilios
                ')
                ->where('is_cancelled', 0)
                ->groupBy('has_delivery')
                ->get()
                ->map(function ($row) {
                    return [
                        'tipo' => $row->has_delivery ? 'Domicilio' : 'Venta directa',
                        'total_ordenes' => $row->total_ordenes,
                        'ingresos_total' => (float)($row->ingresos_total ?? 0.0),
                        'ticket_promedio' => (float)($row->ticket_promedio ?? 0.0),
                        'total_domicilios' => (float)($row->total_domicilios ?? 0.0)
                    ];
                })
                ->toArray();

            $statistics['domicilios_vs_directa'] = $deliveryStats;

            // Análisis por método de pago
            $paymentStats = DB::table('purchases')
                ->selectRaw('
                    payment_method,
                    COUNT(*) as cantidad_transacciones,
                    SUM(total_amount) as valor_total,
                    AVG(total_amount) as valor_promedio
                ')
                ->where('is_cancelled', 0)
                ->groupBy('payment_method')
                ->get()
                ->map(function ($row) {
                    return [
                        'metodo_pago' => $row->payment_method,
                        'cantidad_transacciones' => $row->cantidad_transacciones,
                        'valor_total' => (float)($row->valor_total ?? 0.0),
                        'valor_promedio' => (float)($row->valor_promedio ?? 0.0)
                    ];
                })
                ->toArray();

            $statistics['metodos_pago'] = $paymentStats;

            return $statistics;

        } catch (\Exception $e) {
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
     * 
     * @return array
     */
    public function getSalesSummaryByDate(): array
    {
        try {
            // Consulta básica que debería funcionar sin problemas
            $salesSummary = DB::table('purchases')
                ->selectRaw('
                    invoice_date AS fecha_texto,
                    SUM(total_amount) AS total
                ')
                ->where('is_cancelled', 0)
                ->groupBy('fecha_texto')
                ->orderBy('fecha_texto')
                ->get()
                ->map(function ($row) {
                    return [
                        'fecha' => $row->fecha_texto,
                        'total' => (float)($row->total ?? 0.0)
                    ];
                })
                ->toArray();

            return ['sales_summary' => $salesSummary];

        } catch (\Exception $e) {
            \Log::error('Error en la consulta de ventas por fecha: ' . $e->getMessage());
            
            try {
                // Si la consulta falla, intentar una consulta más simple
                $totalGeneral = DB::table('purchases')
                    ->where('is_cancelled', 0)
                    ->sum('total_amount');

                return [
                    'message' => 'No se pudieron agrupar las ventas por fecha debido a problemas con los datos',
                    'total_general' => (float)($totalGeneral ?? 0.0)
                ];

            } catch (\Exception $fallbackException) {
                \Log::error('Error en consulta de respaldo: ' . $fallbackException->getMessage());
                return [
                    'error' => 'Error al obtener resumen de ventas por fecha: ' . $e->getMessage(),
                    'sales_summary' => []
                ];
            }
        }
    }
}