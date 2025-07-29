<?php

namespace App\Services;

use App\Models\Insumo;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class InsumoService
{
    /**
     * Crear un nuevo insumo
     */
    public static function createInsumo(array $data): ?int
    {
        try {
            // Validar datos requeridos
            $requiredFields = ['nombre_insumo', 'unidad', 'cantidad_unitaria', 'precio_presentacion'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === null) {
                    throw new \InvalidArgumentException("El campo {$field} es obligatorio");
                }
            }

            // Establecer valores por defecto
            $data['cantidad_utilizada'] = $data['cantidad_utilizada'] ?? 0;
            $data['cantidad_por_producto'] = $data['cantidad_por_producto'] ?? 0;
            $data['stock_minimo'] = $data['stock_minimo'] ?? 0;

            $insumo = Insumo::create($data);
            
            return $insumo->id;
        } catch (Exception $e) {
            \Log::error('Error creating insumo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener insumo por ID
     */
    public static function getInsumoById(int $id): ?Insumo
    {
        try {
            return Insumo::find($id);
        } catch (Exception $e) {
            \Log::error('Error getting insumo by ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener lista de insumos con filtros
     */
    public static function getInsumos(?string $search = null, bool $lowStockOnly = false): Collection
    {
        try {
            $query = Insumo::query();

            // Aplicar filtro de búsqueda
            if ($search) {
                $query->search($search);
            }

            // Aplicar filtro de stock bajo
            if ($lowStockOnly) {
                $query->lowStock();
            }

            return $query->orderBy('nombre_insumo')->get();
        } catch (Exception $e) {
            \Log::error('Error getting insumos: ' . $e->getMessage());
            return new Collection();
        }
    }

    /**
     * Actualizar un insumo
     */
    public static function updateInsumo(int $id, array $data): bool
    {
        try {
            $insumo = Insumo::find($id);
            
            if (!$insumo) {
                return false;
            }

            // Filtrar solo los campos permitidos para actualización
            $allowedFields = [
                'nombre_insumo',
                'unidad',
                'cantidad_unitaria',
                'precio_presentacion',
                'cantidad_utilizada',
                'cantidad_por_producto',
                'stock_minimo',
                'sitio_referencia'
            ];

            $updateData = array_intersect_key($data, array_flip($allowedFields));
            
            return $insumo->update($updateData);
        } catch (Exception $e) {
            \Log::error('Error updating insumo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar un insumo
     */
    public static function deleteInsumo(int $id): bool
    {
        try {
            $insumo = Insumo::find($id);
            
            if (!$insumo) {
                return false;
            }

            return $insumo->delete();
        } catch (Exception $e) {
            \Log::error('Error deleting insumo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe un insumo con el mismo nombre
     */
    public static function existsByName(string $name, ?int $excludeId = null): bool
    {
        try {
            $query = Insumo::where('nombre_insumo', $name);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            return $query->exists();
        } catch (Exception $e) {
            \Log::error('Error checking insumo existence: ' . $e->getMessage());
            return false;
        }
    }
}