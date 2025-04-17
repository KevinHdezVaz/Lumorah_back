<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Premio extends Model
{
    protected $table = 'premios';

    protected $fillable = [
        'titulo',
        'descripcion',
        'puntos_requeridos',
        'stock',
        'imagen',
        'estado',
    ];

    public function canjesPremios()
    {
        return $this->hasMany(CanjePremio::class, 'premio_id');
    }

    public function transaccionesPuntos()
    {
        return $this->hasMany(TransaccionPuntos::class, 'premio_id');
    }
}