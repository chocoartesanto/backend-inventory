<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InsumoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class InsumoController extends Controller
{
    /**
     * Crear un nuevo insumo
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar datos de entrada
            $validator = Validator::make($request->all(), [
                'nombre_insumo' => 'required|string|max:255|unique:insumos,nombre_insumo',
                'unidad' => 'required|string|max:50',
                'cantidad_unitaria' => 'required|numeric|min:0',
                'precio_presentacion' => 'required|numeric|min:0',
                'cantidad_utilizada' => 'nullable|numeric|min:0',
                'cantidad_por_producto' => 'nullable|numeric|min:0',
                'stock_minimo' => 'nullable|numeric|min:0',
                'sitio_referencia' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Verificar si ya existe un insumo con el mismo nombre
            if (InsumoService::existsByName($request->nombre_insumo)) {
                return response()->json([
                    'success' => false,
                    'message' => "Ya existe un insumo con el nombre '{$request->nombre_insumo}'"
                ], 400);
            }

            $insumoId = InsumoService::createInsumo($request->all());

            if (!$insumoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el insumo'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Insumo creado exitosamente',
                'data' => [
                    'id' => $insumoId
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating insumo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener un insumo por su ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $insumo = InsumoService::getInsumoById($id);

            if (!$insumo) {
                return response()->json([
                    'success' => false,
                    'message' => "Insumo con ID {$id} no encontrado"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $insumo
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting insumo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener lista de insumos con filtros
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $lowStockOnly = $request->boolean('low_stock_only', false);

            $insumos = InsumoService::getInsumos($search, $lowStockOnly);

            return response()->json([
                'success' => true,
                'data' => $insumos,
                'count' => $insumos->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting insumos list: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Actualizar un insumo existente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Verificar que el insumo existe
            $insumo = InsumoService::getInsumoById($id);
            if (!$insumo) {
                return response()->json([
                    'success' => false,
                    'message' => "Insumo con ID {$id} no encontrado"
                ], 404);
            }

            // Validar datos de entrada
            $validator = Validator::make($request->all(), [
                'nombre_insumo' => 'sometimes|string|max:255|unique:insumos,nombre_insumo,' . $id,
                'unidad' => 'sometimes|string|max:50',
                'cantidad_unitaria' => 'sometimes|numeric|min:0',
                'precio_presentacion' => 'sometimes|numeric|min:0',
                'cantidad_utilizada' => 'sometimes|numeric|min:0',
                'cantidad_por_producto' => 'sometimes|numeric|min:0',
                'stock_minimo' => 'sometimes|numeric|min:0',
                'sitio_referencia' => 'sometimes|nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 400);
            }

            if (empty($request->all())) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se proporcionaron datos para actualizar'
                ], 400);
            }

            $success = InsumoService::updateInsumo($id, $request->all());

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el insumo'
                ], 500);
            }

            // Obtener el insumo actualizado
            $updatedInsumo = InsumoService::getInsumoById($id);

            return response()->json([
                'success' => true,
                'message' => 'Insumo actualizado exitosamente',
                'data' => $updatedInsumo
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating insumo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Eliminar un insumo
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $insumo = InsumoService::getInsumoById($id);
            if (!$insumo) {
                return response()->json([
                    'success' => false,
                    'message' => "Insumo con ID {$id} no encontrado"
                ], 404);
            }

            $success = InsumoService::deleteInsumo($id);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el insumo'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Insumo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting insumo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}