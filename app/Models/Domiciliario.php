<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domiciliario extends Model
{
    use HasFactory;

    protected $table = 'domiciliarios';

    protected $fillable = [
        'nombre',
        'telefono',
        'tarifa',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_salida' => 'date'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    // Validaciones personalizadas
    public static function rules($id = null)
    {
        return [
            'nombre' => 'required|string|max:100',
            'telefono' => 'required|string|max:20|unique:domiciliarios,telefono' . ($id ? ",$id" : ''),
            'tarifa' => 'required|numeric|gt:0',
        ];
    }

    public static function updateRules($id = null)
    {
        return [
            'nombre' => 'sometimes|string|max:100',
            'telefono' => 'sometimes|string|max:20|unique:domiciliarios,telefono' . ($id ? ",$id" : ''),
            'tarifa' => 'sometimes|numeric|gt:0',
        ];
    }
}