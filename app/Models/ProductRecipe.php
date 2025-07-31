<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'insumo_id',
        'cantidad'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2'
    ];

    /**
     * Relación con Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con Insumo
     */
    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    /**
     * Scope para obtener recetas de un producto específico
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope para obtener recetas que usan un insumo específico
     */
    public function scopeUsingInsumo($query, $insumoId)
    {
        return $query->where('insumo_id', $insumoId);
    }
}