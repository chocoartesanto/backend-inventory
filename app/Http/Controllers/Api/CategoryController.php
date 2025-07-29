<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Crear una nueva categoría
     * POST /api/v1/categories
     */
    public function store(Request $request): JsonResponse
    {
        // Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,nombre_categoria'
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio',
            'name.unique' => 'Ya existe una categoría con ese nombre'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $category = Category::create([
                'nombre_categoria' => $request->input('name')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => [
                    'id' => $category->id,
                    'nombre_categoria' => $category->nombre_categoria,
                    'created_at' => $category->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las categorías
     * GET /api/v1/categories
     */
    public function index(): JsonResponse
    {
        try {
            $categories = Category::orderBy('nombre_categoria')->get();
            
            return response()->json([
                'success' => true,
                'data' => $categories
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una categoría específica por ID
     * GET /api/v1/categories/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => "Categoría con ID {$id} no encontrada"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $category
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una categoría
     * PUT /api/v1/categories/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => "Categoría con ID {$id} no encontrada"
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,nombre_categoria,' . $id
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio',
            'name.unique' => 'Ya existe otra categoría con ese nombre'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la categoría',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $category->update([
                'nombre_categoria' => $request->input('name')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente',
                'data' => $category->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una categoría
     * DELETE /api/v1/categories/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => "Categoría con ID {$id} no encontrada"
                ], 404);
            }

            // Nota: Aquí podrías agregar lógica para verificar si hay productos
            // usando esta categoría, como en tu código Python original

            // Eliminar la categoría
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}