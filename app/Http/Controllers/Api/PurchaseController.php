<?php
namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Insumo;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    /**
     * Valida si hay suficientes insumos para crear una compra/factura.
     * NO crea la compra, solo verifica la disponibilidad.
     */
    public function validatePurchase(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.product_name' => 'required|string',
            'products.*.product_variant' => 'nullable|string',
            'products.*.quantity' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_valid' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $validationResult = $this->validateInsumosAvailability($request->products);
            
            if ($validationResult['is_valid']) {
                return response()->json([
                    'is_valid' => true,
                    'message' => 'Todos los insumos están disponibles'
                ]);
            } else {
                return response()->json([
                    'is_valid' => false,
                    'message' => 'No hay suficientes insumos',
                    'errors' => $validationResult['errors']
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'is_valid' => false,
                'message' => 'Error al validar la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida que haya suficientes insumos disponibles para todos los productos
     * CORREGIDO: Usa los nombres correctos de columnas
     */
    public function validateInsumosAvailability($productos): array
    {
        try {
            Log::info("=== INICIANDO VALIDACIÓN DE INSUMOS ===");
            Log::info("DEBUG - Validando disponibilidad de insumos para " . count($productos) . " productos...");
            
            $errors = [];
            $isValid = true;

            foreach ($productos as $index => $producto) {
                Log::info("--- Validando producto #{$index} ---");
                $nombreProducto = $producto['product_name'];
                $cantidadSolicitada = $producto['quantity'];
                Log::info("Producto: {$nombreProducto} (Cantidad: {$cantidadSolicitada})");

                // Extraer el nombre base del producto
                $productNameParts = explode(' - ', $nombreProducto);
                $baseProductName = $productNameParts[0];
                Log::info("Nombre base extraído: '{$baseProductName}'");

                // Buscar el producto en la base de datos
                $productQuery = Product::where('nombre_producto', $baseProductName);
                if (isset($producto['product_variant']) && $producto['product_variant']) {
                    $productQuery->where('variante', $producto['product_variant']);
                    Log::info("Buscando con variante: '{$producto['product_variant']}'");
                }
                
                $productoDB = $productQuery->first();

                if (!$productoDB) {
                    Log::error("ERROR - Producto no encontrado en la base de datos: {$nombreProducto}");
                    
                    // Mostrar productos disponibles para debug
                    $availableProducts = Product::select('id', 'nombre_producto', 'variante')->get();
                    Log::info("Productos disponibles en BD:");
                    foreach ($availableProducts as $ap) {
                        Log::info("  ID: {$ap->id} - '{$ap->nombre_producto}' - Variante: '{$ap->variante}'");
                    }
                    
                    $errors[] = [
                        'product_name' => $nombreProducto,
                        'error' => 'Producto no encontrado'
                    ];
                    $isValid = false;
                    continue;
                }

                Log::info("✓ Producto encontrado - ID: {$productoDB->id}, Nombre: '{$productoDB->nombre_producto}', Variante: '{$productoDB->variante}'");

                // CORREGIDO: Obtener recetas de insumos con nombres de columnas correctos
                $recipes = DB::table('product_recipes as pr')
                    ->join('insumos as i', 'pr.insumo_id', '=', 'i.id')
                    ->where('pr.product_id', $productoDB->id)
                    ->select([
                        'pr.cantidad',
                        'i.id as insumo_id',
                        'i.nombre_insumo as insumo_name',  // CORREGIDO: nombre_insumo en lugar de nombre
                        'i.cantidad_unitaria as insumo_stock',
                        'i.cantidad_utilizada as insumo_utilizada'  // AGREGADO: cantidad utilizada
                    ])
                    ->get();

                Log::info("Recetas encontradas: " . $recipes->count());

                if ($recipes->isEmpty()) {
                    Log::error("ERROR - No se encontraron recetas para el producto: {$nombreProducto}");
                    
                    // Mostrar todas las recetas disponibles
                    $allRecipes = DB::table('product_recipes as pr')
                        ->join('products as p', 'pr.product_id', '=', 'p.id')
                        ->join('insumos as i', 'pr.insumo_id', '=', 'i.id')
                        ->select('pr.product_id', 'p.nombre_producto', 'pr.insumo_id', 'i.nombre_insumo', 'pr.cantidad')
                        ->get();
                    
                    Log::info("Todas las recetas en BD:");
                    foreach ($allRecipes as $recipe) {
                        Log::info("  Producto ID: {$recipe->product_id} ({$recipe->nombre_producto}) -> Insumo: {$recipe->nombre_insumo} (Cantidad: {$recipe->cantidad})");
                    }
                    
                    $errors[] = [
                        'product_name' => $nombreProducto,
                        'error' => 'No se encontraron recetas para este producto'
                    ];
                    $isValid = false;
                    continue;
                }

                // Verificar disponibilidad de cada insumo
                foreach ($recipes as $recipeIndex => $recipe) {
                    Log::info("  -- Validando insumo #{$recipeIndex} --");
                    $nombreInsumo = $recipe->insumo_name;
                    $cantidadPorUnidad = $recipe->cantidad;
                    $stockTotal = $recipe->insumo_stock;
                    $stockUtilizado = $recipe->insumo_utilizada;
                    
                    // CORREGIDO: Calcular stock disponible correctamente
                    $stockDisponible = $stockTotal - $stockUtilizado;
                    $cantidadNecesaria = $cantidadPorUnidad * $cantidadSolicitada;
                    
                    Log::info("  Insumo: {$nombreInsumo}");
                    Log::info("  Stock total: {$stockTotal}");
                    Log::info("  Stock utilizado: {$stockUtilizado}");
                    Log::info("  Stock disponible: {$stockDisponible}");
                    Log::info("  Cantidad por unidad: {$cantidadPorUnidad}");
                    Log::info("  Cantidad necesaria: {$cantidadNecesaria}");

                    if ($stockDisponible < $cantidadNecesaria) {
                        Log::error("  ❌ STOCK INSUFICIENTE - Insumo: {$nombreInsumo}, Necesario: {$cantidadNecesaria}, Disponible: {$stockDisponible}");
                        $errors[] = [
                            'product_name' => $nombreProducto,
                            'insumo_name' => $nombreInsumo,
                            'required' => $cantidadNecesaria,
                            'available' => $stockDisponible,
                            'unit' => 'unidad(es)'
                        ];
                        $isValid = false;
                    } else {
                        Log::info("  ✓ Stock suficiente para {$nombreInsumo}");
                    }
                }
            }

            Log::info("=== VALIDACIÓN COMPLETADA ===");
            Log::info("Resultado: " . ($isValid ? 'VÁLIDO' : 'INVÁLIDO'));
            if (!$isValid) {
                Log::info("Errores encontrados: " . count($errors));
            }

            return [
                'is_valid' => $isValid,
                'errors' => $errors
            ];

        } catch (\Throwable $e) {
            Log::error("ERROR CRÍTICO - Excepción en validación de insumos: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return [
                'is_valid' => false,
                'errors' => [['error' => $e->getMessage()]]
            ];
        }
    }

    /**
     * CORREGIDO: Actualiza el stock de insumos cuando se vende un producto
     */
    private function updateProductStock(string $productName, ?string $productVariant, float $quantity): void
    {
        try {
            Log::info("=== INICIANDO ACTUALIZACIÓN DE STOCK ===");
            Log::info("Producto: {$productName}" . ($productVariant ? " (variante: {$productVariant})" : "") . " - Cantidad: {$quantity}");

            // Buscar el producto primero
            $productNameParts = explode(' - ', $productName);
            $baseProductName = $productNameParts[0];
            
            Log::info("DEBUG - Nombre base del producto: {$baseProductName}");

            $productQuery = Product::where('nombre_producto', $baseProductName);
            if ($productVariant) {
                $productQuery->where('variante', $productVariant);
            }

            $product = $productQuery->first();

            if (!$product) {
                Log::error("CRÍTICO - Producto no encontrado: {$baseProductName}" . ($productVariant ? " (variante: {$productVariant})" : ""));
                
                // Mostrar todos los productos disponibles para debug
                $allProducts = Product::select('id', 'nombre_producto', 'variante')->get();
                Log::info("DEBUG - Productos disponibles en BD:");
                foreach ($allProducts as $p) {
                    Log::info("  ID: {$p->id} - Nombre: '{$p->nombre_producto}' - Variante: '{$p->variante}'");
                }
                
                throw new \Exception("Producto no encontrado: {$baseProductName}");
            }

            Log::info("✓ Producto encontrado - ID: {$product->id}, Nombre: '{$product->nombre_producto}', Variante: '{$product->variante}'");

            // DEBUGGING: Verificar las recetas existentes
            Log::info("DEBUG - Buscando recetas para product_id: {$product->id}");
            
            $recipes = DB::table('product_recipes as pr')
                ->join('insumos as i', 'pr.insumo_id', '=', 'i.id')
                ->where('pr.product_id', $product->id)
                ->select([
                    'pr.cantidad',
                    'i.id as insumo_id',
                    'i.nombre_insumo as insumo_name',
                    'i.cantidad_unitaria as insumo_stock',
                    'i.cantidad_utilizada as insumo_utilizada'
                ])
                ->get();

            Log::info("DEBUG - Recetas encontradas: " . $recipes->count());

            if ($recipes->isEmpty()) {
                // Mostrar todas las recetas disponibles para debug
                $allRecipes = DB::table('product_recipes as pr')
                    ->join('products as p', 'pr.product_id', '=', 'p.id')
                    ->join('insumos as i', 'pr.insumo_id', '=', 'i.id')
                    ->select('pr.product_id', 'p.nombre_producto', 'pr.insumo_id', 'i.nombre_insumo', 'pr.cantidad')
                    ->get();
                
                Log::info("DEBUG - Todas las recetas en BD:");
                foreach ($allRecipes as $recipe) {
                    Log::info("  Producto ID: {$recipe->product_id} ({$recipe->nombre_producto}) -> Insumo ID: {$recipe->insumo_id} ({$recipe->nombre_insumo}) - Cantidad: {$recipe->cantidad}");
                }
                
                throw new \Exception("No se encontraron recetas para el producto: {$baseProductName}");
            }

            // Actualizar stock de cada insumo según la receta
            foreach ($recipes as $recipe) {
                Log::info("--- Procesando insumo ---");
                Log::info("Insumo ID: {$recipe->insumo_id}");
                Log::info("Nombre: {$recipe->insumo_name}");
                Log::info("Cantidad por unidad de producto: {$recipe->cantidad}");
                Log::info("Stock total del insumo: {$recipe->insumo_stock}");
                Log::info("Cantidad ya utilizada: {$recipe->insumo_utilizada}");
                
                $insumoId = $recipe->insumo_id;
                $insumoName = $recipe->insumo_name;
                $cantidadPorUnidad = $recipe->cantidad;
                
                $cantidadAReducir = $cantidadPorUnidad * $quantity;
                
                Log::info("Cantidad total a reducir: {$cantidadAReducir} ({$cantidadPorUnidad} x {$quantity})");
                
                // Obtener el insumo fresco de la BD para asegurar datos actuales
                $insumo = Insumo::find($insumoId);
                
                if (!$insumo) {
                    Log::error("CRÍTICO - Insumo no encontrado con ID: {$insumoId}");
                    continue;
                }
                
                Log::info("Insumo antes de actualizar:");
                Log::info("  cantidad_unitaria: {$insumo->cantidad_unitaria}");
                Log::info("  cantidad_utilizada: {$insumo->cantidad_utilizada}");
                
                $cantidadUtilizadaAnterior = $insumo->cantidad_utilizada;
                $nuevaCantidadUtilizada = $cantidadUtilizadaAnterior + $cantidadAReducir;
                
                // Verificar que no exceda el stock total
                if ($nuevaCantidadUtilizada > $insumo->cantidad_unitaria) {
                    $disponible = $insumo->cantidad_unitaria - $cantidadUtilizadaAnterior;
                    Log::error("ERROR - Stock insuficiente para insumo: {$insumoName}");
                    Log::error("  Necesario: {$cantidadAReducir}, Disponible: {$disponible}");
                    throw new \Exception("Stock insuficiente para el insumo {$insumoName}. Necesario: {$cantidadAReducir}, Disponible: {$disponible}");
                }
                
                // REALIZAR LA ACTUALIZACIÓN CON VERIFICACIÓN EXPLÍCITA
                Log::info("Intentando actualizar insumo ID: {$insumoId}");
                
                // Método 1: Usar update directo en la query
                $affectedRows = DB::table('insumos')
                    ->where('id', $insumoId)
                    ->update(['cantidad_utilizada' => $nuevaCantidadUtilizada]);
                
                Log::info("Filas afectadas por update directo: {$affectedRows}");
                
                // Método 2: También intentar con el modelo como backup
                $updateResult = $insumo->update(['cantidad_utilizada' => $nuevaCantidadUtilizada]);
                Log::info("Resultado de update() del modelo: " . ($updateResult ? 'EXITOSO' : 'FALLÓ'));
                
                // Forzar refresh del modelo desde la BD
                $insumo->refresh();
                
                // Verificar que se actualizó correctamente
                $insumoActualizado = Insumo::find($insumoId);
                Log::info("Insumo después de actualizar:");
                Log::info("  cantidad_unitaria: {$insumoActualizado->cantidad_unitaria}");
                Log::info("  cantidad_utilizada: {$insumoActualizado->cantidad_utilizada}");
                
                // VERIFICACIÓN CRÍTICA
                if ($insumoActualizado->cantidad_utilizada != $nuevaCantidadUtilizada) {
                    Log::error("CRÍTICO: La actualización no se aplicó correctamente!");
                    Log::error("  Esperado: {$nuevaCantidadUtilizada}");
                    Log::error("  Actual: {$insumoActualizado->cantidad_utilizada}");
                    
                    // Intentar una actualización más agresiva
                    Log::info("Intentando actualización con save()...");
                    $insumoActualizado->cantidad_utilizada = $nuevaCantidadUtilizada;
                    $saveResult = $insumoActualizado->save();
                    Log::info("Resultado de save(): " . ($saveResult ? 'EXITOSO' : 'FALLÓ'));
                    
                    // Verificar una vez más
                    $insumoFinal = Insumo::find($insumoId);
                    Log::info("Verificación final - cantidad_utilizada: {$insumoFinal->cantidad_utilizada}");
                    
                    if ($insumoFinal->cantidad_utilizada != $nuevaCantidadUtilizada) {
                        throw new \Exception("No se pudo actualizar el stock del insumo {$insumoName}. Posible problema con el modelo o la base de datos.");
                    }
                }
                
                $stockDisponible = $insumoActualizado->cantidad_unitaria - $insumoActualizado->cantidad_utilizada;
                
                Log::info("✓ STOCK ACTUALIZADO - Insumo: {$insumoName} (ID: {$insumoId})");
                Log::info("  Utilizada anterior: {$cantidadUtilizadaAnterior}");
                Log::info("  Cantidad agregada: {$cantidadAReducir}");
                Log::info("  Nueva cantidad utilizada: {$insumoActualizado->cantidad_utilizada}");
                Log::info("  Stock disponible: {$stockDisponible}");
            }

            // Actualizar el stock del producto terminado
            $oldProductStock = $product->stock_quantity;
            $newProductStock = $oldProductStock - $quantity;
            
            Log::info("--- Actualizando stock del producto terminado ---");
            Log::info("Stock anterior: {$oldProductStock}");
            Log::info("Cantidad a reducir: {$quantity}");
            Log::info("Nuevo stock: {$newProductStock}");
            
            $productUpdateResult = $product->update(['stock_quantity' => $newProductStock]);
            Log::info("Resultado de actualización del producto: " . ($productUpdateResult ? 'EXITOSO' : 'FALLÓ'));
            
            // Verificar actualización del producto
            $productActualizado = Product::find($product->id);
            Log::info("Stock del producto después de actualizar: {$productActualizado->stock_quantity}");
            
            Log::info("=== ACTUALIZACIÓN DE STOCK COMPLETADA ===");

        } catch (\Exception $e) {
            Log::error("ERROR CRÍTICO actualizando stock: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            throw new \Exception("Error al actualizar stock: " . $e->getMessage());
        }
    }

    /**
     * CORREGIDO: Calcula el stock disponible para todos los productos
     */
    public function calculateProductStock(): array
    {
        try {
            Log::info("DEBUG - Ejecutando cálculo de stock de productos");
            
            // CORREGIDO: Query con nombres de columnas correctos
            $query = "
            SELECT
                p.id AS producto_id,
                p.nombre_producto,
                p.variante,
                p.price AS precio,
                c.nombre_categoria AS categoria_nombre,
                COALESCE(FLOOR(MIN(
                    (i.cantidad_unitaria - i.cantidad_utilizada) / 
                    CASE 
                        WHEN pr.cantidad > 0 THEN pr.cantidad
                        ELSE 1
                    END
                )), 0) AS stock_disponible
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_recipes pr ON p.id = pr.product_id
            LEFT JOIN insumos i ON pr.insumo_id = i.id
            WHERE p.is_active = TRUE
            GROUP BY p.id, p.nombre_producto, p.variante, p.price, c.nombre_categoria
            ORDER BY p.nombre_producto
            ";
            
            $results = DB::select($query);
            
            $stockData = [];
            foreach ($results as $row) {
                $stockInfo = [
                    'producto_id' => $row->producto_id,
                    'nombre_producto' => $row->nombre_producto,
                    'variante' => $row->variante ?? '',
                    'precio' => floatval($row->precio),
                    'categoria_nombre' => $row->categoria_nombre ?? 'Sin categoría',
                    'stock_disponible' => intval($row->stock_disponible),
                    'tipo' => 'producto'
                ];
                
                Log::info("DEBUG - Producto: {$stockInfo['nombre_producto']} - Stock disponible: {$stockInfo['stock_disponible']}");
                $stockData[] = $stockInfo;
            }
            
            return $stockData;
            
        } catch (\Exception $e) {
            Log::error("Error calculando stock de productos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * CORREGIDO: Restaura insumos cuando se cancela una compra
     */
    private function restoreInsumos(PurchaseDetail $detail): void
    {
        try {
            Log::info("Restaurando insumos para: {$detail->product_name}");

            // Buscar el producto primero
            $productNameParts = explode(' - ', $detail->product_name);
            $baseProductName = $productNameParts[0];

            $productQuery = Product::where('nombre_producto', $baseProductName);
            if ($detail->product_variant) {
                $productQuery->where('variante', $detail->product_variant);
            }

            $product = $productQuery->first();

            if (!$product) {
                Log::warning("Producto no encontrado para restaurar insumos: {$detail->product_name}");
                return;
            }

            // CORREGIDO: Buscar las recetas usando nombres de columnas correctos
            $recipes = DB::table('product_recipes as pr')
                ->join('insumos as i', 'pr.insumo_id', '=', 'i.id')
                ->where('pr.product_id', $product->id)
                ->select([
                    'pr.cantidad',
                    'i.id as insumo_id',
                    'i.nombre_insumo as insumo_name'  // CORREGIDO
                ])
                ->get();

            if ($recipes->isEmpty()) {
                Log::warning("No se encontraron recetas para restaurar insumos: {$detail->product_name}");
                return;
            }

            // Restaurar cada insumo según la receta
            foreach ($recipes as $recipe) {
                $insumoId = $recipe->insumo_id;
                $insumoName = $recipe->insumo_name;
                $cantidadPorUnidad = $recipe->cantidad;
                
                $cantidadARestaurar = $cantidadPorUnidad * $detail->quantity;
                
                $insumo = Insumo::find($insumoId);
                
                if ($insumo) {
                    // CORREGIDO: Restaurar reduciendo cantidad_utilizada
                    $cantidadUtilizadaAnterior = $insumo->cantidad_utilizada;
                    $nuevaCantidadUtilizada = max(0, $cantidadUtilizadaAnterior - $cantidadARestaurar);
                    
                    $insumo->update(['cantidad_utilizada' => $nuevaCantidadUtilizada]);
                    
                    Log::info("Insumo restaurado - {$insumoName} (ID: {$insumoId}): " .
                            "Cantidad utilizada anterior: {$cantidadUtilizadaAnterior}, " .
                            "Cantidad restaurada: {$cantidadARestaurar}, " .
                            "Nueva cantidad utilizada: {$nuevaCantidadUtilizada}");
                } else {
                    Log::error("Insumo no encontrado para restaurar: {$insumoName} (ID: {$insumoId})");
                }
            }

            // También restaurar el stock del producto terminado
            $oldProductStock = $product->stock_quantity;
            $newProductStock = $oldProductStock + $detail->quantity;
            
            $product->update(['stock_quantity' => $newProductStock]);
            
            Log::info("Stock de producto restaurado - {$detail->product_name} (ID: {$product->id}): " .
                    "Stock anterior: {$oldProductStock}, " .
                    "Stock nuevo: {$newProductStock}");
            
        } catch (\Exception $e) {
            Log::error("Error restaurando insumos: " . $e->getMessage());
            throw new \Exception("Error al restaurar insumos: " . $e->getMessage());
        }
    }

    // ... resto de métodos sin cambios (store, show, getByDateRange, etc.)
    
    /**
     * Método de prueba para verificar actualización de insumos
     * TEMPORAL - Solo para debugging
     */
    public function testInsumoUpdate(Request $request): JsonResponse
    {
        try {
            $insumoId = $request->input('insumo_id', 1);
            $nuevaCantidad = $request->input('nueva_cantidad', 5.00);
            
            Log::info("=== PRUEBA DE ACTUALIZACIÓN DE INSUMO ===");
            Log::info("Insumo ID: {$insumoId}, Nueva cantidad: {$nuevaCantidad}");
            
            // Obtener insumo actual
            $insumo = Insumo::find($insumoId);
            if (!$insumo) {
                return response()->json(['error' => 'Insumo no encontrado'], 404);
            }
            
            Log::info("Insumo antes - cantidad_utilizada: {$insumo->cantidad_utilizada}");
            
            // Verificar si el campo está en fillable
            $fillableFields = $insumo->getFillable();
            Log::info("Campos fillable: " . implode(', ', $fillableFields));
            Log::info("¿cantidad_utilizada en fillable? " . (in_array('cantidad_utilizada', $fillableFields) ? 'SÍ' : 'NO'));
            
            // Intentar actualización con update()
            $updateResult = $insumo->update(['cantidad_utilizada' => $nuevaCantidad]);
            Log::info("Resultado update(): " . ($updateResult ? 'EXITOSO' : 'FALLÓ'));
            
            // Refrescar y verificar
            $insumo->refresh();
            Log::info("Después de refresh - cantidad_utilizada: {$insumo->cantidad_utilizada}");
            
            // Intentar con save()
            $insumo->cantidad_utilizada = $nuevaCantidad + 1;
            $saveResult = $insumo->save();
            Log::info("Resultado save(): " . ($saveResult ? 'EXITOSO' : 'FALLÓ'));
            
            // Verificar directamente en BD
            $insumoFromDB = DB::table('insumos')->where('id', $insumoId)->first();
            Log::info("Directo de BD - cantidad_utilizada: {$insumoFromDB->cantidad_utilizada}");
            
            return response()->json([
                'success' => true,
                'insumo_antes' => $insumo->cantidad_utilizada,
                'insumo_bd' => $insumoFromDB->cantidad_utilizada,
                'fillable_fields' => $fillableFields
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error en prueba: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crea una nueva compra/factura con todos sus detalles.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // DEBUG: Log de entrada completo
            Log::info("DEBUG - Request completo recibido:", $request->all());
            
            // Validación de datos de entrada
            $validator = Validator::make($request->all(), [
                'invoice_number' => 'required|string|unique:purchases,invoice_number',
                'invoice_date' => 'required|string', // Formato: dd/mm/yyyy
                'invoice_time' => 'required|string', // Formato: hh:mm:ss a.m./p.m.
                'client_name' => 'required|string',
                'seller_username' => 'required|string|exists:users,username',
                'client_phone' => 'nullable|string',
                'has_delivery' => 'boolean',
                'delivery_address' => 'nullable|string',
                'delivery_person' => 'nullable|string',
                'delivery_fee' => 'numeric|min:0',
                'subtotal_products' => 'required|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'amount_paid' => 'required|numeric|min:0',
                'change_returned' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'payment_reference' => 'nullable|string',
                'products' => 'required|array|min:1',
                'products.*.product_name' => 'required|string',
                'products.*.product_variant' => 'nullable|string',
                'products.*.quantity' => 'required|numeric|min:1',
                'products.*.unit_price' => 'required|numeric|min:0',
                'products.*.subtotal' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                Log::error("DEBUG - Validación falló:", $validator->errors()->toArray());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            Log::info("DEBUG - Validación exitosa, iniciando proceso...");

        } catch (\Exception $e) {
            Log::error("ERROR - Excepción en validación inicial: " . $e->getMessage());
            Log::error("ERROR - Stack trace: " . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error en validación inicial: ' . $e->getMessage()
            ], 500);
        }

        // Iniciar transacción
        DB::beginTransaction();

        try {
            // PASO 1: Validar disponibilidad de insumos para todos los productos
            if (!empty($request->products)) {
                Log::info("DEBUG - Validando disponibilidad de insumos para " . count($request->products) . " productos...");
                
                try {
                    $validationResult = $this->validateInsumosAvailability($request->products);
                    Log::info("DEBUG - Resultado de validación:", $validationResult);
                    
                    if (!$validationResult['is_valid']) {
                        $errorMessages = [];
                        foreach ($validationResult['errors'] as $error) {
                            if (isset($error['product_name']) && isset($error['insumo_name'])) {
                                $errorMessages[] = "Producto '{$error['product_name']}': Falta {$error['insumo_name']} " .
                                                 "(necesario: {$error['required']} {$error['unit']}, " .
                                                 "disponible: {$error['available']} {$error['unit']})";
                            } else {
                                $errorMessages[] = $error['error'] ?? 'Error desconocido';
                            }
                        }
                        
                        $errorText = "No hay suficientes insumos para completar la venta:\n" . implode("\n", $errorMessages);
                        Log::error("ERROR DE VALIDACIÓN: {$errorText}");
                        
                        DB::rollback();
                        return response()->json([
                            'status' => 'error',
                            'message' => $errorText
                        ], 400);
                    }
                } catch (\Exception $e) {
                    Log::error("ERROR - Excepción en validación de insumos: " . $e->getMessage());
                    DB::rollback();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Error validando insumos: ' . $e->getMessage()
                    ], 500);
                }
            }

            // PASO 2: Validar que el vendedor existe
            try {
                $seller = User::where('username', $request->seller_username)->first();
                if (!$seller) {
                    DB::rollback();
                    return response()->json([
                        'status' => 'error',
                        'message' => "El vendedor '{$request->seller_username}' no existe"
                    ], 400);
                }
                Log::info("DEBUG - Vendedor encontrado: {$seller->username}");
            } catch (\Exception $e) {
                Log::error("ERROR - Excepción validando vendedor: " . $e->getMessage());
                DB::rollback();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error validando vendedor: ' . $e->getMessage()
                ], 500);
            }

            Log::info("DEBUG - Validación de insumos exitosa, continuando con creación...");

            // PASO 3: Convertir fecha y hora
            try {
                $invoiceDate = $this->parseDate($request->invoice_date);
                $invoiceTime = $this->parseTime($request->invoice_time);
                Log::info("DEBUG - Fecha convertida: {$invoiceDate}, Hora convertida: {$invoiceTime}");
            } catch (\Exception $e) {
                Log::error("ERROR - Error convirtiendo fecha/hora: " . $e->getMessage());
                DB::rollback();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error en formato de fecha/hora: ' . $e->getMessage()
                ], 400);
            }

            // PASO 4: Crear la compra principal
            try {
                $purchase = Purchase::create([
                    'invoice_number' => $request->invoice_number,
                    'invoice_date' => $invoiceDate,
                    'invoice_time' => $invoiceTime,
                    'client_name' => $request->client_name,
                    'seller_username' => $request->seller_username,
                    'client_phone' => $request->client_phone,
                    'has_delivery' => $request->has_delivery ?? false,
                    'delivery_address' => $request->delivery_address,
                    'delivery_person' => $request->delivery_person,
                    'delivery_fee' => $request->delivery_fee ?? 0,
                    'subtotal_products' => $request->subtotal_products,
                    'total_amount' => $request->total_amount,
                    'amount_paid' => $request->amount_paid,
                    'change_returned' => $request->change_returned,
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $request->payment_reference,
                ]);

                Log::info("DEBUG - Compra principal creada con ID: {$purchase->id}");

            } catch (\Exception $e) {
                Log::error("ERROR - Error creando compra principal: " . $e->getMessage());
                DB::rollback();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error creando compra: ' . $e->getMessage()
                ], 500);
            }

            // PASO 5: Insertar los detalles de los productos y actualizar stock
            try {
                if (!empty($request->products)) {
                    foreach ($request->products as $index => $productData) {
                        Log::info("DEBUG - Procesando producto {$index}: " . json_encode($productData));
                        
                        // Crear detalle de compra
                        $detail = PurchaseDetail::create([
                            'purchase_id' => $purchase->id,
                            'product_name' => $productData['product_name'],
                            'product_variant' => $productData['product_variant'] ?? null,
                            'quantity' => $productData['quantity'],
                            'unit_price' => $productData['unit_price'],
                            'subtotal' => $productData['subtotal'],
                        ]);

                        Log::info("DEBUG - Detalle creado con ID: {$detail->id}");

                        // CRÍTICO: Actualizar el stock de insumos
                        try {
                            $this->updateProductStock(
                                $productData['product_name'],
                                $productData['product_variant'] ?? null,
                                $productData['quantity']
                            );
                            Log::info("DEBUG - Stock actualizado para producto: {$productData['product_name']}");
                        } catch (\Exception $e) {
                            Log::error("ERROR - Error actualizando stock para {$productData['product_name']}: " . $e->getMessage());
                            DB::rollback();
                            return response()->json([
                                'status' => 'error',
                                'message' => "Error actualizando stock para {$productData['product_name']}: " . $e->getMessage()
                            ], 500);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("ERROR - Error procesando productos: " . $e->getMessage());
                DB::rollback();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error procesando productos: ' . $e->getMessage()
                ], 500);
            }

            // Confirmar transacción
            DB::commit();
            Log::info("DEBUG - Transacción completada exitosamente");

            return response()->json([
                'purchase_id' => $purchase->id,
                'invoice_number' => $request->invoice_number,
                'status' => 'success',
                'message' => 'Compra registrada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("ERROR - Excepción general en store(): " . $e->getMessage());
            Log::error("ERROR - Stack trace: " . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error inesperado al crear la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    // ... resto de métodos auxiliares como parseDate, parseTime, etc.
    
    /**
     * Convierte fecha del formato dd/mm/yyyy a formato MySQL
     */
    private function parseDate(string $dateString): string
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Formato de fecha inválido: {$dateString}. Use dd/mm/yyyy");
        }
    }

    /**
     * Convierte hora del formato hh:mm:ss a.m./p.m. a formato MySQL
     */
    private function parseTime(string $timeString): string
    {
        try {
            // Reemplazar formato español por inglés
            $timeString = str_replace([' a. m.', ' p. m.'], [' AM', ' PM'], $timeString);
            return Carbon::createFromFormat('g:i:s A', $timeString)->format('H:i:s');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Formato de hora inválido: {$timeString}. Use hh:mm:ss a.m./p.m.");
        }
    }

    /**
     * Obtiene los detalles completos de una compra por su número de factura.
     */
    public function show(string $invoiceNumber): JsonResponse
    {
        try {
            $purchase = Purchase::with(['details', 'seller'])
                ->where('invoice_number', $invoiceNumber)
                ->first();

            if (!$purchase) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Factura {$invoiceNumber} no encontrada"
                ], 404);
            }

            return response()->json([
                'id' => $purchase->id,
                'invoice_number' => $purchase->invoice_number,
                'invoice_date' => $purchase->invoice_date->format('d/m/Y'),
                'invoice_time' => $purchase->invoice_time->format('H:i:s'),
                'client_name' => $purchase->client_name,
                'seller_username' => $purchase->seller_username,
                'seller_email' => $purchase->seller->email ?? null,
                'client_phone' => $purchase->client_phone,
                'has_delivery' => $purchase->has_delivery,
                'delivery_address' => $purchase->delivery_address,
                'delivery_person' => $purchase->delivery_person,
                'delivery_fee' => $purchase->delivery_fee,
                'subtotal_products' => $purchase->subtotal_products,
                'total_amount' => $purchase->total_amount,
                'amount_paid' => $purchase->amount_paid,
                'change_returned' => $purchase->change_returned,
                'payment_method' => $purchase->payment_method,
                'payment_reference' => $purchase->payment_reference,
                'is_cancelled' => $purchase->is_cancelled,
                'cancellation_reason' => $purchase->cancellation_reason,
                'cancelled_at' => $purchase->cancelled_at,
                'products' => $purchase->details
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene todas las compras realizadas en un rango de fechas.
     */
    public function getByDateRange(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $purchases = Purchase::with('details')
                ->whereBetween('invoice_date', [$request->start_date, $request->end_date])
                ->get();

            return response()->json([
                'start_date' => Carbon::parse($request->start_date)->format('d/m/Y'),
                'end_date' => Carbon::parse($request->end_date)->format('d/m/Y'),
                'total_purchases' => $purchases->count(),
                'purchases' => $purchases
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener las compras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene un resumen de ventas para el período especificado.
     */
    public function getSalesSummary(string $period): JsonResponse
    {
        $validPeriods = ['today', 'week', 'month', 'year'];
        
        if (!in_array($period, $validPeriods)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Período inválido. Debe ser uno de: ' . implode(', ', $validPeriods)
            ], 400);
        }

        try {
            $endDate = Carbon::today();
            
            switch ($period) {
                case 'today':
                    $startDate = $endDate->copy();
                    break;
                case 'week':
                    $startDate = $endDate->copy()->subDays(7);
                    break;
                case 'month':
                    $startDate = $endDate->copy()->subDays(30);
                    break;
                case 'year':
                    $startDate = $endDate->copy()->subDays(365);
                    break;
            }

            // Resumen general
            $summary = DB::table('purchases as p')
                ->leftJoin('purchase_details as pd', 'p.id', '=', 'pd.purchase_id')
                ->whereBetween('p.invoice_date', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(DISTINCT p.id) as total_purchases,
                    COUNT(pd.id) as total_items_sold,
                    SUM(pd.quantity) as total_quantity_sold,
                    SUM(p.subtotal_products) as total_products_revenue,
                    SUM(p.delivery_fee) as total_delivery_revenue,
                    SUM(p.total_amount) as total_revenue,
                    AVG(p.total_amount) as average_purchase_value,
                    COUNT(DISTINCT p.client_name) as unique_clients,
                    COUNT(DISTINCT CASE WHEN p.has_delivery THEN p.id END) as deliveries_count
                ')
                ->first();

            // Métodos de pago más usados
            $paymentMethods = DB::table('purchases')
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->selectRaw('payment_method, COUNT(*) as count, SUM(total_amount) as total')
                ->groupBy('payment_method')
                ->orderByDesc('count')
                ->get();

            // Productos más vendidos
            $topProducts = DB::table('purchase_details as pd')
                ->join('purchases as p', 'pd.purchase_id', '=', 'p.id')
                ->whereBetween('p.invoice_date', [$startDate, $endDate])
                ->selectRaw('
                    pd.product_name,
                    pd.product_variant,
                    SUM(pd.quantity) as total_quantity,
                    SUM(pd.subtotal) as total_revenue,
                    COUNT(DISTINCT p.id) as times_sold
                ')
                ->groupBy('pd.product_name', 'pd.product_variant')
                ->orderByDesc('total_quantity')
                ->limit(10)
                ->get();

            return response()->json([
                'period' => $period,
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
                'summary' => $summary,
                'payment_methods' => $paymentMethods,
                'top_products' => $topProducts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener el resumen de ventas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancela una compra y restaura el stock de los productos.
     */
    public function cancel(Request $request, string $invoiceNumber): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        DB::beginTransaction();

        try {
            $purchase = Purchase::with('details')->where('invoice_number', $invoiceNumber)->first();
            
            if (!$purchase) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No se encontró la factura {$invoiceNumber}"
                ], 404);
            }

            // Restaurar insumos para cada producto
            foreach ($purchase->details as $detail) {
                $this->restoreInsumos($detail);
            }

            // Marcar la compra como cancelada
            $purchase->update([
                'is_cancelled' => true,
                'cancellation_reason' => $request->reason,
                'cancelled_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Factura {$invoiceNumber} cancelada exitosamente",
                'insumos_restored' => $purchase->details->count()
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al cancelar la compra: ' . $e->getMessage()
            ], 500);
        }
    }
}