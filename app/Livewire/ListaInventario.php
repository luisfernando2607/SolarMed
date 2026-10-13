<?php

namespace App\Livewire;

use App\Models\Producto;
use App\Services\AuditoriaService;
use App\Services\InventarioService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Inventario de insumos y medicamentos: existencias, vencimientos y alertas */
class ListaInventario extends Component
{
    #[Url(except: '')]
    public string $busqueda = '';
    #[Url(except: '')]
    public string $tipo = '';
    #[Url(except: '')]
    public string $filtro = '';

    public bool $mostrarFormulario = false;
    public array $form = [];

    public const FILTROS = ['stock_bajo' => 'Stock bajo', 'por_vencer' => 'Por vencer', 'vencidos' => 'Vencidos'];

    public static function reglasProducto(): array
    {
        return [
            'form.nombre' => 'required|string|max:150',
            'form.tipo' => ['required', Rule::in(array_keys(Producto::TIPOS))],
            'form.dci' => 'nullable|string|max:150',
            'form.forma_farmaceutica' => 'nullable|string|max:80',
            'form.concentracion' => 'nullable|string|max:80',
            'form.presentacion' => 'nullable|string|max:120',
            'form.unidad' => 'required|string|max:40',
            'form.stock_minimo' => 'required|integer|min:0|max:1000000',
            'form.notas' => 'nullable|string|max:1000',
        ];
    }

    public static function atributosProducto(): array
    {
        return [
            'form.nombre' => 'el nombre', 'form.tipo' => 'el tipo', 'form.unidad' => 'la unidad',
            'form.stock_minimo' => 'el stock mínimo', 'form.dci' => 'el nombre genérico',
        ];
    }

    public function nuevoProducto(): void
    {
        $this->authorize('inventario.gestionar');
        $this->resetErrorBag();
        $this->form = ['nombre' => '', 'tipo' => 'insumo', 'dci' => '', 'forma_farmaceutica' => '', 'concentracion' => '',
            'presentacion' => '', 'unidad' => 'unidad', 'stock_minimo' => 0, 'notas' => ''];
        $this->mostrarFormulario = true;
    }

    public function guardarProducto(): void
    {
        $this->authorize('inventario.gestionar');
        $datos = $this->validate(self::reglasProducto(), [], self::atributosProducto())['form'];
        $p = Producto::create(array_map(fn ($v) => $v === '' ? null : $v, $datos) + ['activo' => true]);
        app(AuditoriaService::class)->registrar('crear', 'inventario', $p->id, null, "Producto {$p->descripcion()}");

        $this->mostrarFormulario = false;
        $this->redirect(route('inventario.producto', $p), navigate: true);
    }

    private function productos(): Collection
    {
        $hoy = today();
        $limite = $hoy->copy()->addDays(Producto::DIAS_POR_VENCER);

        $productos = Producto::query()
            ->where('activo', true)
            ->when($this->busqueda !== '', fn ($q) => $q->where(fn ($w) => $w->where('nombre', 'like', "%{$this->busqueda}%")
                ->orWhere('dci', 'like', "%{$this->busqueda}%")))
            ->when(array_key_exists($this->tipo, Producto::TIPOS), fn ($q) => $q->where('tipo', $this->tipo))
            ->withSum(['lotes as disponible' => fn ($q) => $q->disponibles()], 'cantidad')
            ->withSum(['lotes as vencido' => fn ($q) => $q->where('cantidad', '>', 0)->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '<', $hoy)], 'cantidad')
            ->withMin(['lotes as proximo_vencimiento' => fn ($q) => $q->disponibles()->whereNotNull('fecha_vencimiento')], 'fecha_vencimiento')
            ->orderBy('nombre')
            ->get();

        return $productos->map(function (Producto $p) use ($limite) {
            $p->disponible = (int) $p->disponible;
            $p->vencido = (int) $p->vencido;
            $p->por_vencer = $p->proximo_vencimiento && \Carbon\Carbon::parse($p->proximo_vencimiento)->lte($limite);
            $p->stock_bajo = $p->stock_minimo > 0 && $p->disponible <= $p->stock_minimo;

            return $p;
        })->filter(fn ($p) => match ($this->filtro) {
            'stock_bajo' => $p->stock_bajo,
            'por_vencer' => $p->por_vencer,
            'vencidos' => $p->vencido > 0,
            default => true,
        })->values();
    }

    public function render()
    {
        return view('livewire.lista-inventario', [
            'productos' => $this->productos(),
            'alertas' => app(InventarioService::class)->alertas(),
            'activos' => Producto::where('activo', true)->count(),
        ])->layout('layouts.app');
    }
}
