<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'total_products' => $this->resource['total_products'] ?? 0,
            'monthly_sales' => [
                'count' => $this->resource['monthly_sales']['count'] ?? 0,
                'revenue' => number_format($this->resource['monthly_sales']['revenue'] ?? 0, 2, '.', '')
            ],
            'weekly_sales' => $this->formatWeeklySales($this->resource['weekly_sales'] ?? []),
            'top_products' => $this->formatTopProducts($this->resource['top_products'] ?? []),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Formatear datos de ventas semanales
     */
    private function formatWeeklySales(array $weeklySales): array
    {
        return collect($weeklySales)->map(function ($sale) {
            return [
                'date' => $sale['date'],
                'count' => $sale['count'],
                'revenue' => number_format($sale['revenue'], 2, '.', '')
            ];
        })->toArray();
    }

    /**
     * Formatear datos de productos más vendidos
     */
    private function formatTopProducts(array $topProducts): array
    {
        return collect($topProducts)->map(function ($product) {
            return [
                'product_name' => $product['product_name'],
                'product_variant' => $product['product_variant'],
                'quantity_sold' => $product['quantity_sold'],
                'revenue' => number_format($product['revenue'], 2, '.', ''),
                'numero_ordenes' => $product['numero_ordenes']
            ];
        })->toArray();
    }
}

class SalesByTimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $data = [
            'period_type' => $this->getPeriodType(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        // Agregar los datos específicos según el tipo de período
        if (isset($this->resource['ventas_por_dia'])) {
            $data['ventas_por_dia'] = $this->formatDailySales($this->resource['ventas_por_dia']);
        }

        if (isset($this->resource['ventas_por_semana'])) {
            $data['ventas_por_semana'] = $this->formatWeeklySales($this->resource['ventas_por_semana']);
        }

        if (isset($this->resource['ventas_por_mes'])) {
            $data['ventas_por_mes'] = $this->formatMonthlySales($this->resource['ventas_por_mes']);
        }

        if (isset($this->resource['ventas_por_año'])) {
            $data['ventas_por_año'] = $this->formatYearlySales($this->resource['ventas_por_año']);
        }

        return $data;
    }

    private function getPeriodType(): string
    {
        if (isset($this->resource['ventas_por_dia'])) return 'daily';
        if (isset($this->resource['ventas_por_semana'])) return 'weekly';
        if (isset($this->resource['ventas_por_mes'])) return 'monthly';
        if (isset($this->resource['ventas_por_año'])) return 'yearly';
        return 'unknown';
    }

    private function formatDailySales(array $sales): array
    {
        return collect($sales)->map(function ($sale) {
            return [
                'fecha' => $sale['fecha'],
                'total_ventas' => $sale['total_ventas'],
                'ingresos' => number_format($sale['ingresos'], 2, '.', ''),
                'ticket_promedio' => number_format($sale['ticket_promedio'], 2, '.', '')
            ];
        })->toArray();
    }

    private function formatWeeklySales(array $sales): array
    {
        return collect($sales)->map(function ($sale) {
            return [
                'semana' => $sale['semana'],
                'inicio_semana' => $sale['inicio_semana'],
                'fin_semana' => $sale['fin_semana'],
                'total_ventas' => $sale['total_ventas'],
                'ingresos' => number_format($sale['ingresos'], 2, '.', '')
            ];
        })->toArray();
    }

    private function formatMonthlySales(array $sales): array
    {
        return collect($sales)->map(function ($sale) {
            return [
                'año' => $sale['año'],
                'mes' => $sale['mes'],
                'mes_nombre' => $this->getMonthName($sale['mes']),
                'total_ventas' => $sale['total_ventas'],
                'ingresos' => number_format($sale['ingresos'], 2, '.', ''),
                'ingresos_domicilio' => number_format($sale['ingresos_domicilio'], 2, '.', '')
            ];
        })->toArray();
    }

    private function formatYearlySales(array $sales): array
    {
        return collect($sales)->map(function ($sale) {
            return [
                'año' => $sale['año'],
                'total_ventas' => $sale['total_ventas'],
                'ingresos' => number_format($sale['ingresos'], 2, '.', ''),
                'ticket_promedio' => number_format($sale['ticket_promedio'], 2, '.', ''),
                'ingresos_domicilio' => number_format($sale['ingresos_domicilio'], 2, '.', '')
            ];
        })->toArray();
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $months[$month] ?? 'Desconocido';
    }
}

class TopProductsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'productos_mas_vendidos' => $this->formatProducts($this->resource['productos_mas_vendidos'] ?? []),
            'periodo' => $this->resource['periodo'] ?? null,
            'total_productos' => count($this->resource['productos_mas_vendidos'] ?? []),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    private function formatProducts(array $products): array
    {
        return collect($products)->map(function ($product, $index) {
            return [
                'ranking' => $index + 1,
                'producto' => $product['producto'],
                'variante' => $product['variante'],
                'cantidad_vendida' => $product['cantidad_vendida'],
                'ingresos' => number_format($product['ingresos'], 2, '.', ''),
                'numero_ordenes' => $product['numero_ordenes'],
                'promedio_por_orden' => $product['numero_ordenes'] > 0 
                    ? number_format($product['cantidad_vendida'] / $product['numero_ordenes'], 2, '.', '')
                    : '0.00'
            ];
        })->toArray();
    }
}

class DeliveryMetricsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'domicilios_vs_directa' => $this->formatDeliveryStats($this->resource['domicilios_vs_directa'] ?? []),
            'metodos_pago' => $this->formatPaymentStats($this->resource['metodos_pago'] ?? []),
            'resumen' => $this->generateSummary(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    private function formatDeliveryStats(array $stats): array
    {
        return collect($stats)->map(function ($stat) {
            return [
                'tipo' => $stat['tipo'],
                'total_ordenes' => $stat['total_ordenes'],
                'ingresos_total' => number_format($stat['ingresos_total'], 2, '.', ''),
                'ticket_promedio' => number_format($stat['ticket_promedio'], 2, '.', ''),
                'total_domicilios' => number_format($stat['total_domicilios'], 2, '.', ''),
                'porcentaje_ordenes' => $this->calculatePercentage($stat['total_ordenes'])
            ];
        })->toArray();
    }

    private function formatPaymentStats(array $stats): array
    {
        return collect($stats)->map(function ($stat) {
            return [
                'metodo_pago' => $stat['metodo_pago'],
                'cantidad_transacciones' => $stat['cantidad_transacciones'],
                'valor_total' => number_format($stat['valor_total'], 2, '.', ''),
                'valor_promedio' => number_format($stat['valor_promedio'], 2, '.', ''),
                'porcentaje_transacciones' => $this->calculatePaymentPercentage($stat['cantidad_transacciones'])
            ];
        })->toArray();
    }

    private function calculatePercentage(int $orders): string
    {
        $totalOrders = collect($this->resource['domicilios_vs_directa'] ?? [])
            ->sum('total_ordenes');
        
        if ($totalOrders === 0) return '0.00';
        
        return number_format(($orders / $totalOrders) * 100, 2, '.', '');
    }

    private function calculatePaymentPercentage(int $transactions): string
    {
        $totalTransactions = collect($this->resource['metodos_pago'] ?? [])
            ->sum('cantidad_transacciones');
        
        if ($totalTransactions === 0) return '0.00';
        
        return number_format(($transactions / $totalTransactions) * 100, 2, '.', '');
    }

    private function generateSummary(): array
    {
        $deliveryStats = $this->resource['domicilios_vs_directa'] ?? [];
        $paymentStats = $this->resource['metodos_pago'] ?? [];

        $totalOrders = collect($deliveryStats)->sum('total_ordenes');
        $totalRevenue = collect($deliveryStats)->sum('ingresos_total');

        return [
            'total_ordenes' => $totalOrders,
            'ingresos_totales' => number_format($totalRevenue, 2, '.', ''),
            'ticket_promedio_general' => $totalOrders > 0 
                ? number_format($totalRevenue / $totalOrders, 2, '.', '')
                : '0.00',
            'metodos_pago_disponibles' => count($paymentStats),
            'tipos_entrega_disponibles' => count($deliveryStats)
        ];
    }
}