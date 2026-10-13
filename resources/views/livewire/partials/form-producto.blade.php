{{-- Campos del producto (usa $form). Compartido por la lista y el detalle del inventario --}}
@php
    $campo = 'w-full rounded-xl border-gray-200 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <label class="{{ $etiqueta }}">Nombre *</label>
        <input wire:model="form.nombre" type="text" class="{{ $campo }}" placeholder="Ej.: Paracetamol, Guantes de nitrilo talla M">
        @error('form.nombre') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="{{ $etiqueta }}">Tipo *</label>
        <select wire:model.live="form.tipo" class="{{ $campo }}">
            @foreach (\App\Models\Producto::TIPOS as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="{{ $etiqueta }}">Unidad de conteo *</label>
        <input wire:model="form.unidad" type="text" list="unidades-inventario" class="{{ $campo }}" placeholder="tableta, ampolla, unidad, caja…">
        <datalist id="unidades-inventario">
            @foreach (['unidad', 'tableta', 'cápsula', 'ampolla', 'frasco', 'sobre', 'caja', 'par', 'paquete', 'rollo'] as $u)<option value="{{ $u }}">@endforeach
        </datalist>
        @error('form.unidad') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    @if (($form['tipo'] ?? '') === 'medicamento')
        <div>
            <label class="{{ $etiqueta }}">Nombre genérico (DCI)</label>
            <input wire:model="form.dci" type="text" class="{{ $campo }}">
        </div>
        <div>
            <label class="{{ $etiqueta }}">Concentración</label>
            <input wire:model="form.concentracion" type="text" class="{{ $campo }}" placeholder="Ej.: 500 mg">
        </div>
        <div>
            <label class="{{ $etiqueta }}">Forma farmacéutica</label>
            <input wire:model="form.forma_farmaceutica" type="text" class="{{ $campo }}" placeholder="Ej.: tableta, solución inyectable">
        </div>
    @endif
    <div>
        <label class="{{ $etiqueta }}">Presentación</label>
        <input wire:model="form.presentacion" type="text" class="{{ $campo }}" placeholder="Ej.: caja x 100">
    </div>
    <div>
        <label class="{{ $etiqueta }}">Stock mínimo</label>
        <input wire:model="form.stock_minimo" type="number" min="0" class="{{ $campo }}">
        <p class="text-[11px] text-gray-400 mt-1">Avisa cuando el disponible llegue a este número. 0 = sin aviso.</p>
        @error('form.stock_minimo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label class="{{ $etiqueta }}">Notas</label>
        <textarea wire:model="form.notas" rows="2" class="{{ $campo }}" placeholder="Ej.: conservar en refrigeración"></textarea>
    </div>
</div>
