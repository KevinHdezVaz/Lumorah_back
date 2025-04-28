<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Añadido para factories

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;  
    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'email',
        'password', // Asegúrate que coincida con tus campos de base de datos
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at', // Ocultar campos sensibles
    ];
 
}