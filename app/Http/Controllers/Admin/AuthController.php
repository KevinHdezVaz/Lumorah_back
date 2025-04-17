<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
  

class AuthController extends Controller
{
    // Mostrar el formulario de login
    public function showLoginForm()
    {
        return view('session.login-session');
    }

    // Manejar el login para el admin
    public function store()
    {
        $attributes = request()->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        dd($attributes);
        if (Auth::guard('admin')->attempt($attributes)) {
            session()->regenerate();
            dd(Auth::guard('admin')->user());

            return redirect('dashboard')->with(['success' => 'Has iniciado sesión correctamente.']);
        } else {
            return back()->withErrors(['email' => 'Correo o contraseña inválidos.']);
        }
    }

    // Manejar el logout para el admin
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Sesión cerrada correctamente.');
    }
}
