<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    /**
     * Obtiene un resumen de ventas para el período especificado
     * Exactamente igual que la lógica de FastAPI
     * 
     * @param string $period 'today', 'week', 'month', 'year'
     * @return array Dict con el resumen de ventas
     */
    public function getSalesSummary(string $period = 'today'): array
    {
        // Determinar fechas según el período - igual que en FastAPI
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
        
        // Query para obtener resumen - igual que en FastAPI
        $summaryQuery = "
            SELECT 
                COUNT(DISTINCT p.id) as total_purchases,
                COUNT(pd.id) as total_items_sold,
                SUM(pd.quantity) as total_quantity_sold,
                SUM(p.subtotal_products) as total_products_revenue,
                SUM(p.delivery_fee) as total_delivery_revenue,
                SUM(p.total_amount) as total_revenue,
                AVG(p.total_amount) as average_purchase_value,
                COUNT(DISTINCT p.client_name) as unique_clients,
                COUNT(DISTINCT CASE WHEN p.has_delivery THEN p.id END) as deliveries_count
            FROM purchases p
            LEFT JOIN purchase_details pd ON p.id = pd.purchase_id
            WHERE p.invoice_date BETWEEN ? AND ?
        ";
        
        $summary = DB::selectOne($summaryQuery, [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        
        // Query para métodos de pago más usados - igual que en FastAPI
        $paymentMethodsQuery = "
            SELECT payment_method, COUNT(*) as count, SUM(total_amount) as total
            FROM purchases
            WHERE invoice_date BETWEEN ? AND ?
            GROUP BY payment_method
            ORDER BY count DESC
        ";
        
        $paymentMethods = DB::select($paymentMethodsQuery, [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        
        // Query para productos más vendidos - igual que en FastAPI
        $topProductsQuery = "
            SELECT 
                pd.product_name,
                pd.product_variant,
                SUM(pd.quantity) as total_quantity,
                SUM(pd.subtotal) as total_revenue,
                COUNT(DISTINCT p.id) as times_sold
            FROM purchase_details pd
            JOIN purchases p ON pd.purchase_id = p.id
            WHERE p.invoice_date BETWEEN ? AND ?
            GROUP BY pd.product_name, pd.product_variant
            ORDER BY total_quantity DESC
            LIMIT 10
        ";
        
        $topProducts = DB::select($topProductsQuery, [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        
        return [
            'period' => $period,
            'start_date' => $startDate->format('d/m/Y'),
            'end_date' => $endDate->format('d/m/Y'),
            'summary' => $summary ? (array) $summary : [],
            'payment_methods' => array_map(function($item) {
                return (array) $item;
            }, $paymentMethods ?: []),
            'top_products' => array_map(function($item) {
                return (array) $item;
            }, $topProducts ?: [])
        ];
    }

    /**
     * Obtiene todas las compras en un rango de fechas
     * Igual que la función get_purchases_by_date_range de FastAPI
     * 
     * @param Carbon $startDate Fecha inicial
     * @param Carbon $endDate Fecha final
     * @return array Lista de compras
     */
    public function getPurchasesByDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $query = "
            SELECT p.*, u.email as seller_email,
                   COUNT(pd.id) as total_items,
                   SUM(pd.quantity) as total_quantity
            FROM purchases p
            JOIN users u ON p.seller_username = u.username
            LEFT JOIN purchase_details pd ON p.id = pd.purchase_id
            WHERE p.invoice_date BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY p.invoice_date DESC, p.invoice_time DESC
        ";
        
        $purchases = DB::select($query, [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        
        if (empty($purchases)) {
            return [];
        }
        
        // Formatear fechas - igual que en FastAPI
        $formattedPurchases = [];
        foreach ($purchases as $purchase) {
            $purchaseArray = (array) $purchase;
            
            if ($purchaseArray['invoice_date']) {
                $purchaseArray['invoice_date'] = Carbon::parse($purchaseArray['invoice_date'])->format('d/m/Y');
            }
            if ($purchaseArray['invoice_time']) {
                $purchaseArray['invoice_time'] = (string) $purchaseArray['invoice_time'];
            }
            
            $formattedPurchases[] = $purchaseArray;
        }
        
        return $formattedPurchases;
    }

    /**
     * Obtiene una compra por su número de factura
     * Igual que get_purchase_by_invoice de FastAPI
     * 
     * @param string $invoiceNumber Número de factura
     * @return array|null Dict con la información de la compra o null si no existe
     */
    public function getPurchaseByInvoice(string $invoiceNumber): ?array
    {
        // Obtener datos principales de la compra - igual que en FastAPI
        $purchaseQuery = "
            SELECT p.*, u.email as seller_email
            FROM purchases p
            JOIN users u ON p.seller_username = u.username
            WHERE p.invoice_number = ?
        ";
        
        $purchase = DB::selectOne($purchaseQuery, [$invoiceNumber]);
        
        if (!$purchase) {
            return null;
        }
        
        // Obtener detalles de los productos - igual que en FastAPI
        $detailsQuery = "
            SELECT * FROM purchase_details
            WHERE purchase_id = ?
            ORDER BY id
        ";
        
        $details = DB::select($detailsQuery, [$purchase->id]);
        
        // Formatear respuesta - igual que en FastAPI
        $purchaseData = (array) $purchase;
        $purchaseData['products'] = array_map(function($detail) {
            return (array) $detail;
        }, $details ?: []);
        
        // Formatear fecha y hora - igual que en FastAPI
        if ($purchaseData['invoice_date']) {
            $purchaseData['invoice_date'] = Carbon::parse($purchaseData['invoice_date'])->format('d/m/Y');
        }
        if ($purchaseData['invoice_time']) {
            $purchaseData['invoice_time'] = (string) $purchaseData['invoice_time'];
        }
        
        return $purchaseData;
    }

    /**
     * Crea una nueva compra
     * 
     * @param array $purchaseData
     * @return Purchase
     */
    public function createPurchase(array $purchaseData): Purchase
    {
        return Purchase::create($purchaseData);
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
            ->with('details')
            ->orderBy('invoice_date', 'desc')
            ->orderBy('invoice_time', 'desc')
            ->get();

        return $purchases->toArray();
    }
}