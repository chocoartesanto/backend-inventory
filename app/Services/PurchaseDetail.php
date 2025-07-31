<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $table = 'purchase_details';

    protected $fillable = [
        'purchase_id',
        'product_name',
        'product_variant',
        'quantity',
        'unit_price',
        'subtotal',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relación con la compra principal
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Scope para obtener productos por nombre
     */
    public function scopeByProductName($query, $productName)
    {
        return $query->where('product_name', $productName);
    }

    /**
     * Scope para obtener productos por variante
     */
    public function scopeByVariant($query, $variant)
    {
        return $query->where('product_variant', $variant);
    }

    /**
     * Scope para obtener productos más vendidos
     */
    public function scopeTopSelling($query, $limit = 10)
    {
        return $query->selectRaw('
                product_name,
                product_variant,
                SUM(quantity) as total_quantity,
                SUM(subtotal) as total_revenue,
                COUNT(DISTINCT purchase_id) as number_of_orders
            ')
            ->groupBy('product_name', 'product_variant')
            ->orderByDesc('total_quantity')
            ->limit($limit);
    }
}