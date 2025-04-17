<?php

namespace App\Http\Controllers;

use App\Models\Promocion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromocionController extends Controller
{
    public function index()
    {
        $promociones = Promocion::latest()->get();
        return view('laravel-examples.field-listPromo', compact('promociones'));
    }

    public function apiIndex()
    {
        $promociones = Promocion::latest()->get();
        return response()->json($promociones);
    }
    
    public function create()
    {
        return view('laravel-examples.field-addPromo');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'puntos_por_ticket' => 'required|integer|min:1',
            'monto_minimo' => 'nullable|numeric|min:0',
            'estado' => 'required|in:activa,inactiva,expirada',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        $data = $validated;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('promociones', 'public');
        }

        Promocion::create($data);

        return redirect()->route('promociones.index')->with('success', 'Promoción creada con éxito.');
    }

    public function destroy(Promocion $promocion)
    {
        if ($promocion->imagen) {
            Storage::disk('public')->delete($promocion->imagen);
        }

        $promocion->delete();

        return redirect()->route('promociones.index')->with('success', 'Promoción eliminada con éxito.');
    }
}