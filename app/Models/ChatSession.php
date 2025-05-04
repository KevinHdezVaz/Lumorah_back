<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 
        'title',
        'is_saved', // Añade este campo
        'created_at',
        'updated_at'
    ];

    /**
     * Los atributos que deberían estar visibles en las respuestas JSON.
     *
     * @var array
     */
    protected $visible = [
        'id',
        'user_id',
        'title',
        'is_saved', // Añade este campo
        'created_at',
        'updated_at'
    ];

    /**
     * Los atributos que deberían ser casteados a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'is_saved' => 'boolean', // Esto convierte automáticamente 1/0 a true/false
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Valor por defecto para los atributos.
     *
     * @var array
     */
    protected $attributes = [
        'is_saved' => false, // Valor por defecto (se guardará como 0 en la BD)
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}