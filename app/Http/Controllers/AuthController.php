<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Registro de un nuevo usuario (admin, fisioterapeuta o paciente)
     */

     public function register(Request $request)
     {
         $request->validate([
             'name' => 'required|string|max:255',
             'email' => 'required|string|email|max:255|unique:users',
             'password' => 'required|string|min:6',
             'role' => 'required|in:admin,physiotherapist,patient',
             'dni_image' => 'required_if:role,physiotherapist|file|max:10240', // 10MB máximo
         ]);
     
         $user = User::create([
             'name' => $request->name,
             'email' => $request->email,
             'password' => Hash::make($request->password),
             'role' => $request->role,
             'is_verified' => $request->role == 'physiotherapist' ? 0 : 1, // Pendiente de verificación para fisioterapeutas
         ]);
     
         if ($request->hasFile('dni_image') && $request->role == 'physiotherapist') {
             $path = $request->file('dni_image')->store('dni_images', 'public');
             $user->dni_image_path = $path; // Guardar la ruta si usas una columna
             $user->save();
         }
     
         // Generar token con Sanctum
         $token = $user->createToken('auth_token', [$user->role])->plainTextToken;
     
         return response()->json([
             'user' => $user,
             'token' => $token,
         ], 201);
     }
    /**
     * Inicio de sesión para un usuario
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $token = $user->createToken('auth_token', [$user->role])->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Cierre de sesión para un usuario
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada exitosamente']);
    }

    /**
     * Obtener el perfil del usuario autenticado
     */
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }
}