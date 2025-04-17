<?php

namespace App\Http\Controllers;

use App\Models\Premio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PremioController extends Controller
{
    public function index()
    {
        $premios = Premio::latest()->get();
        return view('laravel-examples.field-listPremio', compact('premios'));
    }

    public function apiIndex()
    {
        $premios = Premio::latest()->get();
        return response()->json($premios);
    }

    
    public function create()
    {
        return view('laravel-examples.field-addPremio');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'puntos_requeridos' => 'required|integer|min:1',
            'stock' => 'nullable|integer|min:0',
            'estado' => 'required|in:activo,inactivo,sin_stock',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        $data = $validated;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('premios', 'public');
        }

        Premio::create($data);

        return redirect()->route('premios.index')->with('success', 'Premio creado con éxito.');
    }

    public function destroy(Premio $premio)
    {
        if ($premio->imagen) {
            Storage::disk('public')->delete($premio->imagen);
        }

        $premio->delete();

        return redirect()->route('premios.index')->with('success', 'Premio eliminado con éxito.');
    }
}