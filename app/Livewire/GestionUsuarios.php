<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\User;
use App\Models\Medico;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GestionUsuarios extends Component
{
    use WithPagination, WithFileUploads;

    public string $busqueda = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public bool $editMode = false;
    public ?int $editId = null;

    public $name;
    public $email;
    public $cedula;
    public $password;
    public $password_confirmation;
    public $rol;
    public $p12_file;

    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editId)],
            'cedula' => 'nullable|string|max:13',
            'rol' => 'required|exists:roles,name',
        ];

        if ($this->editMode) {
            $rules['password'] = 'nullable|string|min:8|confirmed';
            $rules['p12_file'] = 'nullable|file|mimes:p12,pfx|max:2048';
        } else {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        return $rules;
    }

    protected $messages = [
        'name.required' => 'El nombre es obligatorio.',
        'email.required' => 'El email es obligatorio.',
        'email.unique' => 'Este email ya está registrado.',
        'password.required' => 'La contraseña es obligatoria.',
        'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        'password.confirmed' => 'Las contraseñas no coinciden.',
        'rol.required' => 'Debe seleccionar un rol.',
        'p12_file.mimes' => 'El archivo debe ser .p12 o .pfx.',
        'p12_file.max' => 'El archivo no debe superar los 2MB.',
    ];

    public function resetForm(): void
    {
        $this->reset(['name', 'email', 'cedula', 'password', 'password_confirmation', 'rol', 'p12_file', 'editMode', 'editId']);
    }

    public function crear(): void
    {
        $this->authorize('usuarios.gestionar');
        $this->resetForm();
        $this->editId = 0;
    }

    public function editar(User $user): void
    {
        $this->authorize('usuarios.gestionar');
        $this->editMode = true;
        $this->editId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->cedula = $user->cedula;
        $this->password = null;
        $this->password_confirmation = null;
        $this->p12_file = null;
        $this->rol = $user->roles->first()?->name;
    }

    public function guardar(): void
    {
        $this->authorize('usuarios.gestionar');
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'cedula' => $this->cedula,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editMode) {
            $user = User::findOrFail($this->editId);
            $user->update($data);
            $user->syncRoles([$this->rol]);

            if ($this->p12_file && $this->rol === 'medico') {
                $this->guardarP12($user);
            }

            $this->dispatch('notify', message: 'Usuario actualizado exitosamente.', type: 'success');
        } else {
            $data['password'] = Hash::make($this->password);
            $user = User::create($data);
            $user->assignRole($this->rol);

            if ($this->p12_file && $this->rol === 'medico') {
                $this->guardarP12($user);
            }

            $this->dispatch('notify', message: 'Usuario creado exitosamente.', type: 'success');
        }

        $this->resetForm();
    }

    private function guardarP12(User $user): void
    {
        $medico = Medico::firstOrCreate(
            ['user_id' => $user->id],
            ['nombres' => $user->name, 'apellidos' => '', 'especialidad_id' => 1]
        );

        $path = $this->p12_file->store("certificados/{$user->id}", 'private');
        $medico->update(['p12_path' => $path]);
    }

    public function ordenarPor($field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function eliminar(User $user): void
    {
        $this->authorize('usuarios.gestionar');

        if ($user->id === auth()->id()) {
            $this->dispatch('notify', message: 'No puedes eliminarte a ti mismo.', type: 'error');
            return;
        }

        $user->delete();
        $this->dispatch('notify', message: 'Usuario eliminado exitosamente.', type: 'success');
    }

    public function render()
    {
        $query = User::query();

        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->busqueda}%")
                  ->orWhere('email', 'like', "%{$this->busqueda}%");
            });
        }

        $usuarios = $query->with('roles', 'medico')->orderBy($this->sortBy, $this->sortDirection)->paginate(15);

        return view('livewire.gestion-usuarios', [
            'usuarios' => $usuarios,
            'roles' => Role::all(),
        ])->layout('layouts.app');
    }
}
