<?php


namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PurchaseServiceEloquent
{
    /**
     * Versión mejorada usando Eloquent ORM
     * Mantiene exactamente la misma lógica que FastAPI
     */

    /**
     * Obtiene resumen de ventas usando Eloquent
     * 
     * @param string $period
     * @return array
     */
    public function getSalesSummary(string $period = 'today'): array
    {
        // Determinar fechas - igual que FastAPI
        $endDate = Carbon::today();
        
        if ($period === 'today') {
            $startDate = $endDate->copy();
        } elseif ($period === 'week') {
            $startDate = $endDate->copy()->subDays(7);
        } elseif ($period === 'month') {
            $startDate = $endDate->copy()->subDays(30);
        } elseif ($period === 'year') {
            $startDate = $endDate->copy()->subDays(365);
        } else {
            $startDate = $endDate->copy();
        }

        // Obtener compras en el rango de fechas
        $purchases = Purchase::whereBetween('invoice_date', [$startDate, $endDate])
            ->with(['details'])
            ->get();

        // Calcular resumen igual que FastAPI
        $summary = [
            'total_purchases' => $purchases->count(),
            'total_items_sold' => $purchases->sum(function($purchase) {
                return $purchase->details->count();
            }),
            'total_quantity_sold' => $purchases->sum(function($purchase) {
                return $purchase->details->sum('quantity');
            }),
            'total_products_revenue' => $purchases->sum('subtotal_products'),
            'total_delivery_revenue' => $purchases->sum('delivery_fee'),
            'total_revenue' => $purchases->sum('total_amount'),
            'average_purchase_value' => $purchases->avg('total_amount'),
            'unique_clients' => $purchases->pluck('client_name')->unique()->count(),
            'deliveries_count' => $purchases->where('has_delivery', true)->count()
        ];

        // Métodos de pago más usados
        $paymentMethods = $purchases->groupBy('payment_method')
            ->map(function($group) {
                return [
                    'payment_method' => $group->first()->payment_method,
                    'count' => $group->count(),
                    'total' => $group->sum('total_amount')
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray();

        // Productos más vendidos
        $topProducts = $purchases->flatMap(function($purchase) {
                return $purchase->details;
            })
            ->groupBy(function($detail) {
                return $detail->product_name . '|' . ($detail->product_variant ?? '');
            })
            ->map(function($group) {
                $first = $group->first();
                return [
                    'product_name' => $first->product_name,
                    'product_variant' => $first->product_variant,
                    'total_quantity' => $group->sum('quantity'),
                    'total_revenue' => $group->sum('subtotal'),
                    'times_sold' => $group->pluck('purchase_id')->unique()->count()
                ];
            })
            ->sortByDesc('total_quantity')
            ->take(10)
            ->values()
            ->toArray();

        return [
            'period' => $period,
            'start_date' => $startDate->format('d/m/Y'),
            'end_date' => $endDate->format('d/m/Y'),
            'summary' => $summary,
            'payment_methods' => $paymentMethods,
            'top_products' => $topProducts
        ];
    }

    /**
     * Obtiene compras por rango de fechas usando Eloquent
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getPurchasesByDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $purchases = Purchase::whereBetween('invoice_date', [$startDate, $endDate])
            ->with(['seller', 'details'])
            ->orderBy('invoice_date', 'desc')
            ->orderBy('invoice_time', 'desc')
            ->get();

        return $purchases->map(function($purchase) {
            return array_merge($purchase->toArray(), [
                'seller_email' => $purchase->seller->email ?? '',
                'total_items' => $purchase->details->count(),
                'total_quantity' => $purchase->details->sum('quantity'),
                'invoice_date' => $purchase->invoice_date->format('d/m/Y'),
                'invoice_time' => $purchase->invoice_time ? $purchase->invoice_time->format('H:i:s') : ''
            ]);
        })->toArray();
    }

    /**
     * Obtiene compra por número de factura usando Eloquent
     * 
     * @param string $invoiceNumber
     * @return array|null
     */
    public function getPurchaseByInvoice(string $invoiceNumber): ?array
    {
        $purchase = Purchase::where('invoice_number', $invoiceNumber)
            ->with(['seller', 'details'])
            ->first();

        if (!$purchase) {
            return null;
        }

        $purchaseData = $purchase->toArray();
        $purchaseData['seller_email'] = $purchase->seller->email ?? '';
        $purchaseData['products'] = $purchase->details->toArray();
        $purchaseData['invoice_date'] = $purchase->invoice_date->format('d/m/Y');
        $purchaseData['invoice_time'] = $purchase->invoice_time ? $purchase->invoice_time->format('H:i:s') : '';

        return $purchaseData;
    }

    /**
     * Obtiene compras por cliente
     * 
     * @param string $clientName
     * @return array
     */
    public function getPurchasesByClient(string $clientName): array
    {
        $purchases = Purchase::where('client_name', $clientName)
            ->with(['details', 'seller'])
            ->orderBy('invoice_date', 'desc')
            ->orderBy('invoice_time', 'desc')
            ->get();

        return $purchases->toArray();
    }
}