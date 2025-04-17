<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promocion extends Model
{
    protected $table = 'promociones';

    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'puntos_por_ticket',
        'monto_minimo',
        'estado',
        'imagen',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'promocion_id');
    }
}