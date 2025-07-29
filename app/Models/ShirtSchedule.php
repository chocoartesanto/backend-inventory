<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShirtSchedule extends Model
{
    use HasFactory;

    protected $table = 'shirt_schedule';
    
    // No usar timestamps automáticos de Laravel, usar los personalizados
    public $timestamps = false;
    
    protected $primaryKey = 'day';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'day',
        'day_name',
        'color',
        'color_name',
        'updated_by'
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    // Definir los días por defecto
    public static function getDefaultSchedule()
    {
        return [
            ['day' => 'monday', 'dayName' => 'Lunes', 'color' => '#ffffff', 'colorName' => 'Blanco'],
            ['day' => 'tuesday', 'dayName' => 'Martes', 'color' => '#ec4899', 'colorName' => 'Rosa'],
            ['day' => 'wednesday', 'dayName' => 'Miércoles', 'color' => '#8b5cf6', 'colorName' => 'Morado'],
            ['day' => 'thursday', 'dayName' => 'Jueves', 'color' => '#6b7280', 'colorName' => 'Gris'],
            ['day' => 'friday', 'dayName' => 'Viernes', 'color' => '#dc2626', 'colorName' => 'Rojo Oscuro'],
            ['day' => 'saturday', 'dayName' => 'Sábado', 'color' => '#ec4899', 'colorName' => 'Rosa'],
            ['day' => 'sunday', 'dayName' => 'Domingo', 'color' => '#8b5cf6', 'colorName' => 'Morado']
        ];
    }

    // Obtener nombres de días en español
    public static function getDayNames()
    {
        return [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo'
        ];
    }

    // Relación con el usuario que actualizó
    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by', 'username');
    }
}