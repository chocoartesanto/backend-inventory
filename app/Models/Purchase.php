<?php

// app/Models/Purchase.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'invoice_time',
        'client_name',
        'seller_username',
        'client_phone',
        'has_delivery',
        'delivery_address',
        'delivery_person',
        'delivery_fee',
        'subtotal_products',
        'total_amount',
        'amount_paid',
        'change_returned',
        'payment_method',
        'payment_reference',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'has_delivery' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'subtotal_products' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_returned' => 'decimal:2',
    ];

    /**
     * Relación con los detalles de la compra
     */
    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    /**
     * Relación con el usuario vendedor
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_username', 'username');
    }
}