<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Category;
use App\Models\Insumo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\ProductRequest;

class ProductController extends Controller
{
    /**
     * Obtener lista de productos con filtros
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Verificar si la tabla product_recipes existe
            $tableExists = Schema::hasTable('product_recipes');
            
            if ($tableExists) {
                // Si existe, cargar con relaciones completas
                $query = Product::with(['category', 'user', 'recipes.insumo']);
            } else {
                // Si no existe, cargar solo sin recipes
                $query = Product::with(['category', 'user']);
            }

            // Filtros opcionales
            if ($request->has('categoria_id') && $request->categoria_id) {
                $query->byCategory($request->categoria_id);
            }

            if ($request->has('search') && $request->search) {
                $query->search($request->search);
            }

            if ($request->has('low_stock_only') && $request->low_stock_only) {
                $query->lowStock();
            }

            // Solo productos activos por defecto
            if (!$request->has('include_inactive')) {
                $query->active();
            }

            // Paginación
            $limit = min($request->get('limit', 100), 500);
            $offset = $request->get('offset', 0);

            $products = $query->offset($offset)
                            ->limit($limit)
                            ->get();

            // Formatear respuesta
            $formattedProducts = $products->map(function ($product) use ($tableExists) {
                $baseData = [
                    'id' => $product->id,
                    'nombre_producto' => $product->nombre_producto,
                    'precio' => $product->precio,
                    'variant' => $product->variant,
                    'is_active' => $product->is_active,
                    'stock_quantity' => $product->stock_quantity,
                    'min_stock' => $product->min_stock,
                    'is_on_demand' => $product->isOnDemand(),
                    'has_low_stock' => $product->hasLowStock(),
                    'category' => [
                        'id' => $product->category->id,
                        'nombre_categoria' => $product->category->nombre_categoria
                    ],
                    'user' => [
                        'id' => $product->user->id,
                        'nombre_producto' => $product->user->nombre_producto
                    ],
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ];

                // Solo agregar ingredientes si la tabla existe y hay relaciones
                if ($tableExists && $product->recipes) {
                    $baseData['ingredients'] = $product->recipes->map(function ($recipe) {
                        return [
                            'insumo_id' => $recipe->insumo_id,
                            'nombre_insumo' => $recipe->insumo->nombre_insumo ?? 'N/A',
                            'cantidad' => $recipe->cantidad,
                            'unidad' => $recipe->insumo->unidad ?? 'N/A',
                            'precio_unitario' => isset($recipe->insumo->precio_presentacion) 
                                ? ($recipe->insumo->precio_presentacion / $recipe->insumo->cantidad_unitaria)
                                : 0
                        ];
                    });
                } else {
                    $baseData['ingredients'] = [];
                }

                return $baseData;
            });

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'total' => $products->count(),
                'limit' => $limit,
                'offset' => $offset,
                'table_exists' => $tableExists
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Crear un nuevo producto con receta
     */
    public function store(ProductRequest $request): JsonResponse
    {
        try {
            \Log::channel('daily')->info('=== MÉTODO STORE EJECUTADO ===');

            // Extensive logging of the entire request
            \Log::channel('daily')->info('Product Store - Full Request Debug', [
                'all_data' => $request->all(),
                'headers' => $request->headers->all(),
                'server_params' => $_SERVER,
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type')
            ]);
    
            // Log raw input before validation
            \Log::channel('daily')->info('Product Store - Raw Input', [
                'raw_input' => $request->input(),
                'input_keys' => array_keys($request->input())
            ]);
    
            // Obtain validated data
            try {
            $validatedData = $request->validated();
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::channel('daily')->error('Product Store - Validation Failed', [
                    'errors' => $e->errors(),
                    'failed_data' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
    
            // Extensive logging of validated data
            \Log::channel('daily')->info('Product Store - Validated Data', [
                'validated_data' => $validatedData,
                'validated_keys' => array_keys($validatedData)
            ]);
    
            // Obtain ingredients from request
            $ingredients = $request->input('ingredients', []);
    
            \Log::channel('daily')->info('Product Store - Ingredients', [
                'ingredients' => $ingredients,
                'ingredients_count' => count($ingredients)
            ]);
    
            DB::beginTransaction();
    
            try {
                // Detailed logging before product creation
                \Log::channel('daily')->info('Product Store - Preparing to Create Product', [
                    'nombre_producto' => $validatedData['nombre_producto'] ?? 'N/A',
                    'variant' => $validatedData['variant'] ?? 'N/A', 
                    'precio' => $validatedData['precio'] ?? 'N/A',
                    'categoria_id' => $validatedData['categoria_id'] ?? 'N/A',
                    'user_id' => $validatedData['user_id'] ?? 'N/A'
                ]);

                // Product creation with extensive error handling
                $product = Product::create([
                    'nombre_producto' => $validatedData['nombre_producto'],
                    'variant' => $validatedData['variant'] ?? null, 
                    'precio' => $validatedData['precio'],
                    'categoria_id' => $validatedData['categoria_id'], 
                    'user_id' => $validatedData['user_id'],
                    'is_active' => $validatedData['is_active'] ?? true,
                    'stock_quantity' => -1, // Producto bajo demanda
                    'min_stock' => 0
                ]);


    
                // Log successful product creation
                \Log::channel('daily')->info('DEBUGGING - Intentando crear con estos datos:', [
                    'exact_data' => [
                        'nombre_producto' => $validatedData['nombre_producto'],
                        'variant' => $validatedData['variant'] ?? 'NO_EXISTE',
                        'precio' => $validatedData['precio'],
                        'categoria_id' => $validatedData['categoria_id'], 
                        'user_id' => $validatedData['user_id'],
                    ]
                ]);
                // Check if product_recipes table exists before creating recipes
                $tableExists = Schema::hasTable('product_recipes');
                $ingredientsCount = 0;
                
                if ($tableExists && !empty($ingredients)) {
                    \Log::channel('daily')->info('Product Store - Preparing to Create Ingredients', [
                        'ingredients_count' => count($ingredients)
                    ]);

                    foreach ($ingredients as $ingredient) {
                        // Validate required ingredient fields
                        if (!isset($ingredient['insumo_id']) || !isset($ingredient['cantidad'])) {
                            \Log::channel('daily')->warning('Product Store - Incomplete Ingredient Data', [
                                'ingredient' => $ingredient,
                                'missing_fields' => [
                                    'insumo_id' => !isset($ingredient['insumo_id']),
                                    'cantidad' => !isset($ingredient['cantidad'])
                                ]
                            ]);
                            throw new \Exception('Datos de ingrediente incompletos');
                        }
    
                        $productRecipe = ProductRecipe::create([
                            'product_id' => $product->id,
                            'insumo_id' => $ingredient['insumo_id'],
                            'cantidad' => $ingredient['cantidad']
                        ]);

                        \Log::channel('daily')->info('Product Store - Ingredient Created', [
                            'product_recipe_id' => $productRecipe->id,
                            'insumo_id' => $ingredient['insumo_id'],
                            'cantidad' => $ingredient['cantidad']
                        ]);

                        $ingredientsCount++;
                    }
                }
    
                DB::commit();
    
                $message = !empty($ingredients) && $tableExists
                    ? 'Producto creado exitosamente con su receta'
                    : 'Producto creado exitosamente';
    
                \Log::channel('daily')->info('Product Store - Complete Success', [
                    'product_id' => $product->id,
                    'message' => $message,
                    'ingredients_count' => $ingredientsCount
                ]);
    
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $product->id,
                        'nombre_producto' => $product->nombre_producto,
                        'variant' => $product->variant,
                        'precio' => $product->precio,
                        'categoria_id' => $product->categoria_id,
                        'user_id' => $product->user_id,
                        'is_active' => $product->is_active,
                        'message' => $message,
                        'ingredients_count' => $ingredientsCount,
                        'has_ingredients' => $ingredientsCount > 0,
                        'table_exists' => $tableExists
                    ]
                ], 201);
    
            } catch (\Exception $e) {
                DB::rollBack();
                
                \Log::channel('daily')->error('Product Store - Database Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'input_data' => $request->all()
                ]);
    
                throw $e;
            }
    
        } catch (\Exception $e) {
            \Log::channel('daily')->error('Product Store - General Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input_data' => $request->all()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el producto',
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Obtener un producto por su ID
     */
    public function show($id): JsonResponse
    {
        try {
            // Verificar si la tabla product_recipes existe
            $tableExists = Schema::hasTable('product_recipes');
            
            if ($tableExists) {
                $product = Product::with(['category', 'user', 'recipes.insumo'])->findOrFail($id);
            } else {
                $product = Product::with(['category', 'user'])->findOrFail($id);
            }

            $formattedProduct = [
                'id' => $product->id,
                'nombre_producto' => $product->nombre_producto,
                'precio' => $product->precio, // ✅ CORREGIDO: era 'price'
                'variant' => $product->variant,
                'is_active' => $product->is_active,
                'stock_quantity' => $product->stock_quantity,
                'min_stock' => $product->min_stock,
                'is_on_demand' => $product->isOnDemand(),
                'has_low_stock' => $product->hasLowStock(),
                'category' => [
                    'id' => $product->category->id,
                    'nombre_categoria' => $product->category->nombre_categoria
                ],
                'user' => [
                    'id' => $product->user->id,
                    'nombre_producto' => $product->user->nombre_producto
                ],
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ];

            // Solo agregar ingredientes si la tabla existe
            if ($tableExists && $product->recipes) {
                $formattedProduct['ingredients'] = $product->recipes->map(function ($recipe) {
                    return [
                        'insumo_id' => $recipe->insumo_id,
                        'nombre_insumo' => $recipe->insumo->nombre_insumo,
                        'cantidad' => $recipe->cantidad,
                        'unidad' => $recipe->insumo->unidad,
                        'precio_unitario' => $recipe->insumo->precio_presentacion / $recipe->insumo->cantidad_unitaria
                    ];
                });
            } else {
                $formattedProduct['ingredients'] = [];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedProduct
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Producto con ID {$id} no encontrado"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un producto existente con su receta
     */
    public function update(ProductRequest $request, $id): JsonResponse
    {
        try {
            // Obtener datos validados
            $validatedData = $request->validated();

            $product = Product::findOrFail($id);

            DB::beginTransaction();

            try {
                // ✅ CORREGIDO: Mapeo consistente de campos
                $product->update([
                    'nombre_producto' => $validatedData['nombre_producto'],
                    'precio' => $validatedData['precio'],
                    'categoria_id' => $validatedData['categoria_id'], // ✅ Consistente con BD
                    'variant' => $validatedData['variant'] ?? null, // ✅ Frontend envía 'variant'
                    'is_active' => $validatedData['is_active'] ?? true
                ]);

                // Verificar si la tabla product_recipes existe
                $tableExists = Schema::hasTable('product_recipes');
                $ingredientsCount = 0;
                
                if ($tableExists) {
                    // Eliminar receta anterior
                    ProductRecipe::where('product_id', $product->id)->delete();

                    // Crear los nuevos ingredientes de la receta
                    $ingredients = $request->input('ingredients', []);
                    foreach ($ingredients as $ingredient) {
                        ProductRecipe::create([
                            'product_id' => $product->id,
                            'insumo_id' => $ingredient['insumo_id'],
                            'cantidad' => $ingredient['cantidad']
                        ]);
                        $ingredientsCount++;
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $product->id,
                        'nombre_producto' => $product->nombre_producto,
                        'variant' => $product->variant,
                        'precio' => $product->precio,
                        'message' => 'Producto actualizado exitosamente con su receta',
                        'ingredients_count' => $ingredientsCount,
                        'table_exists' => $tableExists
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Producto con ID {$id} no encontrado"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un producto (soft delete) con verificación inteligente
     */
    public function destroy($id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            
            DB::beginTransaction();

            try {
                // Verificar si la tabla purchase_details existe
                $purchaseDetailsTableExists = Schema::hasTable('purchase_details');
                
                if ($purchaseDetailsTableExists) {
                    // Obtener las columnas de la tabla purchase_details
                    $columns = Schema::getColumnListing('purchase_details');
                    \Log::info('Purchase details columns:', $columns);
                    
                    // Determinar el nombre correcto de la columna del producto
                    $productColumnName = null;
                    $possibleColumns = ['product_id', 'producto_id', 'id_producto', 'product_id_fk'];
                    
                    foreach ($possibleColumns as $possibleColumn) {
                        if (in_array($possibleColumn, $columns)) {
                            $productColumnName = $possibleColumn;
                            break;
                        }
                    }
                    
                    if ($productColumnName) {
                        // Verificar si el producto tiene compras asociadas
                        $hasPurchaseDetails = DB::table('purchase_details')
                                               ->where($productColumnName, $id)
                                               ->exists();

                        if ($hasPurchaseDetails) {
                            return response()->json([
                                'success' => false,
                                'message' => 'No se puede eliminar el producto porque tiene compras asociadas',
                                'details' => [
                                    'product_id' => $id,
                                    'product_name' => $product->nombre_producto,
                                    'reason' => 'Integridad referencial - Existen registros de compra'
                                ]
                            ], 400);
                        }
                    } else {
                        // Si no encontramos columna de producto, registrar warning pero continuar
                        \Log::warning('No se encontró columna de producto en purchase_details', [
                            'available_columns' => $columns,
                            'searched_columns' => $possibleColumns
                        ]);
                    }
                } else {
                    \Log::info('Tabla purchase_details no existe, procediendo con eliminación');
                }

                // Verificar otras tablas que podrían tener relaciones
                $relatedTables = $this->checkRelatedTables($id);
                
                if (!empty($relatedTables)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar el producto porque tiene registros relacionados',
                        'details' => [
                            'product_id' => $id,
                            'product_name' => $product->nombre_producto,
                            'related_tables' => $relatedTables
                        ]
                    ], 400);
                }

                // Eliminar las recetas asociadas primero
                if (Schema::hasTable('product_recipes')) {
                    ProductRecipe::where('product_id', $id)->delete();
                }
                
                // Soft delete del producto
                $product->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Producto eliminado exitosamente',
                    'data' => [
                        'deleted_product_id' => $id,
                        'deleted_product_name' => $product->nombre_producto,
                        'deletion_type' => 'soft_delete'
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Producto con ID {$id} no encontrado"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto',
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'product_id' => $id
                ]
            ], 500);
        }
    }

    /**
     * Verificar tablas relacionadas que podrían impedir la eliminación
     */
    private function checkRelatedTables($productId): array
    {
        $relatedTables = [];
        
        try {
            // Lista de tablas a verificar con sus posibles nombres de columna
            $tablesToCheck = [
                'purchase_details' => ['product_id', 'producto_id', 'id_producto'],
                'order_items' => ['product_id', 'producto_id', 'id_producto'],
                'cart_items' => ['product_id', 'producto_id', 'id_producto'],
                'sale_details' => ['product_id', 'producto_id', 'id_producto'],
                'invoice_details' => ['product_id', 'producto_id', 'id_producto'],
            ];
            
            foreach ($tablesToCheck as $tableName => $possibleColumns) {
                if (Schema::hasTable($tableName)) {
                    $columns = Schema::getColumnListing($tableName);
                    
                    foreach ($possibleColumns as $columnName) {
                        if (in_array($columnName, $columns)) {
                            $count = DB::table($tableName)->where($columnName, $productId)->count();
                            if ($count > 0) {
                                $relatedTables[] = [
                                    'table' => $tableName,
                                    'column' => $columnName,
                                    'count' => $count
                                ];
                            }
                            break; // Solo necesitamos encontrar una columna válida por tabla
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Error checking related tables', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
        }
        
        return $relatedTables;
    }

    /**
     * Agregar o actualizar ingredientes de un producto
     */
    public function updateIngredients(Request $request, $id): JsonResponse
    {
        try {
            // Validar que el producto existe
            $product = Product::findOrFail($id);
            
            // Verificar si la tabla product_recipes existe
            if (!Schema::hasTable('product_recipes')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La funcionalidad de ingredientes no está disponible'
                ], 400);
            }
            
            // Validar ingredientes
            $validator = Validator::make($request->all(), [
                'ingredients' => 'required|array|min:1',
                'ingredients.*.insumo_id' => 'required|exists:insumos,id',
                'ingredients.*.cantidad' => 'required|numeric|min:0.001'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en los datos de los ingredientes',
                    'errors' => $validator->errors()
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Eliminar ingredientes existentes
                ProductRecipe::where('product_id', $id)->delete();

                // Crear los nuevos ingredientes
                $ingredientsCount = 0;
                foreach ($request->ingredients as $ingredient) {
                    ProductRecipe::create([
                        'product_id' => $id,
                        'insumo_id' => $ingredient['insumo_id'],
                        'cantidad' => $ingredient['cantidad']
                    ]);
                    $ingredientsCount++;
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Ingredientes actualizados exitosamente',
                    'data' => [
                        'product_id' => $id,
                        'ingredients_count' => $ingredientsCount
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Producto con ID {$id} no encontrado"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar ingredientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método de debug para ver qué datos están llegando
     */
    public function debug(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Debug de datos recibidos',
            'request_all' => $request->all(),
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'has_precio' => $request->has('precio'),
            'has_categoria_id' => $request->has('categoria_id'),
            'has_category_id' => $request->has('category_id'),
            'precio_value' => $request->get('precio'),
            'categoria_id_value' => $request->get('categoria_id'),
            'category_id_value' => $request->get('category_id'),
            'has_variant' => $request->has('variant'),
            'variant_value' => $request->get('variant')
        ]);
    }

    public function corsDebug(Request $request): JsonResponse
    {
        return response()->json([
            'origin' => $request->header('Origin'),
            'host' => $request->header('Host'),
            'referer' => $request->header('Referer'),
            'all_headers' => $request->headers->all()
        ]);
    }
}