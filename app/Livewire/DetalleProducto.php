<?php

namespace App\Livewire;

use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Paciente;
use App\Models\Producto;
use App\Services\AuditoriaService;
use App\Services\InventarioService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/** Lotes, kardex y movimientos de un producto del inventario */
class DetalleProducto extends Component
{
    use WithPagination;

    #[Locked]
    public Producto $producto;

    /** entrada | salida | ajuste | baja | editar */
    public ?string $accion = null;
    #[Locked]
    public ?int $loteId = null;

    public array $entrada = [];
    public array $salida = [];
    public array $ajuste = [];
    public array $baja = [];
    public array $form = [];

    public string $buscarPaciente = '';

    public function mount(Producto $producto): void
    {
        $this->producto = $producto;
    }

    public function abrir(string $accion, ?int $loteId = null): void
    {
        $this->authorize('inventario.gestionar');
        abort_unless(in_array($accion, ['entrada', 'salida', 'ajuste', 'baja', 'editar'], true), 404);
        $this->resetErrorBag();
        $this->accion = $accion;
        $this->loteId = $loteId;
        $this->buscarPaciente = '';

        $lote = $loteId ? Lote::where('producto_id', $this->producto->id)->findOrFail($loteId) : null;
        match ($accion) {
            'entrada' => $this->entrada = ['codigo_lote' => '', 'fecha_vencimiento' => '', 'cantidad' => '', 'motivo' => 'compra',
                'proveedor' => '', 'costo_unitario' => '', 'registro_sanitario' => '', 'observacion' => ''],
            'salida' => $this->salida = ['cantidad' => '', 'motivo' => 'uso_atencion', 'paciente_id' => null, 'observacion' => ''],
            'ajuste' => $this->ajuste = ['conteo' => $lote->cantidad, 'observacion' => ''],
            'baja' => $this->baja = ['motivo' => $lote->vencido() ? 'vencido' : 'danado', 'cantidad' => $lote->cantidad, 'observacion' => ''],
            'editar' => $this->form = $this->producto->only(['nombre', 'tipo', 'dci', 'forma_farmaceutica', 'concentracion', 'presentacion', 'unidad', 'stock_minimo', 'notas']),
        };
    }

    public function cerrar(): void
    {
        $this->accion = null;
        $this->loteId = null;
    }

    public function elegirPaciente(int $id): void
    {
        $this->salida['paciente_id'] = Paciente::whereKey($id)->value('id');
        $this->buscarPaciente = '';
    }

    public function guardar(): void
    {
        $this->authorize('inventario.gestionar');
        try {
            $mensaje = $this->ejecutar(app(InventarioService::class));
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Los errores del servicio (cantidad, conteo…) se muestran bajo el campo del formulario abierto
            if ($this->accion !== 'editar' && !str_contains((string) array_key_first($e->errors()), '.')) {
                throw \Illuminate\Validation\ValidationException::withMessages(
                    collect($e->errors())->mapWithKeys(fn ($m, $k) => ["{$this->accion}.{$k}" => $m])->all());
            }
            throw $e;
        }
        if ($mensaje === null) {
            return;
        }

        $this->cerrar();
        $this->resetPage();
        $this->dispatch('notify', message: $mensaje, type: 'success');
    }

    private function ejecutar(InventarioService $servicio): ?string
    {
        switch ($this->accion) {
            case 'entrada':
                $d = $this->validate([
                    'entrada.codigo_lote' => 'required|string|max:60',
                    'entrada.fecha_vencimiento' => $this->producto->tipo === 'medicamento' ? ['required', 'date'] : ['nullable', 'date'],
                    'entrada.cantidad' => 'required|integer|min:1|max:1000000',
                    'entrada.motivo' => ['required', Rule::in(array_keys(MovimientoInventario::MOTIVOS['entrada']))],
                    'entrada.proveedor' => 'nullable|string|max:150',
                    'entrada.costo_unitario' => 'nullable|numeric|min:0|max:100000',
                    'entrada.registro_sanitario' => 'nullable|string|max:60',
                    'entrada.observacion' => 'nullable|string|max:500',
                ], ['entrada.fecha_vencimiento.required' => 'Los medicamentos deben registrar su fecha de vencimiento.'],
                ['entrada.codigo_lote' => 'el lote', 'entrada.cantidad' => 'la cantidad', 'entrada.fecha_vencimiento' => 'la fecha de vencimiento'])['entrada'];
                $servicio->entrada($this->producto, $d, $d['motivo'], $d['observacion'] ?: null);
                $mensaje = 'Entrada registrada.';
                break;

            case 'salida':
                $d = $this->validate([
                    'salida.cantidad' => 'required|integer|min:1|max:1000000',
                    'salida.motivo' => ['required', Rule::in(array_keys(MovimientoInventario::MOTIVOS['salida']))],
                    'salida.paciente_id' => 'nullable|integer|exists:pacientes,id',
                    'salida.observacion' => 'nullable|string|max:500',
                ], [], ['salida.cantidad' => 'la cantidad'])['salida'];
                $servicio->salida($this->producto, (int) $d['cantidad'], $d['motivo'], $d['paciente_id'] ?: null, null, $d['observacion'] ?: null);
                $mensaje = 'Salida registrada.';
                break;

            case 'ajuste':
                $d = $this->validate(['ajuste.conteo' => 'required|integer|min:0|max:1000000', 'ajuste.observacion' => 'required|string|max:500'],
                    ['ajuste.observacion.required' => 'Explique el motivo del ajuste.'], ['ajuste.conteo' => 'el conteo'])['ajuste'];
                $servicio->ajuste($this->lote(), (int) $d['conteo'], $d['observacion']);
                $mensaje = 'Ajuste registrado.';
                break;

            case 'baja':
                $d = $this->validate([
                    'baja.motivo' => ['required', Rule::in(array_keys(MovimientoInventario::MOTIVOS['baja']))],
                    'baja.cantidad' => 'required|integer|min:1|max:1000000',
                    'baja.observacion' => 'nullable|string|max:500',
                ], [], ['baja.cantidad' => 'la cantidad'])['baja'];
                $servicio->baja($this->lote(), $d['motivo'], (int) $d['cantidad'], $d['observacion'] ?: null);
                $mensaje = 'Baja registrada.';
                break;

            case 'editar':
                $datos = $this->validate(ListaInventario::reglasProducto(), [], ListaInventario::atributosProducto())['form'];
                $this->producto->update(array_map(fn ($v) => $v === '' ? null : $v, $datos));
                app(AuditoriaService::class)->registrar('editar', 'inventario', $this->producto->id, null, "Producto {$this->producto->descripcion()}");
                $mensaje = 'Producto actualizado.';
                break;

            default:
                return null;
        }

        return $mensaje;
    }

    public function alternarActivo(): void
    {
        $this->authorize('inventario.gestionar');
        $this->producto->update(['activo' => !$this->producto->activo]);
        app(AuditoriaService::class)->registrar('editar', 'inventario', $this->producto->id, null,
            ($this->producto->activo ? 'Reactivó ' : 'Desactivó ') . $this->producto->descripcion());
    }

    private function lote(): Lote
    {
        return Lote::where('producto_id', $this->producto->id)->findOrFail($this->loteId);
    }

    public function render()
    {
        $lotes = $this->producto->lotes()
            ->orderByRaw('cantidad = 0, fecha_vencimiento IS NULL, fecha_vencimiento ASC, id ASC')
            ->get();

        return view('livewire.detalle-producto', [
            'lotes' => $lotes,
            'disponible' => (int) $lotes->filter(fn ($l) => !$l->vencido())->sum('cantidad'),
            'vencido' => (int) $lotes->filter(fn ($l) => $l->vencido())->sum('cantidad'),
            'movimientos' => $this->producto->movimientos()->with('lote:id,codigo_lote', 'user:id,name', 'paciente:id,nombres,apellidos,cedula,identidad_reservada')
                ->latest('created_at')->latest('id')->paginate(15),
            'loteActual' => $this->loteId ? $lotes->firstWhere('id', $this->loteId) : null,
            'pacienteElegido' => !empty($this->salida['paciente_id']) ? Paciente::find($this->salida['paciente_id']) : null,
            'resultadosPacientes' => mb_strlen(trim($this->buscarPaciente)) >= 3
                ? Paciente::where(fn ($q) => $q->where('cedula', 'like', trim($this->buscarPaciente) . '%')
                    ->orWhere('apellidos', 'like', '%' . trim($this->buscarPaciente) . '%')
                    ->orWhere('nombres', 'like', '%' . trim($this->buscarPaciente) . '%'))->orderBy('apellidos')->take(5)->get()
                : collect(),
        ])->layout('layouts.app');
    }
}
