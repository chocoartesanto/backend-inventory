<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_name',
        'product_variant',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    // protected $casts = [
    //     'cantidad' => 'decimal:2',
    //     'precio_unitario' => 'decimal:2',
    //     'subtotal' => 'decimal:2'
    // ];

    /**
     * Relación con la compra principal
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}