<?php

namespace App\Livewire\Traits;

use App\Models\Cie10Code;

trait WithCie10Autocomplete
{
    public $cie10Search = '';
    public $cie10Results = [];
    public $cie10Selected = null;

    public function updatedCie10Search()
    {
        if (strlen($this->cie10Search) < 1) {
            $this->cie10Results = [];
            return;
        }

        $this->cie10Results = Cie10Code::where('codigo', 'like', "%{$this->cie10Search}%")
            ->orWhere('descripcion', 'like', "%{$this->cie10Search}%")
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function selectCie10($codigo)
    {
        $code = Cie10Code::where('codigo', $codigo)->first();
        if ($code) {
            $this->codigo_cie10 = $code->codigo;
            $this->cie10Search = $code->codigo . ' — ' . $code->descripcion;
            $this->cie10Selected = $code->toArray();
            $this->cie10Results = [];
        }
    }

    public function clearCie10()
    {
        $this->codigo_cie10 = null;
        $this->cie10Search = '';
        $this->cie10Results = [];
        $this->cie10Selected = null;
    }
}
