<?php

// app/Models/Insumo.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_insumo',
        'unidad',
        'cantidad_unitaria',
        'precio_presentacion',
        'cantidad_utilizada',
        'cantidad_por_producto',
        'stock_minimo',
        'sitio_referencia'
    ];

    // Relaciones
    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class);
    }

    // Método para calcular el costo por unidad
    public function getCostoPorUnidadAttribute()
    {
        if ($this->cantidad_unitaria > 0) {
            return $this->precio_presentacion / $this->cantidad_unitaria;
        }
        return 0;
    }

    // Método para verificar stock bajo
    public function hasLowStock()
    {
        return $this->cantidad_utilizada <= $this->stock_minimo;
    }
}