@extends('layouts.user_type.auth')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header pb-0 px-3">
            <h6 class="mb-0">Nueva Promoción</h6>
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
            <form method="POST" action="{{ route('promociones.store') }}" enctype="multipart/form-data">
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
                    <label for="fecha_inicio">Fecha de Inicio</label>
                    <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" 
                        class="form-control @error('fecha_inicio') is-invalid @enderror" 
                        value="{{ old('fecha_inicio') }}" required>
                    @error('fecha_inicio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="fecha_fin">Fecha de Fin</label>
                    <input type="datetime-local" id="fecha_fin" name="fecha_fin" 
                        class="form-control @error('fecha_fin') is-invalid @enderror" 
                        value="{{ old('fecha_fin') }}" required>
                    @error('fecha_fin')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="puntos_por_ticket">Puntos por Ticket</label>
                    <input type="number" id="puntos_por_ticket" name="puntos_por_ticket" 
                        class="form-control @error('puntos_por_ticket') is-invalid @enderror" 
                        value="{{ old('puntos_por_ticket') }}" min="1" required>
                    @error('puntos_por_ticket')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="monto_minimo">Monto Mínimo</label>
                    <input type="number" id="monto_minimo" name="monto_minimo" 
                        class="form-control @error('monto_minimo') is-invalid @enderror" 
                        value="{{ old('monto_minimo') }}" step="0.01" min="0">
                    @error('monto_minimo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" 
                        class="form-control @error('estado') is-invalid @enderror" required>
                        <option value="activa" {{ old('estado') == 'activa' ? 'selected' : '' }}>Activa</option>
                        <option value="inactiva" {{ old('estado') == 'inactiva' ? 'selected' : '' }}>Inactiva</option>
                        <option value="expirada" {{ old('estado') == 'expirada' ? 'selected' : '' }}>Expirada</option>
                    </select>
                    @error('estado')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="imagen">Imagen de la Promoción</label>
                    <input type="file" id="imagen" name="imagen" 
                        class="form-control @error('imagen') is-invalid @enderror" 
                        accept="image/jpeg,image/png,image/jpg">
                    @error('imagen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Formatos permitidos: JPG, JPEG, PNG. Tamaño máximo: 10MB</small>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('promociones.index') }}" class="btn btn-light m-0">Cancelar</a>
                    <button type="submit" class="btn bg-gradient-primary m-0 ms-2">Agregar Promoción</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection