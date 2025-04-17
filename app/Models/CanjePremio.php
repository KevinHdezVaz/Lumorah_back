<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CanjePremio extends Model
{
    protected $table = 'canjes_premios';

    protected $fillable = [
        'usuario_id',
        'premio_id',
        'puntos_gastados',
        'estado',
        'canjeado_en',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function premio()
    {
        return $this->belongsTo(Premio::class, 'premio_id');
    }
}