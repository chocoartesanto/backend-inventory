<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InsumoController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController; // ← NUEVA LÍNEA AGREGADA
use App\Http\Controllers\Api\ShirtScheduleController; // ← NUEVA LÍNEA AGREGADA
use App\Http\Controllers\Api\DomiciliarioController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\QueryPurchaseController;
use App\Http\Controllers\Api\ExtractController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\PDFController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Ruta raíz de la API
Route::get('/', function () {
    return response()->json([
        'message' => '¡Bienvenido a la API de Inventario de Heladería!',
        'version' => '1.0.0',
        'status' => 'active'
    ]);
});

// Ruta de health check
Route::get('/health', function () {
    return response()->json(['status' => 'healthy']);
});

// Grupo de rutas v1
Route::prefix('v1')->group(function () {
    
    // Rutas de autenticación (no requieren autenticación)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/token', [AuthController::class, 'login']); // Para compatibilidad con FastAPI
    });
    
    // Rutas protegidas (requieren autenticación)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Rutas de autenticación para usuarios autenticados
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'me']);
        });
        
        // Rutas de usuarios (solo para superusuarios)
        Route::prefix('admin/users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
            Route::patch('/{id}/activate', [UserController::class, 'activate']);
            Route::delete('/{id}/permanent', [UserController::class, 'permanentDelete']);
        });
        
        // ===== RUTAS DE CATEGORÍAS =====
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::get('/{id}', [CategoryController::class, 'show']);
            Route::put('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });
        
        // ===== RUTAS DE INSUMOS =====
        Route::prefix('insumos')->group(function () {
            Route::get('/', [InsumoController::class, 'index']);
            Route::post('/', [InsumoController::class, 'store']);
            Route::get('/{id}', [InsumoController::class, 'show']);
            Route::put('/{id}', [InsumoController::class, 'update']);
            Route::delete('/{id}', [InsumoController::class, 'destroy']);
        });
        
        // ===== RUTAS DE PRODUCTOS =====
        Route::prefix('/products')->group(function () {
            // Obtener todos los productos (con filtros opcionales)
            Route::get('/', [ProductController::class, 'index']);
            
            // Crear un nuevo producto con su receta
            Route::post('/', [ProductController::class, 'store']);
            
            // Obtener un producto específico por ID
            Route::get('/{id}', [ProductController::class, 'show']);
            
            // Actualizar un producto existente con su receta
            Route::put('/{id}', [ProductController::class, 'update']);
            
            // Eliminar un producto (soft delete)
            Route::delete('/{id}', [ProductController::class, 'destroy']);
        });
        
        // ===== RUTAS DE SERVICIOS ===== ← NUEVA SECCIÓN
        Route::prefix('services')->group(function () {
            // Obtener stock disponible de productos basado en insumos
            Route::get('/stock', [StockController::class, 'getProductStock']);
        });


    
              // ===== RUTAS DE PROGRAMACIÓN DE CAMISETAS ===== ← NUEVA SECCIÓN
              Route::prefix('/services/shirt-schedule')->group(function () {
                // Obtener la programación completa de camisetas
                Route::get('/', [ShirtScheduleController::class, 'index']);
                
                // Guardar/actualizar la programación completa de camisetas
                Route::post('/', [ShirtScheduleController::class, 'store']);
                
                // Actualizar el color de un día específico
                Route::patch('/{day}', [ShirtScheduleController::class, 'updateDay']);
                
                // Limpiar duplicados manualmente (útil para mantenimiento)
                Route::delete('/duplicates', [ShirtScheduleController::class, 'cleanDuplicates']);
            });

            // ===== RUTAS DE DOMICILIARIOS =====
            Route::prefix('/users/services/domiciliarios')->group(function () {
                Route::get('/', [DomiciliarioController::class, 'index']);
                Route::post('/', [DomiciliarioController::class, 'store']);
                Route::get('/{id}', [DomiciliarioController::class, 'show']);
                Route::put('/{id}', [DomiciliarioController::class, 'update']);
                Route::delete('/{id}', [DomiciliarioController::class, 'destroy']);
            });

            // ===== RUTAS DE COMPRAS =====
            // Route::prefix('/users/services/purchases')->group(function () {
            //     Route::get('/', [PurchaseController::class, 'index']);
            //     Route::post('/', [PurchaseController::class, 'store']);
            //     Route::get('/{id}', [PurchaseController::class, 'show']);
            // });

            Route::prefix('services')->group(function () {
        
                // Validar disponibilidad de insumos antes de crear compra
                Route::post('/purchases/validate', [App\Http\Controllers\Api\PurchaseController::class, 'validatePurchase']);
                
                // Crear nueva compra/factura
                Route::post('/purchases', [App\Http\Controllers\Api\PurchaseController::class, 'store']);
                
                // Obtener compra por número de factura
                Route::get('/purchases/{invoice_number}', [App\Http\Controllers\Api\PurchaseController::class, 'show'])
                    ->where('invoice_number', '[A-Za-z0-9\-]+');
                
                // Obtener compras por rango de fechas
                Route::get('/purchases/date-range', [App\Http\Controllers\Api\PurchaseController::class, 'getByDateRange']);
                
                // Obtener resumen de ventas por período
                Route::get('/purchases/summary/{period}', [App\Http\Controllers\Api\PurchaseController::class, 'getSalesSummary'])
                    ->where('period', 'today|week|month|year');
                
                // Cancelar una compra/factura
                Route::patch('/purchases/{invoice_number}/cancel', [App\Http\Controllers\Api\PurchaseController::class, 'cancel'])
                    ->where('invoice_number', '[A-Za-z0-9\-]+');
                
                // Rutas adicionales útiles para el sistema
                
                // Obtener todas las compras (con paginación)
                Route::get('/purchases', [App\Http\Controllers\Api\PurchaseController::class, 'index']);
                
                // Obtener compras por vendedor
                Route::get('/purchases/seller/{username}', [App\Http\Controllers\Api\PurchaseController::class, 'getBySeller']);
                
                // Obtener compras por cliente
                Route::get('/purchases/client/{client_name}', [App\Http\Controllers\Api\PurchaseController::class, 'getByClient']);
                
                // Obtener estadísticas de ventas detalladas
                Route::get('/purchases/statistics', [App\Http\Controllers\Api\PurchaseController::class, 'getStatistics']);
                
                // Exportar datos de ventas
                Route::get('/purchases/export', [App\Http\Controllers\Api\PurchaseController::class, 'export']);
                
                // Buscar compras por múltiples criterios
                Route::post('/purchases/search', [App\Http\Controllers\Api\PurchaseController::class, 'search']);
            });
            



            // Rutas del Dashboard - Exactamente igual que el endpoint de FastAPI
            Route::prefix('/services')->group(function () {
                
                // Dashboard routes - mantiene la misma estructura que FastAPI
                Route::prefix('dashboard')->group(function () {
                    // Endpoint principal igual que FastAPI: @router_dashboard.get("/summary") 
                    Route::get('/summary', [DashboardController::class, 'getDashboardSummary']);
                    
                    // Endpoints adicionales para funcionalidades específicas
                    Route::get('/sales-summary', [DashboardController::class, 'getSalesSummary']);
                    Route::get('/stock-summary', [DashboardController::class, 'getStockSummary']);
                    Route::get('/low-stock', [DashboardController::class, 'getLowStockProducts']);
                });
                
            });
   
            // Rutas de purchases que ya existen en tu FastAPI
            Route::prefix('/services')->group(function () {
                Route::prefix('purchases')->group(function () {
                    Route::get('/', [QueryPurchaseController::class, 'index']);
                    Route::get('/client/{client_name}', [QueryPurchaseController::class, 'getByClient']);
                    Route::get('/statistics', [QueryPurchaseController::class, 'getStatistics']);
                    Route::get('/summary/{period}', [QueryPurchaseController::class, 'getSalesSummary']);
                });
            });


            Route::prefix('/extracts')->group(function () {
    

    // Extracto mensual: GET /api/v1/extracts/monthly/{year}/{month}?page=1&page_size=50
    Route::get('/monthly/{year}/{month}', [ExtractController::class, 'getMonthlyExtract'])
                ->where(['year' => '[0-9]+', 'month' => '[0-9]+'])
                ->name('extracts.monthly');
            
            // Extracto diario: GET /api/v1/extracts/daily/{date}?page=1&page_size=50
            Route::get('/daily/{date}', [ExtractController::class, 'getDailyExtract'])
                ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
                ->name('extracts.daily');
            
            // Extracto por rango de fechas: GET /api/v1/extracts/range?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD&page=1&page_size=50
            Route::get('/range', [ExtractController::class, 'getDateRangeExtract'])
                ->name('extracts.range');

            // RUTAS PARA GENERAR PDFs
            
            // PDF mensual: GET /api/v1/extracts/monthly/{year}/{month}/pdf
            Route::get('/monthly/{year}/{month}/pdf', [ExtractController::class, 'generateMonthlyPdf'])
                ->where(['year' => '[0-9]+', 'month' => '[0-9]+'])
                ->name('extracts.monthly.pdf');
            
            // PDF diario: GET /api/v1/extracts/daily/{date}/pdf
            Route::get('/daily/{date}/pdf', [ExtractController::class, 'generateDailyPdf'])
                ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
                ->name('extracts.daily.pdf');
            
            // PDF por rango: GET /api/v1/extracts/range/pdf?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
            Route::get('/range/pdf', [ExtractController::class, 'generateRangePdf'])
                ->name('extracts.range.pdf');
            });

 

            // http://127.0.0.1:8053/api/v1/services/extracts/monthly/2025/7?page=1&page_size=50 

            // Si necesitas que coincida exactamente con la URL que mencionaste:
            // GET /api/v1/services/extracts/monthly/2025/7?page=1&page_size=50
            Route::prefix('/services/extracts')->group(function () {
                Route::get('/monthly/{year}/{month}', [ExtractController::class, 'getMonthlyExtract'])
                    ->where(['year' => '[0-9]+', 'month' => '[0-9]+']);
                Route::get('/daily/{date}', [ExtractController::class, 'getDailyExtract'])
                    ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
                Route::get('/range', [ExtractController::class, 'getDateRangeExtract']);
                
                // PDFs bajo services también
                Route::get('/monthly/{year}/{month}/pdf', [ExtractController::class, 'generateMonthlyPdf'])
                    ->where(['year' => '[0-9]+', 'month' => '[0-9]+']);
                Route::get('/daily/{date}/pdf', [ExtractController::class, 'generateDailyPdf'])
                    ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
                Route::get('/range/pdf', [ExtractController::class, 'generateRangePdf']);
            });
        


            Route::prefix('/services/statistics')->group(function () {
                // Estadísticas generales de la aplicación
                Route::get('/', [StatisticsController::class, 'index']);
                
                // Estadísticas de ventas por tiempo
                Route::get('/ventas-por-tiempo/{time_range}', [StatisticsController::class, 'getSalesByTime']);
                
                // Productos más vendidos
                Route::get('/productos-top', [StatisticsController::class, 'getTopProducts']);
                
                // Métricas de entrega y servicio
                Route::get('/metricas-entrega', [StatisticsController::class, 'getDeliveryMetrics']);
                
                // Resumen de ventas por fecha
                Route::get('/sales-summary-by-date', [StatisticsController::class, 'getSalesSummaryByDate']);
            });




            Route::prefix('/services/pdf')->group(function () {
                // Generar reporte PDF desde datos estructurados
                Route::post('/generate-report', [PDFController::class, 'generateReport']);
                
                // Verificar disponibilidad del logo
                Route::get('/check-logo', [PDFController::class, 'checkLogo']);
                
                // Generar reporte de muestra (útil para testing)
                Route::get('/sample-report', [PDFController::class, 'generateSampleReport']);
            });


        // Aquí puedes agregar más rutas protegidas en el futuro...
        // Route::prefix('sales')->group(function () {
        //     // Rutas de ventas
        // });
        
        // Route::prefix('purchases')->group(function () {
        //     // Rutas de compras
        // });
        
    });
});

// Ruta para manejar rutas no encontradas en la API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint no encontrado'
    ], 404);
});

// Route::prefix('v1')->group(function () {
//     // Rutas de productos
//     Route::get('/cors-debug', [ProductController::class, 'corsDebug']);
    
//     // Otras rutas existentes...
//     Route::get('/products', [ProductController::class, 'index']);
//     Route::post('/products', [ProductController::class, 'store']);
//     // ... otras rutas de productos
// });