<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $table = 'purchases';

    protected $fillable = [
        'invoice_date',
        'total_amount',
        'delivery_fee',
        'payment_method',
        'has_delivery',
        'is_cancelled',
        'client_name',
        'client_phone',
        'delivery_address',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'has_delivery' => 'boolean',
        'is_cancelled' => 'boolean',
    ];

    /**
     * Relación con los detalles de compra
     */
    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    /**
     * Scope para compras no canceladas
     */
    public function scopeNotCancelled($query)
    {
        return $query->where('is_cancelled', false);
    }

    /**
     * Scope para compras con entrega
     */
    public function scopeWithDelivery($query)
    {
        return $query->where('has_delivery', true);
    }

    /**
     * Scope para compras por método de pago
     */
    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope para compras por rango de fechas
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween(\DB::raw("STR_TO_DATE(invoice_date, '%d/%m/%Y')"), [$startDate, $endDate]);
    }

    /**
     * Accessor para obtener la fecha formateada
     */
    public function getFormattedDateAttribute()
    {
        try {
            // Asumiendo que invoice_date está en formato dd/mm/yyyy
            $date = \Carbon\Carbon::createFromFormat('d/m/Y', $this->invoice_date);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return $this->invoice_date;
        }
    }
}