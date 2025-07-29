<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShirtSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ShirtScheduleController extends Controller
{
    /**
     * Obtiene la programación actual de camisetas
     */
    public function index(): JsonResponse
    {
        try {
            // Obtener la programación actual de la base de datos
            $dbSchedule = ShirtSchedule::select('day', 'day_name as dayName', 'color', 'color_name as colorName')
                ->get()
                ->keyBy('day');

            // Obtener la programación por defecto
            $defaultSchedule = ShirtSchedule::getDefaultSchedule();
            $finalSchedule = [];

            // Completar con datos de BD o usar valores por defecto
            foreach ($defaultSchedule as $defaultDay) {
                if ($dbSchedule->has($defaultDay['day'])) {
                    $finalSchedule[] = $dbSchedule[$defaultDay['day']]->toArray();
                } else {
                    $finalSchedule[] = $defaultDay;
                }
            }

            // Obtener información de la última actualización
            $updateInfo = ShirtSchedule::orderBy('updated_at', 'desc')->first();

            return response()->json([
                'success' => true,
                'schedule' => $finalSchedule,
                'updated_at' => $updateInfo ? $updateInfo->updated_at->format('Y-m-d H:i:s') : null,
                'updated_by' => $updateInfo ? $updateInfo->updated_by : null
            ]);

        } catch (\Exception $e) {
            // En caso de error, devolver la programación por defecto
            return response()->json([
                'success' => true,
                'schedule' => ShirtSchedule::getDefaultSchedule(),
                'updated_at' => null,
                'updated_by' => null
            ]);
        }
    }

    /**
     * Guarda la programación completa de camisetas
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'schedule' => 'required|array',
                'schedule.*.day' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'schedule.*.dayName' => 'required|string',
                'schedule.*.color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'schedule.*.colorName' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $currentTime = Carbon::now();

            DB::beginTransaction();

            try {
                foreach ($request->schedule as $dayData) {
                    ShirtSchedule::updateOrCreate(
                        ['day' => $dayData['day']],
                        [
                            'day_name' => $dayData['dayName'],
                            'color' => $dayData['color'],
                            'color_name' => $dayData['colorName'],
                            'updated_by' => $user->username ?? $user->name,
                            'updated_at' => $currentTime
                        ]
                    );
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Programación de camisetas guardada correctamente',
                    'updated_at' => $currentTime->format('Y-m-d H:i:s'),
                    'updated_by' => $user->username ?? $user->name
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la programación de camisetas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza el color de un día específico
     */
    public function updateDay(Request $request, string $day): JsonResponse
    {
        try {
            $validator = Validator::make(array_merge(['day' => $day], $request->all()), [
                'day' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'colorName' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $dayNames = ShirtSchedule::getDayNames();
            $dayName = $dayNames[$day] ?? ucfirst($day);
            $currentTime = Carbon::now();

            $shirtSchedule = ShirtSchedule::updateOrCreate(
                ['day' => $day],
                [
                    'day_name' => $dayName,
                    'color' => $request->color,
                    'color_name' => $request->colorName,
                    'updated_by' => $user->username ?? $user->name,
                    'updated_at' => $currentTime
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Color para {$day} actualizado correctamente",
                'day' => $day,
                'color' => $request->color,
                'colorName' => $request->colorName,
                'updated_at' => $currentTime->format('Y-m-d H:i:s'),
                'updated_by' => $user->username ?? $user->name
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error al actualizar el color para {$day}: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpia duplicados de la tabla (endpoint de mantenimiento)
     */
    public function cleanDuplicates(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Encontrar duplicados usando consulta SQL directa
            $duplicates = DB::select("
                SELECT day, COUNT(*) as count 
                FROM shirt_schedule 
                GROUP BY day 
                HAVING COUNT(*) > 1
            ");

            if (empty($duplicates)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No se encontraron duplicados',
                    'cleaned_days' => []
                ]);
            }

            $cleanedDays = [];

            DB::beginTransaction();

            try {
                foreach ($duplicates as $duplicate) {
                    $day = $duplicate->day;
                    $count = $duplicate->count;

                    // Mantener solo el registro más reciente
                    DB::statement("
                        DELETE FROM shirt_schedule 
                        WHERE day = ? AND updated_at != (
                            SELECT max_updated_at FROM (
                                SELECT MAX(updated_at) as max_updated_at 
                                FROM shirt_schedule 
                                WHERE day = ?
                            ) as temp
                        )
                    ", [$day, $day]);

                    $cleanedDays[] = [
                        'day' => $day,
                        'duplicates_removed' => $count - 1
                    ];
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Se limpiaron duplicados de " . count($cleanedDays) . " días",
                    'cleaned_days' => $cleanedDays
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar duplicados: ' . $e->getMessage()
            ], 500);
        }
    }
}