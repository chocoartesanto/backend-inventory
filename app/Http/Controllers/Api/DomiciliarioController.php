<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domiciliario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class DomiciliarioController extends Controller
{
    /**
     * Obtiene todos los domiciliarios
     */
    public function index(): JsonResponse
    {
        try {
            $domiciliarios = Domiciliario::all();
            
            return response()->json($domiciliarios, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los domiciliarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea un nuevo domiciliario
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), Domiciliario::rules());

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $domiciliario = Domiciliario::create($request->all());

            return response()->json($domiciliario, 201);
        } catch (QueryException $e) {
            // Manejar errores específicos de base de datos
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                return response()->json([
                    'message' => 'Ya existe un domiciliario con el teléfono ' . $request->telefono
                ], 400);
            }
            
            return response()->json([
                'message' => 'Error al crear el domiciliario',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el domiciliario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene un domiciliario específico por su ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $domiciliario = Domiciliario::find($id);

            if (!$domiciliario) {
                return response()->json([
                    'message' => "Domiciliario con ID $id no encontrado"
                ], 404);
            }

            return response()->json($domiciliario, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el domiciliario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza un domiciliario existente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $domiciliario = Domiciliario::find($id);

            if (!$domiciliario) {
                return response()->json([
                    'message' => "Domiciliario con ID $id no encontrado"
                ], 404);
            }

            $validator = Validator::make($request->all(), Domiciliario::updateRules($id));

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Solo actualizar los campos que se enviaron
            $fieldsToUpdate = array_filter($request->only(['nombre', 'telefono', 'tarifa']), function($value) {
                return $value !== null;
            });

            if (empty($fieldsToUpdate)) {
                return response()->json($domiciliario, 200);
            }

            $domiciliario->update($fieldsToUpdate);

            return response()->json($domiciliario->fresh(), 200);
        } catch (QueryException $e) {
            // Manejar errores específicos de base de datos
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                return response()->json([
                    'message' => 'Ya existe otro domiciliario con el teléfono ' . $request->telefono
                ], 400);
            }
            
            return response()->json([
                'message' => 'Error al actualizar el domiciliario',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el domiciliario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina un domiciliario
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $domiciliario = Domiciliario::find($id);

            if (!$domiciliario) {
                return response()->json([
                    'message' => "Domiciliario con ID $id no encontrado"
                ], 404);
            }

            $domiciliario->delete();

            return response()->json([
                'message' => "Domiciliario con ID $id eliminado exitosamente"
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el domiciliario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}