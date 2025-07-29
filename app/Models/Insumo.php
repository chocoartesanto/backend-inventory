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

    protected $casts = [
        'cantidad_unitaria' => 'decimal:2',
        'precio_presentacion' => 'decimal:2',
        'cantidad_utilizada' => 'decimal:2',
        'cantidad_por_producto' => 'decimal:2',
        'stock_minimo' => 'decimal:2'
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
// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class Insumo extends Model
// {
//     use HasFactory;

//     protected $table = 'insumos';

//     protected $fillable = [
//         'nombre_insumo',
//         'unidad',
//         'cantidad_unitaria',
//         'precio_presentacion',
//         'cantidad_utilizada',
//         'cantidad_por_producto',
//         'stock_minimo',
//         'sitio_referencia'
//     ];

//     protected $casts = [
//         'cantidad_unitaria' => 'float',
//         'precio_presentacion' => 'float',
//         'cantidad_utilizada' => 'float',
//         'cantidad_por_producto' => 'float',
//         'stock_minimo' => 'float',
//     ];

//     // ====== RELACIONES NUEVAS PARA PRODUCTS ======
    
//     /**
//      * Relación con ProductRecipe (recetas que usan este insumo)
//      */
//     public function recipes()
//     {
//         return $this->hasMany(ProductRecipe::class);
//     }

//     /**
//      * Relación con Products a través de ProductRecipe
//      */
//     public function products()
//     {
//         return $this->belongsToMany(Product::class, 'product_recipes', 'insumo_id', 'product_id')
//                     ->withPivot('cantidad')
//                     ->withTimestamps();
//     }

//     // ====== TUS MÉTODOS ORIGINALES (SIN CAMBIOS) ======

//     /**
//      * Scope para buscar insumos por nombre
//      */
//     public function scopeSearch($query, $search)
//     {
//         if ($search) {
//             return $query->where('nombre_insumo', 'like', '%' . $search . '%');
//         }
//         return $query;
//     }

//     /**
//      * Scope para insumos con stock bajo
//      */
//     public function scopeLowStock($query)
//     {
//         return $query->whereRaw('cantidad_utilizada <= stock_minimo');
//     }

//     /**
//      * Verificar si el insumo tiene stock bajo
//      */
//     public function hasLowStock()
//     {
//         return $this->cantidad_utilizada <= $this->stock_minimo;
//     }

//     /**
//      * Calcular el costo por unidad
//      */
//     public function getCostoPorUnidadAttribute()
//     {
//         if ($this->cantidad_unitaria > 0) {
//             return $this->precio_presentacion / $this->cantidad_unitaria;
//         }
//         return 0;
//     }

//     // ====== MÉTODO ADICIONAL PARA PRODUCTS ======
    
//     /**
//      * Obtener el precio formateado
//      */
//     public function getFormattedPriceAttribute()
//     {
//         return '$' . number_format($this->precio_presentacion, 2);
//     }
// }