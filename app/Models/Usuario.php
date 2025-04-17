<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'email',
        'contraseña',
        'saldo_puntos',
    ];

    protected $hidden = [
        'contraseña',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'usuario_id');
    }

    public function canjesPremios()
    {
        return $this->hasMany(CanjePremio::class, 'usuario_id');
    }

    public function transaccionesPuntos()
    {
        return $this->hasMany(TransaccionPuntos::class, 'usuario_id');
    }
}