<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;

class PDFService
{
    public function generatePDFReport(array $reportData, array $extractData, string $period, bool $useLogo = true): string
    {
        try {
            // Render the view with all necessary data
            $html = view('pdf.report', compact('reportData', 'extractData', 'period', 'useLogo'))->render();

            // Generate the PDF using Browsershot
            $pdf = Browsershot::html($html)
                ->format('A4')
                ->margins(20, 20, 20, 20)
                ->showBrowserHeaderAndFooter(false)
                ->printBackground()
                ->pdf();

            return $pdf;
        } catch (\Exception $e) {
            // Log the full error for debugging
            Log::error('PDF Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'reportData' => array_keys($reportData),
                'extractData' => count($extractData)
            ]);

            // Rethrow the exception to be handled by the caller
            throw $e;
        }
    }

    /**
     * Busca el logo en el directorio public/icons.
     */
    public function getLogoPath(): ?string
    {
        $iconsDir = public_path('icons');
        
        $logoNames = ['logo.svg', 'logo.png', 'logo.jpg', 'logo.jpeg', 'brand.png', 'company.png'];
        
        foreach ($logoNames as $logoName) {
            $logoPath = $iconsDir . DIRECTORY_SEPARATOR . $logoName;
            
            if (file_exists($logoPath) && is_readable($logoPath)) {
                return $logoPath;
            }
        }
        
        Log::warning('No logo found in icons directory');
        return null;
    }
}


// namespace App\Services;

// use Codedge\Fpdf\Fpdf\Fpdf;
// use Carbon\Carbon;
// use Illuminate\Support\Facades\Schema;
// use Illuminate\Support\Facades\Log; // Importar Log facade para un uso correcto

// class PDFService
// {
//     /**
//      * Busca el logo en el directorio public/icons.
//      * Se mantiene sin cambios ya que es una funcionalidad básica.
//      */
//     public function getLogoPath(): ?string
//     {
//         $iconsDir = public_path('icons');
        
//         Log::info("Buscando logo en directorio: {$iconsDir}");
        
//         if (!is_dir($iconsDir)) {
//             Log::error("Directorio 'icons' no encontrado en: {$iconsDir}");
//             return null;
//         }
        
//         $logoNames = ['logo.png', 'logo.jpg', 'logo.jpeg', 'brand.png', 'company.png'];
        
//         foreach ($logoNames as $logoName) {
//             $logoPath = $iconsDir . DIRECTORY_SEPARATOR . $logoName;
            
//             if (file_exists($logoPath) && is_readable($logoPath)) {
//                 try {
//                     $imageInfo = @getimagesize($logoPath);
                    
//                     if ($imageInfo !== false && isset($imageInfo[0]) && isset($imageInfo[1]) && $imageInfo[0] > 0 && $imageInfo[1] > 0) {
//                         Log::info("Logo válido encontrado: {$logoPath}");
//                         return $logoPath;
//                     }
//                 } catch (\Exception $e) {
//                     Log::error("Error procesando logo {$logoPath}: " . $e->getMessage());
//                 }
//             }
//         }
        
//         return null;
//     }

//     /**
//      * Convierte texto UTF-8 a ISO-8859-1 para compatibilidad con FPDF.
//      * Es una función técnica y se mantiene sin cambios.
//      */
//     private function convertText(string $text): string
//     {
//         $replacements = [
//             'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
//             'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
//             'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U', '°' => 'o'
//         ];
        
//         $converted = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
        
//         if ($converted === false || empty($converted)) {
//             $converted = strtr($text, $replacements);
//         }
        
//         return $converted ?: $text;
//     }

//     /**
//      * Formatea un precio para ser mostrado en el PDF.
//      * Se mantiene sin cambios.
//      */
//     public function formatPrice(float $price): string
//     {
//         return '$' . number_format($price, 0, ',', '.');
//     }

//     /**
//      * Verifica si una columna existe en una tabla.
//      * Se mantiene sin cambios.
//      */
//     private function columnExists(string $table, string $column): bool
//     {
//         try {
//             return Schema::hasColumn($table, $column);
//         } catch (\Exception $e) {
//             Log::warning("Error verificando columna {$column} en tabla {$table}: " . $e->getMessage());
//             return false;
//         }
//     }

//     /**
//      * Genera un informe PDF con un formato más amigable y moderno.
//      *
//      * @param array $reportData Datos resumidos del informe.
//      * @param array $extractData Datos detallados del extracto.
//      * @param string $period Período del informe (ej. "Enero 2023").
//      * @param bool $useLogo Indica si se debe incluir el logo.
//      * @return string El contenido del PDF como una cadena binaria.
//      * @throws \Exception Si la clase FPDF no está disponible o hay errores en la generación.
//      */
//     public function generatePDFReport(array $reportData, array $extractData, string $period, bool $useLogo = true): string
//     {
//         try {
//             Log::info('Generando PDF Report con formato más amigable.');

//             if (!class_exists(Fpdf::class)) {
//                 throw new \Exception('La clase FPDF no está disponible. Asegúrate de que el paquete Codedge/Fpdf esté instalado y configurado correctamente.');
//             }

//             $logoPath = $useLogo ? $this->getLogoPath() : null;
            
//             // Usar orientación horizontal para más espacio
//             $pdf = new ReportPDF($period, $logoPath, 'L'); // 'L' para Landscape (apaisado)
//             $pdf->AliasNbPages(); // Habilitar el alias {nb} para el total de páginas
//             $pdf->AddPage();
            
//             // === RESUMEN GENERAL ===
//             $pdf->sectionTitle("RESUMEN GENERAL");
            
//             // Mostrar resumen en un formato de cuadrícula 2x2 mejorado
//             $pdf->summaryGrid([
//                 "Total Ventas" => $this->formatPrice($reportData['total_ventas']),
//                 "Cantidad Ventas" => (string)$reportData['cantidad_ventas'],
//                 "Ticket Promedio" => $this->formatPrice($reportData['ticket_promedio']),
//                 "Total Domicilios" => (string)$reportData['cantidad_domicilios']
//             ]);
            
//             $pdf->Ln(15); // Menos espacio después del resumen para una apariencia más compacta
            
//             // === PRODUCTOS MÁS VENDIDOS ===
//             $pdf->sectionTitle("PRODUCTOS MÁS VENDIDOS");
            
//             // Definir cabeceras y anchos de columna para la tabla de productos
//             $productHeaders = ["PRODUCTO", "VARIANTE", "CANTIDAD", "ORDENES", "INGRESOS"];
//             $productWidths = [70, 50, 30, 30, 35]; 
            
//             $pdf->createTable($productHeaders, $productWidths);
            
//             if (!empty($reportData['productos_top'])) {
//                 foreach ($reportData['productos_top'] as $p) {
//                     $rowData = [
//                         $p['producto'],
//                         $p['variante'] ?? '-', // Usar guion si la variante está vacía
//                         (string)$p['cantidad_vendida'],
//                         (string)$p['numero_ordenes'],
//                         $this->formatPrice($p['ingresos'])
//                     ];
//                     $pdf->addTableRow($rowData, $productWidths);
//                 }
//             } else {
//                 // Mensaje si no hay datos disponibles para esta sección
//                 $pdf->addTableRow(["No hay datos de productos top disponibles para este período."], [array_sum($productWidths)]);
//             }
//             $pdf->closeTable();
//             $pdf->Ln(15);
            
//             // === MÉTODOS DE PAGO ===
//             $pdf->sectionTitle("MÉTODOS DE PAGO");
            
//             // Definir cabeceras y anchos de columna para la tabla de métodos de pago
//             $paymentHeaders = ["MÉTODO DE PAGO", "CANTIDAD", "TOTAL"];
//             $paymentWidths = [100, 35, 50]; 
            
//             $pdf->createTable($paymentHeaders, $paymentWidths);
            
//             if (!empty($reportData['metodos_pago'])) {
//                 foreach ($reportData['metodos_pago'] as $m) {
//                     $rowData = [
//                         $m['payment_method'],
//                         (string)$m['count'],
//                         $this->formatPrice($m['total'])
//                     ];
//                     $pdf->addTableRow($rowData, $paymentWidths);
//                 }
//             } else {
//                 // Mensaje si no hay datos disponibles para esta sección
//                 $pdf->addTableRow(["No hay datos de métodos de pago disponibles para este período."], [array_sum($paymentWidths)]);
//             }
//             $pdf->closeTable();
//             $pdf->Ln(15);
            
//             // === EXTRACTO DETALLADO - Nueva página (solo si hay datos) ===
//             if (!empty($extractData)) {
//                 $pdf->AddPage();
//                 $pdf->sectionTitle("EXTRACTO DETALLADO");
                
//                 // Definir cabeceras y anchos de columna para la tabla de extracto
//                 $extractHeaders = ["FACTURA", "FECHA", "CLIENTE", "PRODUCTO", "CANT", "SUBTOTAL", "TOTAL", "PAGO"];
//                 $extractWidths = [24, 24, 40, 60, 18, 28, 28, 28]; // Anchos ajustados para un mejor ajuste en la página
                
//                 $pdf->createTable($extractHeaders, $extractWidths);
                
//                 foreach ($extractData as $item) {
//                     $rowData = [
//                         $item['invoice_number'] ?? 'N/A',
//                         $item['invoice_date'] ?? 'N/A',
//                         $item['cliente'] ?? 'N/A',
//                         $item['product_name'] ?? 'N/A',
//                         (string)($item['quantity'] ?? 0),
//                         $this->formatPrice($item['subtotal'] ?? 0),
//                         $this->formatPrice($item['total_amount'] ?? 0),
//                         $item['payment_method'] ?? 'N/A'
//                     ];
//                     $pdf->addTableRow($rowData, $extractWidths);
//                 }
//                 $pdf->closeTable();
//             } else {
//                 Log::info("No hay datos de extracto detallado, se omite la página de extracto.");
//             }
            
//             return $pdf->Output('S'); // 'S' para devolver el PDF como una cadena (binaria)
            
//         } catch (\Exception $e) {
//             Log::error('Error en generatePDFReport: ' . $e->getMessage());
//             throw $e;
//         }
//     }
// }

// /**
//  * CLASE PDF PERSONALIZADA: ReportPDF
//  * Extiende Fpdf para definir un diseño más amigable y moderno para los informes.
//  */
// class ReportPDF extends Fpdf
// {
//     protected $period;
//     protected $logoPath;
//     protected $leftMargin = 15;
//     protected $topMargin = 20;
    
//     protected $tableStartX = 0; // Variable para mantener la posición X de las tablas
//     protected $currentHeaders;  // Para almacenar cabeceras de tabla para saltos de página
//     protected $currentWidths;   // Para almacenar anchos de columna para saltos de página

//     // ESQUEMA DE COLORES AMIGABLE Y MINIMALISTA
//     protected $primaryColor = [255, 255, 255];      // Blanco para fondos principales
//     protected $secondaryColor = [249, 237, 235];    // #F9EDEB - Toque de color suave para acentos
//     protected $accentColor = [220, 180, 175];       // Un tono más oscuro y cálido del acento para contraste sutil
//     protected $lightColor = [255, 255, 255];        // Blanco puro
//     protected $darkColor = [40, 40, 40];            // Gris oscuro casi negro para textos principales
//     protected $midToneColor = [100, 100, 100];      // Gris medio para textos secundarios/discretos
//     protected $borderColor = [230, 230, 230];       // Gris muy claro para bordes sutiles

//     public function __construct(string $period, ?string $logoPath = null, string $orientation = 'L')
//     {
//         parent::__construct($orientation, 'mm', 'A4');
//         $this->period = $period;
//         $this->logoPath = $logoPath;
        
//         $this->SetAutoPageBreak(true, 20); // Habilitar salto de página automático con margen inferior de 20mm
//         $this->SetMargins($this->leftMargin, $this->topMargin, $this->leftMargin); // Establecer márgenes predeterminados
//     }

//     /**
//      * Convierte texto limpio, eliminando caracteres especiales para FPDF.
//      * Se mantiene similar al original para asegurar la compatibilidad.
//      */
//     private function cleanText(string $text): string
//     {
//         $replacements = [
//             'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
//             'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
//             'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U'
//         ];
        
//         $converted = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
//         return $converted !== false ? $converted : strtr($text, $replacements);
//     }

//     /**
//      * Define el contenido del encabezado de cada página.
//      * Modificado para un estilo más limpio y menos "empresarial".
//      */
//     public function Header()
//     {
//         // Fondo blanco limpio para el área del encabezado
//         $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
//         $this->Rect(0, 0, $this->GetPageWidth(), 45, 'F'); // Cubre el área del encabezado con blanco
        
//         // Logo - Posición y tamaño ajustados para ser menos dominante y más integrado
//         if ($this->logoPath && file_exists($this->logoPath)) {
//             try {
//                 $this->Image($this->logoPath, $this->leftMargin + 5, 8, 25, 20); // Más pequeño y un poco a la derecha del margen
//             } catch (\Exception $e) {
//                 Log::error("Error cargando logo en PDF Header: " . $e->getMessage());
//             }
//         }
        
//         // Título principal - "Reporte de Ventas" con un tamaño ligeramente reducido y centrado
//         $this->SetFont('Arial', 'B', 20); 
//         $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]); // Color de texto oscuro
//         $this->SetY(15); // Posición vertical del título
//         $this->Cell(0, 10, 'Reporte de Ventas', 0, 1, 'C'); 

//         // Período - Centrado, con color de acento para un toque suave
//         $this->SetFont('Arial', 'I', 14); // Itálica para un toque más casual
//         $this->SetTextColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]); 
//         $this->Cell(0, 7, $this->cleanText($this->period), 0, 1, 'C');
        
//         // Fecha de Generación - Más discreta, en gris medio
//         $this->SetFont('Arial', '', 10);
//         $this->SetTextColor($this->midToneColor[0], $this->midToneColor[1], $this->midToneColor[2]); 
//         $this->Cell(0, 5, 'Generado: ' . Carbon::now()->format('d/m/Y H:i'), 0, 1, 'C');
        
//         // Línea divisoria sutil para un encabezado más limpio, con color de borde
//         $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
//         $this->SetLineWidth(0.3); // Línea fina
//         $this->Line($this->leftMargin, 43, $this->GetPageWidth() - $this->leftMargin, 43);

//         // Resetear color de texto a oscuro para el contenido principal
//         $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]);
//         $this->Ln(15); // Espacio después del encabezado antes de iniciar el contenido
//     }

//     /**
//      * Define el contenido del pie de página de cada página.
//      * Más discreto, en gris medio.
//      */
//     public function Footer()
//     {
//         $this->SetY(-15); // Posición desde el final de la página
//         $this->SetFont('Arial', 'I', 9); // Fuente itálica, tamaño pequeño
//         $this->SetTextColor($this->midToneColor[0], $this->midToneColor[1], $this->midToneColor[2]); // Gris medio
//         $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C'); // Número de página centrado
//     }

//     /**
//      * Dibuja un título de sección con un estilo limpio y subrayado sutil.
//      * Elimina el fondo de color para una apariencia menos "bloque".
//      */
//     public function sectionTitle(string $title)
//     {
//         $this->SetFont('Arial', 'B', 16); // Negrita, tamaño 16
//         $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]); // Texto oscuro
        
//         $textWidth = $this->GetStringWidth($this->cleanText($title)); // Ancho del texto para el subrayado
//         $lineX = $this->leftMargin; 
//         $lineY = $this->GetY() + 8; // Posición de la línea debajo del texto

//         $this->SetX($this->leftMargin); // Asegurar que el título comience en el margen izquierdo
//         $this->Cell(0, 10, $this->cleanText($title), 0, 1, 'L', false); // Sin fondo, sin borde, alineado a la izquierda

//         // Subrayado sutil con el color de acento
//         $this->SetDrawColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
//         $this->SetLineWidth(0.8); // Línea un poco más gruesa
//         $this->Line($lineX, $lineY, $lineX + $textWidth + 2, $lineY); // Línea ligeramente más larga que el texto
//         $this->SetLineWidth(0.2); // Restablecer el ancho de línea por defecto
        
//         $this->Ln(10); // Espacio después del título de sección
//     }

//     /**
//      * Dibuja una cuadrícula de resumen 2x2 con menos bordes y más espacio.
//      * Las cajas son más ligeras, contribuyendo a un diseño más amigable.
//      */
//     public function summaryGrid(array $data)
//     {
//         $keys = array_keys($data);
//         $values = array_values($data);
        
//         $boxWidth = 120; // Ancho de cada caja
//         $boxHeight = 35; // Altura de cada caja
//         $spacing = 15;   // Espacio entre cajas
        
//         $pageWidth = $this->GetPageWidth();
//         $totalWidth = ($boxWidth * 2) + $spacing;
//         $startX = ($pageWidth - $totalWidth) / 2; // Calcular posición para centrar la cuadrícula
        
//         // Asegurar que no quede pegado a los márgenes si la página es muy estrecha
//         if ($startX < $this->leftMargin) {
//             $startX = $this->leftMargin;
//         }
        
//         $currentY = $this->GetY();
        
//         // Dibujar la primera fila de cajas
//         $this->summaryBox($keys[0], $values[0], $startX, $currentY, $boxWidth, $boxHeight);
//         $this->summaryBox($keys[1], $values[1], $startX + $boxWidth + $spacing, $currentY, $boxWidth, $boxHeight);
        
//         // Dibujar la segunda fila de cajas
//         $this->summaryBox($keys[2], $values[2], $startX, $currentY + $boxHeight + $spacing, $boxWidth, $boxHeight);
//         $this->summaryBox($keys[3], $values[3], $startX + $boxWidth + $spacing, $currentY + $boxHeight + $spacing, $boxWidth, $boxHeight);
        
//         $this->SetY($currentY + ($boxHeight * 2) + ($spacing * 2)); // Posicionar Y para el siguiente contenido
//     }

//     /**
//      * Dibuja una caja de resumen individual con un estilo más limpio y minimalista.
//      * Solo tiene un sutil borde inferior y una línea de acento superior.
//      */
//     private function summaryBox(string $label, string $value, float $x, float $y, float $w, float $h)
//     {        
//         // Fondo blanco principal para la caja
//         $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
//         $this->Rect($x, $y, $w, $h, 'F');
        
//         // Borde sutil inferior con el color de borde predefinido
//         $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
//         $this->SetLineWidth(0.2); // Línea fina
//         $this->Line($x, $y + $h, $x + $w, $y + $h);
        
//         // Línea decorativa superior con el color secundario (toque de color)
//         $this->SetFillColor($this->secondaryColor[0], $this->secondaryColor[1], $this->secondaryColor[2]);
//         $this->Rect($x, $y, $w, 3, 'F'); // Línea de acento más delgada en la parte superior

//         // Etiqueta (nombre del dato) en gris oscuro, centrada
//         $this->SetXY($x, $y + 10); // Posición vertical
//         $this->SetFont('Arial', 'B', 10); // Negrita, tamaño 10
//         $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]); 
//         $this->Cell($w, 6, $this->cleanText($label), 0, 0, 'C'); // Celda centrada

//         // Valor del dato en gris oscuro, más grande y prominente
//         $this->SetXY($x, $y + 18); // Posición vertical
//         $this->SetFont('Arial', 'B', 16); // Negrita, tamaño 16
//         $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]); 
//         $this->Cell($w, 10, $this->cleanText($value), 0, 0, 'C'); // Celda centrada
        
//         // Restablecer configuraciones de dibujo y texto
//         $this->SetDrawColor(0, 0, 0);
//         $this->SetLineWidth(0.2);
//         $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]);
//     }

//     /**
//      * Crea los encabezados de una tabla con un estilo más suave.
//      * Almacena las cabeceras y anchos para el manejo de saltos de página.
//      */
//     public function createTable(array $headers, array $widths)
//     {
//         $this->currentHeaders = $headers; // Guarda las cabeceras para recrear en nueva página
//         $this->currentWidths = $widths;   // Guarda los anchos para recrear en nueva página

//         $totalWidth = array_sum($widths);
//         $pageWidth = $this->GetPageWidth() - ($this->leftMargin * 2);
//         $startX = $this->leftMargin + (($pageWidth - $totalWidth) / 2); // Centrar la tabla
        
//         // Si la tabla es muy ancha, usar todo el ancho disponible
//         if ($totalWidth > $pageWidth * 0.95) {
//             $startX = $this->leftMargin;
//         }
        
//         $this->SetX($startX);
//         $this->tableStartX = $startX; // Guarda la posición X inicial de la tabla
        
//         $this->SetFont('Arial', 'B', 10); // Fuente para cabeceras de tabla
//         $this->SetFillColor($this->secondaryColor[0], $this->secondaryColor[1], $this->secondaryColor[2]); // Fondo de color de acento
//         $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]); // Texto oscuro
//         $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]); // Bordes sutiles
//         $this->SetLineWidth(0.4); // Línea fina para los bordes de la cabecera
        
//         // Dibujar cada celda de la cabecera
//         foreach ($headers as $i => $header) {
//             // El borde '1' dibuja todos los lados de la celda de la cabecera
//             $this->Cell($widths[$i], 12, $this->cleanText($header), 1, 0, 'C', true); 
//         }
//         $this->Ln(); // Salto de línea después de las cabeceras
        
//         // Restablecer configuraciones para las filas de datos (sin fondo, bordes más finos)
//         $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
//         $this->SetLineWidth(0.2);
//         $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]);
//     }

//     /**
//      * Agrega una fila a la tabla con fondo alternado y bordes minimalistas.
//      * Maneja el salto de línea del texto dentro de las celdas (MultiCell).
//      */
//     public function addTableRow(array $data, array $widths)
//     {
//         $startX = isset($this->tableStartX) ? $this->tableStartX : $this->leftMargin;
//         $this->SetFont('Arial', '', 9); // Fuente para el contenido de la tabla
//         $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]);
        
//         $initialY = $this->GetY();
//         $lineHeight = 5; // Altura base de cada línea de texto en MultiCell
//         $cellPadding = 1; // Espacio superior e inferior dentro de la celda
//         $fixedMinRowHeight = 12; // Altura mínima de la fila para asegurar espacio

//         // --- Fase 1: Calcular la altura real de la fila si el contenido se envuelve ---
//         $maxContentHeight = 0;
//         // Se guarda el estado actual de FPDF para poder simular sin alterar el dibujo real
//         $x_backup = $this->x;
//         $y_backup = $this->y;
//         $font_backup = $this->FontFamily;
//         $font_size_backup = $this->FontSizePt;

//         foreach ($data as $i => $cellText) {
//             // Se posiciona para simular el MultiCell y obtener la altura
//             $this->SetXY($startX + array_sum(array_slice($widths, 0, $i)), $initialY);
//             $text = $this->cleanText((string)$cellText);
//             // MultiCell con el último parámetro 'true' para simulación (no dibuja, solo calcula)
//             // Nota: El soporte de 'true' para simulación puede variar según la versión o extensiones de FPDF.
//             // Si la simulación no funciona, esta parte del código necesitará un ajuste.
//             $currentCellHeight = $this->MultiCell($widths[$i], $lineHeight, $text, 0, 'L', false, true); 
//             $maxContentHeight = max($maxContentHeight, $currentCellHeight);
//         }
//         // Restaurar el estado de FPDF después de la simulación
//         $this->SetXY($x_backup, $y_backup);
//         $this->SetFont($font_backup, '', $font_size_backup);
        
//         // La altura final de la fila será la máxima calculada más el doble del padding, o la altura mínima fija
//         $finalRowHeight = max($fixedMinRowHeight, $maxContentHeight + ($cellPadding * 2)); 

//         // --- Fase 2: Manejar el salto de página ANTES de dibujar la fila ---
//         if ($this->GetY() + $finalRowHeight > $this->PageBreakTrigger) {
//             $this->AddPage();
//             // Si hay un salto de página, se recrean las cabeceras de la tabla
//             if (isset($this->currentHeaders) && isset($this->currentWidths)) {
//                 $this->createTable($this->currentHeaders, $this->currentWidths); 
//             }
//             $initialY = $this->GetY(); // Actualizar la posición Y inicial para la nueva página
//         }
        
//         // --- Fase 3: Dibujar el fondo y el contenido de la fila ---
//         static $rowNumber = 0;
//         $rowNumber++;
        
//         // Definir color de fondo alterno para las filas
//         $fillColor = ($rowNumber % 2 == 0) ? [255, 255, 255] : [248, 248, 248]; // Blanco o gris muy claro
//         $this->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
        
//         // Dibujar el rectángulo de fondo para toda la fila
//         $this->Rect($startX, $initialY, array_sum($widths), $finalRowHeight, 'F');
        
//         $currentCellX = $startX;
//         foreach ($data as $i => $cellText) {
//             $text = $this->cleanText((string)$cellText);
            
//             // Lógica de alineación inteligente: derecha para números, centro para cantidades, izquierda para texto
//             $align = 'L'; // Alineación por defecto a la izquierda
//             // Si es un precio o un número (excluyendo las primeras columnas que suelen ser texto)
//             if (preg_match('/^\$[\d.,]+$/', $text) || (is_numeric(str_replace(['$', ',', '.'], '', $text)) && !empty($text) && !in_array($i, [0,1,2,3]))) { 
//                 $align = 'R'; // Alineación a la derecha
//             }
//             // Columnas específicas que suelen ser cantidades (ej. en tabla de Productos o Extracto)
//             if ( ($i == 2 || $i == 3) && count($widths) == 5 ) { // Para tabla de Productos: Cantidad, Ordenes
//                  $align = 'C';
//             }
//             if ( $i == 1 && count($widths) == 3 ) { // Para tabla de Métodos de Pago: Cantidad
//                  $align = 'C';
//             }
//             if ( $i == 4 && count($widths) == 8 ) { // Para tabla de Extracto: Cant
//                 $align = 'C';
//             }

//             // Posicionar y dibujar el contenido de la celda
//             // Se ajusta la posición Y para centrar visualmente el texto MultiCell
//             $this->SetXY($currentCellX, $initialY + ($finalRowHeight / 2) - ($lineHeight / 2)); 
//             $this->MultiCell($widths[$i], $lineHeight, $text, 0, $align, false); // Sin borde individual para la celda
            
//             $currentCellX += $widths[$i]; // Avanzar X para la siguiente celda
//         }
        
//         // Mover el puntero Y para la siguiente fila, basándose en la altura final de esta fila
//         $this->SetY($initialY + $finalRowHeight); 

//         // Dibujar solo el borde horizontal inferior para toda la fila
//         $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
//         $this->SetLineWidth(0.2);
//         $this->Line($this->tableStartX, $initialY + $finalRowHeight, $this->tableStartX + array_sum($widths), $initialY + $finalRowHeight);

//         // Restablecer el color de dibujo a negro por defecto
//         $this->SetDrawColor(0, 0, 0); 
//     }

//     /**
//      * Cierra la tabla, añadiendo un pequeño espacio después.
//      * Se mantiene sin cambios.
//      */
//     public function closeTable()
//     {
//         $this->Ln(10);
//     }
// }

