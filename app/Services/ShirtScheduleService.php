
<?php

namespace App\Services;

use App\Models\ShirtSchedule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShirtScheduleService
{
    /**
     * Obtiene la programación completa con valores por defecto si es necesario
     */
    public function getScheduleWithDefaults(): array
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

            return $finalSchedule;

        } catch (\Exception $e) {
            // En caso de error, devolver la programación por defecto
            return ShirtSchedule::getDefaultSchedule();
        }
    }

    /**
     * Guarda la programación completa
     */
    public function saveSchedule(array $scheduleData, string $username): array
    {
        $currentTime = Carbon::now();
        
        DB::beginTransaction();

        try {
            foreach ($scheduleData as $dayData) {
                ShirtSchedule::updateOrCreate(
                    ['day' => $dayData['day']],
                    [
                        'day_name' => $dayData['dayName'],
                        'color' => $dayData['color'],
                        'color_name' => $dayData['colorName'],
                        'updated_by' => $username,
                        'updated_at' => $currentTime
                    ]
                );
            }

            DB::commit();

            return [
                'success' => true,
                'updated_at' => $currentTime->format('Y-m-d H:i:s'),
                'updated_by' => $username
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza un día específico
     */
    public function updateDay(string $day, string $color, string $colorName, string $username): array
    {
        $dayNames = ShirtSchedule::getDayNames();
        $dayName = $dayNames[$day] ?? ucfirst($day);
        $currentTime = Carbon::now();

        $shirtSchedule = ShirtSchedule::updateOrCreate(
            ['day' => $day],
            [
                'day_name' => $dayName,
                'color' => $color,
                'color_name' => $colorName,
                'updated_by' => $username,
                'updated_at' => $currentTime
            ]
        );

        return [
            'success' => true,
            'day' => $day,
            'color' => $color,
            'colorName' => $colorName,
            'updated_at' => $currentTime->format('Y-m-d H:i:s'),
            'updated_by' => $username
        ];
    }

    /**
     * Obtiene información de la última actualización
     */
    public function getLastUpdateInfo(): ?array
    {
        $updateInfo = ShirtSchedule::orderBy('updated_at', 'desc')->first();
        
        if (!$updateInfo) {
            return null;
        }

        return [
            'updated_at' => $updateInfo->updated_at->format('Y-m-d H:i:s'),
            'updated_by' => $updateInfo->updated_by
        ];
    }

    /**
     * Limpia duplicados de la tabla
     */
    public function cleanDuplicates(): array
    {
        // Encontrar duplicados
        $duplicates = DB::select("
            SELECT day, COUNT(*) as count 
            FROM shirt_schedule 
            GROUP BY day 
            HAVING COUNT(*) > 1
        ");

        if (empty($duplicates)) {
            return [
                'success' => true,
                'message' => 'No se encontraron duplicados',
                'cleaned_days' => []
            ];
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

            return [
                'success' => true,
                'message' => "Se limpiaron duplicados de " . count($cleanedDays) . " días",
                'cleaned_days' => $cleanedDays
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene el color de un día específico
     */
    public function getDayColor(string $day): ?array
    {
        $schedule = ShirtSchedule::where('day', $day)->first();
        
        if (!$schedule) {
            // Buscar en la programación por defecto
            $defaultSchedule = ShirtSchedule::getDefaultSchedule();
            foreach ($defaultSchedule as $defaultDay) {
                if ($defaultDay['day'] === $day) {
                    return [
                        'day' => $day,
                        'dayName' => $defaultDay['dayName'],
                        'color' => $defaultDay['color'],
                        'colorName' => $defaultDay['colorName']
                    ];
                }
            }
            return null;
        }

        return [
            'day' => $schedule->day,
            'dayName' => $schedule->day_name,
            'color' => $schedule->color,
            'colorName' => $schedule->color_name
        ];
    }

    /**
     * Inicializa la tabla con datos por defecto si está vacía
     */
    public function initializeDefaultSchedule(string $username = 'system'): bool
    {
        if (ShirtSchedule::count() > 0) {
            return false; // Ya tiene datos
        }

        try {
            $currentTime = Carbon::now();
            $defaultSchedule = ShirtSchedule::getDefaultSchedule();

            foreach ($defaultSchedule as $dayData) {
                ShirtSchedule::create([
                    'day' => $dayData['day'],
                    'day_name' => $dayData['dayName'],
                    'color' => $dayData['color'],
                    'color_name' => $dayData['colorName'],
                    'updated_by' => $username,
                    'updated_at' => $currentTime
                ]);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}