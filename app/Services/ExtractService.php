<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class ExtractService
{
    /**
     * Obtiene un extracto detallado de compras por mes y año con paginación
     *
     * @param int $year
     * @param int $month
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws Exception
     */
    public function getMonthlyPurchaseExtract(int $year, int $month, int $page = 1, int $pageSize = 50): array
    {
        try {
            // Primero obtenemos el total de registros
            $totalQuery = "
                SELECT COUNT(*) as total
                FROM purchases p
                JOIN purchase_details pd ON p.id = pd.purchase_id
                WHERE YEAR(p.invoice_date) = ? 
                  AND MONTH(p.invoice_date) = ?
            ";
            
            $totalResult = DB::select($totalQuery, [$year, $month]);
            $total = $totalResult[0]->total ?? 0;

            // Calcular offset
            $offset = ($page - 1) * $pageSize;

            // Query principal con paginación
            $results = DB::select("
                SELECT
                    p.invoice_number,
                    p.invoice_date,
                    p.invoice_time,
                    CONCAT(p.client_name, ' (', p.client_phone, ')') AS cliente,
                    u.username AS vendedor,
                    pd.product_name,
                    pd.product_variant,
                    pd.quantity,
                    pd.unit_price,
                    pd.subtotal,
                    p.subtotal_products,
                    p.total_amount,
                    p.payment_method,
                    p.created_at
                FROM purchases p
                JOIN purchase_details pd ON p.id = pd.purchase_id
                LEFT JOIN users u ON p.seller_username = u.username
                WHERE YEAR(p.invoice_date) = ? 
                  AND MONTH(p.invoice_date) = ?
                ORDER BY p.invoice_date, p.invoice_time
                LIMIT ? OFFSET ?
            ", [$year, $month, $pageSize, $offset]);

            $data = $this->formatExtractData($results);
            
            return $this->buildPaginationResponse($data, $total, $page, $pageSize);

        } catch (Exception $e) {
            throw new Exception("Error al obtener extracto mensual: " . $e->getMessage());
        }
    }

    /**
     * Obtiene un extracto detallado de compras para una fecha específica con paginación
     *
     * @param Carbon $targetDate
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws Exception
     */
    public function getDailyPurchaseExtract(Carbon $targetDate, int $page = 1, int $pageSize = 50): array
    {
        try {
            // Total de registros
            $totalQuery = "
                SELECT COUNT(*) as total
                FROM purchases p
                JOIN purchase_details pd ON p.id = pd.purchase_id
                WHERE p.invoice_date = ?
            ";
            
            $totalResult = DB::select($totalQuery, [$targetDate->format('Y-m-d')]);
            $total = $totalResult[0]->total ?? 0;

            $offset = ($page - 1) * $pageSize;

            $results = DB::select("
                SELECT
                    p.invoice_number,
                    p.invoice_date,
                    p.invoice_time,
                    CONCAT(p.client_name, ' (', p.client_phone, ')') AS cliente,
                    u.username AS vendedor,
                    pd.product_name,
                    pd.product_variant,
                    pd.quantity,
                    pd.unit_price,
                    pd.subtotal,
                    p.subtotal_products,
                    p.total_amount,
                    p.payment_method,
                    p.created_at
                FROM purchases p
                JOIN purchase_details pd ON p.id = pd.purchase_id
                LEFT JOIN users u ON p.seller_username = u.username
                WHERE p.invoice_date = ?
                ORDER BY p.invoice_time
                LIMIT ? OFFSET ?
            ", [$targetDate->format('Y-m-d'), $pageSize, $offset]);

            $data = $this->formatExtractData($results);
            
            return $this->buildPaginationResponse($data, $total, $page, $pageSize);

        } catch (Exception $e) {
            throw new Exception("Error al obtener extracto diario: " . $e->getMessage());
        }
    }

    /**
     * Obtiene un extracto detallado de compras para un rango de fechas con paginación
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws Exception
     */
    public function getDateRangePurchaseExtract(Carbon $startDate, Carbon $endDate, int $page = 1, int $pageSize = 50): array
    {
        try {
            // Total de registros
            $totalQuery = "
                SELECT COUNT(*) as total
                FROM purchases p
                JOIN purchase_details pd ON p.id = pd.purchase_id
                WHERE p.invoice_date BETWEEN ? AND ?
            ";
            
            $totalResult = DB::select($totalQuery, [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            $total = $totalResult[0]->total ?? 0;

            $offset = ($page - 1) * $pageSize;

            $results = DB::select("
                SELECT
                    p.invoice_number,
                    p.invoice_date,
                    p.invoice_time,
                    CONCAT(p.client_name, ' (', p.client_phone, ')') AS cliente,
                    u.username AS vendedor,
                    pd.product_name,
                    pd.product_variant,
                    pd.quantity,
                    pd.unit_price,
                    pd.subtotal,
                    p.subtotal_products,
                    p.total_amount,
                    p.payment_method,
                    p.created_at
                FROM purchases p
                JOIN purchase_details pd ON p.id = pd.purchase_id
                LEFT JOIN users u ON p.seller_username = u.username
                WHERE p.invoice_date BETWEEN ? AND ?
                ORDER BY p.invoice_date, p.invoice_time
                LIMIT ? OFFSET ?
            ", [$startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $pageSize, $offset]);

            $data = $this->formatExtractData($results);
            
            return $this->buildPaginationResponse($data, $total, $page, $pageSize);

        } catch (Exception $e) {
            throw new Exception("Error al obtener extracto por rango de fechas: " . $e->getMessage());
        }
    }

    /**
     * Formatea los datos del extracto para mejor legibilidad
     *
     * @param array $results
     * @return array
     */
    private function formatExtractData(array $results): array
    {
        $formattedResults = [];

        foreach ($results as $row) {
            $formattedRow = (array) $row;

            // Formatear fechas y horas
            if (isset($formattedRow['invoice_date']) && $formattedRow['invoice_date']) {
                $formattedRow['invoice_date'] = Carbon::parse($formattedRow['invoice_date'])->format('d/m/Y');
            }

            if (isset($formattedRow['invoice_time']) && $formattedRow['invoice_time']) {
                $formattedRow['invoice_time'] = (string) $formattedRow['invoice_time'];
            }

            if (isset($formattedRow['created_at']) && $formattedRow['created_at']) {
                $formattedRow['created_at'] = Carbon::parse($formattedRow['created_at'])->format('d/m/Y H:i:s');
            }

            // Asegurar que los valores numéricos sean float para JSON
            $numericFields = ['quantity', 'unit_price', 'subtotal', 'subtotal_products', 'total_amount'];
            foreach ($numericFields as $field) {
                if (isset($formattedRow[$field])) {
                    $formattedRow[$field] = (float) $formattedRow[$field];
                }
            }

            $formattedResults[] = $formattedRow;
        }

        return $formattedResults;
    }

    /**
     * Construye la respuesta de paginación
     *
     * @param array $data
     * @param int $total
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    private function buildPaginationResponse(array $data, int $total, int $page, int $pageSize): array
    {
        $totalPages = (int) ceil($total / $pageSize);
        
        return [
            'data' => $data,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_previous' => $page > 1
        ];
    }

    /**
     * Genera PDF del extracto mensual
     *
     * @param int $year
     * @param int $month
     * @return string
     * @throws Exception
     */
    public function generateMonthlyExtractPdf(int $year, int $month): string
    {
        try {
            // Obtener todos los datos sin paginación para el PDF
            $results = DB::select("
                SELECT
                    p.invoice_number,
                    p.invoice_date,
                    p.invoice_time,
                    CONCAT(p.client_name, ' (', p.client_phone, ')') AS cliente,
                    u.username AS vendedor,
                    pd.product_name,
                    pd.product_variant,
                    pd.quantity,
                    pd.unit_price,
                    pd.subtotal,
                    p.subtotal_products,
                    p.total_amount,
                    p.payment_method
                FROM purchases p
                JOIN purchase_details pd ON p.id = pd.purchase_id
                LEFT JOIN users u ON p.seller_username = u.username
                WHERE YEAR(p.invoice_date) = ? 
                  AND MONTH(p.invoice_date) = ?
                ORDER BY p.invoice_date, p.invoice_time
            ", [$year, $month]);

            $data = $this->formatExtractData($results);
            $monthName = Carbon::create($year, $month, 1)->translatedFormat('F');
            
            return $this->generatePdf($data, "Extracto Mensual - {$monthName} {$year}");

        } catch (Exception $e) {
            throw new Exception("Error al generar PDF mensual: " . $e->getMessage());
        }
    }

    /**
     * Genera PDF del extracto diario
     *
     * @param Carbon $targetDate
     * @return string
     * @throws Exception
     */
    public function generateDailyExtractPdf(Carbon $targetDate): string
    {
        try {
            $results = DB::select("
                SELECT
                    p.invoice_number,
                    p.invoice_date,
                    p.invoice_time,
                    CONCAT(p.client_name, ' (', p.client_phone, ')') AS cliente,
                    u.username AS vendedor,
                    pd.product_name,
                    pd.product_variant,
                    pd.quantity,
                    pd.unit_price,
                    pd.subtotal,
                    p.subtotal_products,
                    p.total_amount,
                    p.payment_method
                FROM purchases p
                JOIN purchase_details pd ON p.id = pd.purchase_id
                LEFT JOIN users u ON p.seller_username = u.username
                WHERE p.invoice_date = ?
                ORDER BY p.invoice_time
            ", [$targetDate->format('Y-m-d')]);

            $data = $this->formatExtractData($results);
            
            return $this->generatePdf($data, "Extracto Diario - {$targetDate->format('d/m/Y')}");

        } catch (Exception $e) {
            throw new Exception("Error al generar PDF diario: " . $e->getMessage());
        }
    }

    /**
     * Genera PDF del extracto por rango de fechas
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     * @throws Exception
     */
    public function generateRangeExtractPdf(Carbon $startDate, Carbon $endDate): string
    {
        try {
            $results = DB::select("
                SELECT
                    p.invoice_number,
                    p.invoice_date,
                    p.invoice_time,
                    CONCAT(p.client_name, ' (', p.client_phone, ')') AS cliente,
                    u.username AS vendedor,
                    pd.product_name,
                    pd.product_variant,
                    pd.quantity,
                    pd.unit_price,
                    pd.subtotal,
                    p.subtotal_products,
                    p.total_amount,
                    p.payment_method
                FROM purchases p
                JOIN purchase_details pd ON p.id = pd.purchase_id
                LEFT JOIN users u ON p.seller_username = u.username
                WHERE p.invoice_date BETWEEN ? AND ?
                ORDER BY p.invoice_date, p.invoice_time
            ", [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            $data = $this->formatExtractData($results);
            
            return $this->generatePdf($data, "Extracto {$startDate->format('d/m/Y')} - {$endDate->format('d/m/Y')}");

        } catch (Exception $e) {
            throw new Exception("Error al generar PDF por rango: " . $e->getMessage());
        }
    }

    /**
     * Genera el PDF usando DomPDF
     *
     * @param array $data
     * @param string $title
     * @return string
     * @throws Exception
     */
    private function generatePdf(array $data, string $title): string
    {
        try {
            $pdf = app('dompdf.wrapper');
            
            // Calcular totales
            $totalAmount = 0;
            $totalRecords = count($data);
            
            foreach ($data as $record) {
                $totalAmount += $record['total_amount'] ?? 0;
            }

            $html = view('extracts.pdf', [
                'title' => $title,
                'data' => $data,
                'totalRecords' => $totalRecords,
                'totalAmount' => $totalAmount,
                'generatedAt' => Carbon::now()->format('d/m/Y H:i:s')
            ])->render();

            $pdf->loadHTML($html);
            $pdf->setPaper('A4', 'landscape');
            
            return $pdf->output();

        } catch (Exception $e) {
            throw new Exception("Error al generar PDF: " . $e->getMessage());
        }
    }
}