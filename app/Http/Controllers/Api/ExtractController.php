<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExtractService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ExtractController extends Controller
{
    protected $extractService;

    public function __construct(ExtractService $extractService)
    {
        $this->extractService = $extractService;
    }

    /**
     * Obtiene un extracto detallado de compras por mes y año
     * 
     * @param Request $request
     * @param int $year
     * @param int $month
     * @return JsonResponse
     */
    public function getMonthlyExtract(Request $request, int $year, int $month): JsonResponse
    {
        try {
            // Validar mes
            if ($month < 1 || $month > 12) {
                return response()->json([
                    'error' => 'El mes debe estar entre 1 y 12'
                ], 400);
            }

            // Obtener parámetros de paginación
            $page = (int) $request->get('page', 1);
            $pageSize = (int) $request->get('page_size', 50);

            // Validar parámetros de paginación
            if ($page < 1) $page = 1;
            if ($pageSize < 1 || $pageSize > 1000) $pageSize = 50;

            $result = $this->extractService->getMonthlyPurchaseExtract($year, $month, $page, $pageSize);

            // Obtener nombre del mes en español
            $monthName = Carbon::create($year, $month, 1)->translatedFormat('F');

            return response()->json([
                'year' => $year,
                'month' => $month,
                'month_name' => ucfirst($monthName),
                'total_records' => $result['total'],
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_pages' => $result['total_pages'],
                'has_next' => $result['has_next'],
                'has_previous' => $result['has_previous'],
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener el extracto mensual: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera un PDF del extracto mensual
     * 
     * @param int $year
     * @param int $month
     * @return \Illuminate\Http\Response
     */
    public function generateMonthlyPdf(int $year, int $month)
    {
        try {
            if ($month < 1 || $month > 12) {
                return response()->json([
                    'error' => 'El mes debe estar entre 1 y 12'
                ], 400);
            }

            $pdf = $this->extractService->generateMonthlyExtractPdf($year, $month);
            $monthName = Carbon::create($year, $month, 1)->translatedFormat('F');
            $filename = "extracto_mensual_{$monthName}_{$year}.pdf";

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera un PDF del extracto diario
     * 
     * @param string $date
     * @return \Illuminate\Http\Response
     */
    public function generateDailyPdf(string $date)
    {
        try {
            $targetDate = Carbon::createFromFormat('Y-m-d', $date);
            
            if (!$targetDate) {
                return response()->json([
                    'error' => 'Formato de fecha inválido. Use YYYY-MM-DD'
                ], 400);
            }

            $pdf = $this->extractService->generateDailyExtractPdf($targetDate);
            $filename = "extracto_diario_{$targetDate->format('Y-m-d')}.pdf";

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera un PDF del extracto por rango de fechas
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateRangePdf(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d'
            ]);

            $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
            $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date);

            if ($startDate->gt($endDate)) {
                return response()->json([
                    'error' => 'La fecha inicial debe ser anterior o igual a la fecha final'
                ], 400);
            }

            $pdf = $this->extractService->generateRangeExtractPdf($startDate, $endDate);
            $filename = "extracto_rango_{$startDate->format('Y-m-d')}_al_{$endDate->format('Y-m-d')}.pdf";

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Datos de entrada inválidos',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene un extracto detallado de compras para una fecha específica
     * 
     * @param Request $request
     * @param string $date
     * @return JsonResponse
     */
    public function getDailyExtract(Request $request, string $date): JsonResponse
    {
        try {
            // Validar y convertir fecha
            $targetDate = Carbon::createFromFormat('Y-m-d', $date);
            
            if (!$targetDate) {
                return response()->json([
                    'error' => 'Formato de fecha inválido. Use YYYY-MM-DD'
                ], 400);
            }

            // Obtener parámetros de paginación
            $page = (int) $request->get('page', 1);
            $pageSize = (int) $request->get('page_size', 50);

            if ($page < 1) $page = 1;
            if ($pageSize < 1 || $pageSize > 1000) $pageSize = 50;

            $result = $this->extractService->getDailyPurchaseExtract($targetDate, $page, $pageSize);

            return response()->json([
                'date' => $targetDate->format('d/m/Y'),
                'total_records' => $result['total'],
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_pages' => $result['total_pages'],
                'has_next' => $result['has_next'],
                'has_previous' => $result['has_previous'],
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener el extracto diario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene un extracto detallado de compras para un rango de fechas
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getDateRangeExtract(Request $request): JsonResponse
    {
        try {
            // Validar parámetros
            $request->validate([
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d'
            ]);

            $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
            $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date);

            // Validar que start_date sea anterior o igual a end_date
            if ($startDate->gt($endDate)) {
                return response()->json([
                    'error' => 'La fecha inicial debe ser anterior o igual a la fecha final'
                ], 400);
            }

            $page = (int) $request->get('page', 1);
            $pageSize = (int) $request->get('page_size', 50);

            if ($page < 1) $page = 1;
            if ($pageSize < 1 || $pageSize > 1000) $pageSize = 50;

            $result = $this->extractService->getDateRangePurchaseExtract($startDate, $endDate, $page, $pageSize);

            return response()->json([
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
                'total_records' => $result['total'],
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_pages' => $result['total_pages'],
                'has_next' => $result['has_next'],
                'has_previous' => $result['has_previous'],
                'data' => $result['data']
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Datos de entrada inválidos',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener el extracto por rango de fechas: ' . $e->getMessage()
            ], 500);
        }
    }
}