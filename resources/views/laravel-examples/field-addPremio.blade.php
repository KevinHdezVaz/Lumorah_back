@extends('layouts.user_type.auth')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header pb-0 px-3">
            <h6 class="mb-0">Nuevo Premio</h6>
        </div>
        <div class="card-body pt-4 p-3">
            @if ($errors->any())
                <div class="alert alert-danger text-white" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('premios.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="titulo">Título</label>
                    <input type="text" id="titulo" name="titulo" 
                        class="form-control @error('titulo') is-invalid @enderror" 
                        value="{{ old('titulo') }}" required>
                    @error('titulo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" 
                        class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="puntos_requeridos">Puntos Requeridos</label>
                    <input type="number" id="puntos_requeridos" name="puntos_requeridos" 
                        class="form-control @error('puntos_requeridos') is-invalid @enderror" 
                        value="{{ old('puntos_requeridos') }}" min="1" required>
                    @error('puntos_requeridos')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" 
                        class="form-control @error('stock') is-invalid @enderror" 
                        value="{{ old('stock') }}" min="0">
                    @error('stock')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" 
                        class="form-control @error('estado') is-invalid @enderror" required>
                        <option value="activo" {{ old('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                        <option value="inactivo" {{ old('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        <option value="sin_stock" {{ old('estado') == 'sin_stock' ? 'selected' : '' }}>Sin Stock</option>
                    </select>
                    @error('estado')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="imagen">Imagen del Premio</label>
                    <input type="file" id="imagen" name="imagen" 
                        class="form-control @error('imagen') is-invalid @enderror" 
                        accept="image/jpeg,image/png,image/jpg">
                    @error('imagen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Formatos permitidos: JPG, JPEG, PNG. Tamaño máximo: 10MB</small>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('premios.index') }}" class="btn btn-light m-0">Cancelar</a>
                    <button type="submit" class="btn bg-gradient-primary m-0 ms-2">Agregar Premio</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection