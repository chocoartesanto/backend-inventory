<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre_producto',
        'variant',
        'precio',
        'description',
        'categoria_id',
        'user_id',
        'is_active',
        'stock_quantity',
        'min_stock'
    ];

    // protected $casts = [
    //     'precio_venta' => 'decimal:2',
    //     'costo_produccion' => 'decimal:2',
    //     'stock' => 'decimal:2'
    // ];

    // ✅ Accessors/Mutators corregidos
    public function getVarianteAttribute()
    {
        return $this->variant;
    }

    public function setVarianteAttribute($value)
    {
        $this->attributes['variant'] = $value;
    }

    // ✅ Mantener los nombres originales de BD para estos campos
    public function getNombreProductoAttribute()
    {
        return $this->attributes['nombre_producto'];
    }

    public function setNombreProductoAttribute($value)
    {
        $this->attributes['nombre_producto'] = $value;
    }

    public function getPrecioCopAttribute()
    {
        return $this->attributes['precio'];
    }

    public function setPrecioCopAttribute($value)
    {
        $this->attributes['precio'] = $value;
    }

    public function getCategoriaIdAttribute()
    {
        return $this->attributes['categoria_id'];
    }

    public function setCategoriaIdAttribute($value)
    {
        $this->attributes['categoria_id'] = $value;
    }

    // Relaciones
    public function category()
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('categoria_id', $categoryId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('nombre_producto', 'LIKE', "%{$search}%")
                    ->orWhere('variant', 'LIKE', "%{$search}%");
    }

    public function scopeLowStock($query)
    {
        return $query->where('stock_quantity', '>', 0)
                    ->whereRaw('stock_quantity <= min_stock');
    }

    // Métodos auxiliares
    public function isOnDemand()
    {
        return $this->stock_quantity === -1;
    }

    public function hasLowStock()
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= $this->min_stock;
    }
}