<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PDFController extends Controller
{
    protected $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function generateReport(Request $request)
    {
        // Transformar los datos de entrada
        $input = $request->all();
        
        // Normalizar la estructura de los datos
        $reportData = $this->normalizeReportData($input);

        // Validación de los datos normalizados
        $validator = Validator::make($reportData, [
            'total_ventas' => 'required',
            'cantidad_ventas' => 'required|numeric',
            'ticket_promedio' => 'required',
            'cantidad_domicilios' => 'required|numeric',
            'productos_top' => 'sometimes|array',
            'metodos_pago' => 'sometimes|array',
            'period' => 'required|string'
        ], [
            // Mensajes de error personalizados
            'total_ventas.required' => 'El total de ventas es obligatorio.',
            'cantidad_ventas.required' => 'La cantidad de ventas es obligatoria.',
            'cantidad_ventas.numeric' => 'La cantidad de ventas debe ser un número.',
            'ticket_promedio.required' => 'El ticket promedio es obligatorio.',
            'cantidad_domicilios.required' => 'La cantidad de domicilios es obligatoria.',
            'cantidad_domicilios.numeric' => 'La cantidad de domicilios debe ser un número.',
            'period.required' => 'El período es obligatorio.'
        ]);

        // Si la validación falla, devolver errores
            if ($validator->fails()) {
                return response()->json([
                'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 400);
            }

        try {
            // Generar el PDF
            $pdfContent = $this->pdfService->generatePDFReport(
                $reportData,
                $reportData['extract_data'] ?? [], 
                $reportData['period'], 
                $reportData['use_logo'] ?? true
            );

            // Devolver el PDF como una descarga
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reporte_ventas.pdf"'
            ]);

        } catch (\Exception $e) {
            // Manejo de errores durante la generación del PDF
            return response()->json([
                'message' => 'Error generando el reporte PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normaliza los nombres de los campos del reporte
     */
    private function normalizeReportData(array $input): array
    {
        // Mapeo de posibles nombres de campos
        $mappings = [
            'total_ventas' => ['total_ventas', 'totalVentas', 'total ventas'],
            'cantidad_ventas' => ['cantidad_ventas', 'cantidadVentas', 'cantidad ventas'],
            'ticket_promedio' => ['ticket_promedio', 'ticketPromedio', 'ticket promedio'],
            'cantidad_domicilios' => ['cantidad_domicilios', 'cantidadDomicilios', 'cantidad domicilios'],
            'productos_top' => ['productos_top', 'productosTop', 'productos top'],
            'metodos_pago' => ['metodos_pago', 'metodosPago', 'metodos pago'],
            'extract_data' => ['extract_data', 'extractData', 'extract data'],
            'period' => ['period', 'periodo', 'Period'],
            'use_logo' => ['use_logo', 'useLogo', 'use logo']
        ];

        $normalizedData = [];

        // Buscar y normalizar cada campo
        foreach ($mappings as $normalKey => $possibleKeys) {
            foreach ($possibleKeys as $key) {
                // Buscar en diferentes niveles de anidamiento
                $value = $this->findNestedValue($input, $key);
                
                if ($value !== null) {
                    $normalizedData[$normalKey] = $value;
                    break;
                }
            }
        }

        return $normalizedData;
    }

    /**
     * Busca un valor en un array anidado
     */
    private function findNestedValue(array $array, string $key)
    {
        // Buscar directamente
        if (isset($array[$key])) {
            return $array[$key];
        }

        // Buscar en subarrays
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                // Buscar en el subarray
                if (isset($v[$key])) {
                    return $v[$key];
                }
                
                // Búsqueda recursiva
                $nestedResult = $this->findNestedValue($v, $key);
                if ($nestedResult !== null) {
                    return $nestedResult;
                }
            }
        }

        return null;
    }

    /**
     * Verifica si existe un logo disponible
     * 
     * @return JsonResponse
     */
    public function checkLogo()
    {
        try {
            $logoPath = $this->pdfService->getLogoPath();
            
            return response()->json([
                'logo_available' => !is_null($logoPath),
                'logo_path' => $logoPath
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error verificando logo: ' . $e->getMessage(),
                'logo_available' => false,
                'logo_path' => null
            ], 500);
        }
    }

    /**
     * Endpoint para generar reportes de prueba (útil para testing)
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function generateSampleReport()
    {
        try {
            // Datos de muestra para testing
            $sampleReportData = [
                'total_ventas' => 1250000.00,
                'cantidad_ventas' => 45,
                'ticket_promedio' => 27777.78,
                'cantidad_domicilios' => 12,
                'metodos_pago' => [
                    [
                        'payment_method' => 'Efectivo',
                        'count' => 20,
                        'total' => 550000.00
                    ],
                    [
                        'payment_method' => 'Tarjeta',
                        'count' => 15,
                        'total' => 450000.00
                    ],
                    [
                        'payment_method' => 'Transferencia',
                        'count' => 10,
                        'total' => 250000.00
                    ]
                ],
                'productos_top' => [
                    [
                        'producto' => 'Hamburguesa Clásica',
                        'variante' => 'Con papas',
                        'cantidad_vendida' => 25,
                        'numero_ordenes' => 20,
                        'ingresos' => 375000.00
                    ],
                    [
                        'producto' => 'Pizza Margarita',
                        'variante' => 'Mediana',
                        'cantidad_vendida' => 18,
                        'numero_ordenes' => 15,
                        'ingresos' => 270000.00
                    ]
                ]
            ];

            $sampleExtractData = [
                [
                    'invoice_number' => 'FAC-001',
                    'invoice_date' => '15/07/2025',
                    'invoice_time' => '14:30',
                    'cliente' => 'Juan Pérez',
                    'vendedor' => 'María González',
                    'product_name' => 'Hamburguesa Clásica',
                    'product_variant' => 'Con papas',
                    'quantity' => 2,
                    'unit_price' => 15000.00,
                    'subtotal' => 30000.00,
                    'total_amount' => 30000.00,
                    'payment_method' => 'Efectivo'
                ],
                [
                    'invoice_number' => 'FAC-002',
                    'invoice_date' => '15/07/2025',
                    'invoice_time' => '15:45',
                    'cliente' => 'Ana López',
                    'vendedor' => 'Carlos Rodríguez',
                    'product_name' => 'Pizza Margarita',
                    'product_variant' => 'Mediana',
                    'quantity' => 1,
                    'unit_price' => 18000.00,
                    'subtotal' => 18000.00,
                    'total_amount' => 18000.00,
                    'payment_method' => 'Tarjeta'
                ]
            ];

            $pdfContent = $this->pdfService->generatePDFReport(
                $sampleReportData,
                $sampleExtractData,
                'Reporte de Muestra - Julio 2025',
                true
            );

            $filename = 'reporte_muestra_' . now()->format('Ymd_His') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            \Log::error('Error generando reporte de muestra: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al generar el reporte de muestra: ' . $e->getMessage()
            ], 500);
        }
    }
}