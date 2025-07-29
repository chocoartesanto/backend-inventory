<?php
// app/Models/UserPermission.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'facturar',
        'verVentas',
    ];

    protected $casts = [
        'facturar' => 'boolean',
        'verVentas' => 'boolean',
    ];

    /**
     * Relación con usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}