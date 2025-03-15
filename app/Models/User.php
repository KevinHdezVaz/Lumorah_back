<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'dni_image_path', // Añadido
        'is_verified',    // Añadido
    ];

    /**
     * Los atributos que deben estar ocultos para las respuestas.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_verified' => 'boolean', // Añadido
    ];
}