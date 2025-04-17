<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaccionPuntos extends Model
{
    protected $table = 'transacciones_puntos';

    protected $fillable = [
        'usuario_id',
        'ticket_id',
        'premio_id',
        'puntos',
        'tipo',
        'descripcion',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function premio()
    {
        return $this->belongsTo(Premio::class, 'premio_id');
    }
}