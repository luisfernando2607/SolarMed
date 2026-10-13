<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\User;
use App\Models\Medico;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Services\SriSignService;

class GestionUsuarios extends Component
{
    use WithPagination, WithFileUploads;

    public string $busqueda = '';
    private const ORDENABLES = ['cedula', 'created_at', 'email', 'name'];

    #[Locked]
    public string $sortBy = 'created_at';
    #[Locked]
    public string $sortDirection = 'desc';
    public bool $editMode = false;
    #[Locked]
    public ?int $editId = null;

    public $name;
    public $email;
    public $cedula;
    public $password;
    public $password_confirmation;
    public $rol;
    public $p12_file;
    public $p12_password;

    // Datos del prescriptor exigidos en la receta (ACESS-2023-0030 Art. 5 d y e)
    public $colegiatura;
    public $telefono_medico;

    // Datos tributarios del médico (emisor de sus facturas electrónicas)
    public $ruc;
    public $razon_social;
    public $nombre_comercial;
    public $direccion_matriz;
    public $obligado_contabilidad = 'NO';
    public $regimen_rimpe;
    public $sri_establecimiento = '001';
    public $sri_pto_emi = '001';

    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editId)],
            'cedula' => 'nullable|string|max:13',
            'rol' => 'required|exists:roles,name',
        ];

        if ($this->rol === 'medico') {
            $rules += [
                'ruc' => ['nullable', 'regex:/^\d{13}$/'],
                'colegiatura' => 'nullable|string|max:40',
                'telefono_medico' => 'nullable|string|max:40',
                'razon_social' => 'required_with:ruc|nullable|string|max:300',
                'nombre_comercial' => 'nullable|string|max:300',
                'direccion_matriz' => 'required_with:ruc|nullable|string|max:300',
                'obligado_contabilidad' => 'required|in:SI,NO',
                'regimen_rimpe' => ['nullable', Rule::in(Medico::REGIMENES_RIMPE)],
                'sri_establecimiento' => ['required', 'regex:/^\d{3}$/'],
                'sri_pto_emi' => ['required', 'regex:/^\d{3}$/'],
            ];
        }

        if ($this->editMode) {
            $rules['password'] = ['nullable', 'string', Password::defaults(), 'confirmed'];
            $rules['p12_file'] = 'nullable|file|mimes:p12,pfx|max:2048';
            $rules['p12_password'] = 'required_with:p12_file|nullable|string|max:255';
        } else {
            $rules['password'] = ['required', 'string', Password::defaults(), 'confirmed'];
        }

        return $rules;
    }

    protected $messages = [
        'name.required' => 'El nombre es obligatorio.',
        'email.required' => 'El email es obligatorio.',
        'email.unique' => 'Este email ya está registrado.',
        'password.required' => 'La contraseña es obligatoria.',
        'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        'password.letters' => 'La contraseña debe incluir letras.',
        'password.numbers' => 'La contraseña debe incluir números.',
        'password.confirmed' => 'Las contraseñas no coinciden.',
        'rol.required' => 'Debe seleccionar un rol.',
        'p12_file.mimes' => 'El archivo debe ser .p12 o .pfx.',
        'p12_file.max' => 'El archivo no debe superar los 2MB.',
        'p12_password.required_with' => 'Ingrese la contraseña del certificado.',
        'ruc.regex' => 'El RUC debe tener 13 dígitos.',
        'razon_social.required_with' => 'La razón social es obligatoria si se ingresa el RUC.',
        'direccion_matriz.required_with' => 'La dirección matriz es obligatoria si se ingresa el RUC.',
        'sri_establecimiento.regex' => 'Use 3 dígitos, por ejemplo 001.',
        'sri_pto_emi.regex' => 'Use 3 dígitos, por ejemplo 001.',
    ];

    public function resetForm(): void
    {
        $this->reset(['name', 'email', 'cedula', 'password', 'password_confirmation', 'rol', 'p12_file', 'p12_password', 'editMode', 'editId',
            'ruc', 'razon_social', 'nombre_comercial', 'direccion_matriz', 'obligado_contabilidad',
            'regimen_rimpe', 'sri_establecimiento', 'sri_pto_emi', 'colegiatura', 'telefono_medico']);
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
        $this->p12_password = null;
        $this->rol = $user->roles->first()?->name;

        $medico = $user->medico;
        $this->colegiatura = $medico?->colegiatura;
        $this->telefono_medico = $medico?->telefono;
        $this->ruc = $medico?->ruc;
        $this->razon_social = $medico?->razon_social;
        $this->nombre_comercial = $medico?->nombre_comercial;
        $this->direccion_matriz = $medico?->direccion_matriz;
        $this->obligado_contabilidad = $medico?->obligado_contabilidad ?? 'NO';
        $this->regimen_rimpe = $medico?->regimen_rimpe;
        $this->sri_establecimiento = $medico?->sri_establecimiento ?? '001';
        $this->sri_pto_emi = $medico?->sri_pto_emi ?? '001';
    }

    public function guardar(): void
    {
        $this->authorize('usuarios.gestionar');
        $this->validate();

        if ($this->p12_file && $this->rol === 'medico') {
            $this->verificarP12();
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'cedula' => $this->cedula,
        ];

        if ($this->password) {
            // Clave asignada por el administrador: la persona debe cambiarla al entrar (AM 5216-A Art. 15)
            $data['password'] = Hash::make($this->password);
            $data['debe_cambiar_password'] = true;
        }

        if ($this->editMode) {
            $user = User::findOrFail($this->editId);
            $user->update($data);
            $user->syncRoles([$this->rol]);

            if ($this->rol === 'medico') {
                $medico = $this->guardarDatosMedico($user);
                if ($this->p12_file) {
                    $this->guardarP12($user, $medico);
                }
            }

            $this->dispatch('notify', message: 'Usuario actualizado exitosamente.', type: 'success');
        } else {
            $user = User::create($data);
            $user->assignRole($this->rol);

            if ($this->rol === 'medico') {
                $medico = $this->guardarDatosMedico($user);
                if ($this->p12_file) {
                    $this->guardarP12($user, $medico);
                }
            }

            $this->dispatch('notify', message: 'Usuario creado exitosamente.', type: 'success');
        }

        $this->resetForm();
    }

    private function verificarP12(): void
    {
        $contenido = file_get_contents($this->p12_file->getRealPath());
        if (!SriSignService::leerP12($contenido, (string) $this->p12_password)) {
            throw ValidationException::withMessages([
                'p12_password' => 'No se pudo abrir el certificado: la contraseña es incorrecta o el archivo no es válido.',
            ]);
        }
    }

    private function guardarDatosMedico(User $user): Medico
    {
        $medico = Medico::firstOrCreate(
            ['user_id' => $user->id],
            ['nombres' => $user->name, 'apellidos' => '', 'especialidad_id' => \App\Models\Especialidad::value('id')]
        );

        $medico->update([
            'colegiatura' => trim((string) $this->colegiatura) ?: null,
            'telefono' => trim((string) $this->telefono_medico) ?: null,
            'ruc' => $this->ruc ?: null,
            'razon_social' => $this->razon_social ?: null,
            'nombre_comercial' => $this->nombre_comercial ?: null,
            'direccion_matriz' => $this->direccion_matriz ?: null,
            'obligado_contabilidad' => $this->obligado_contabilidad,
            'regimen_rimpe' => $this->regimen_rimpe ?: null,
            'sri_establecimiento' => $this->sri_establecimiento,
            'sri_pto_emi' => $this->sri_pto_emi,
        ]);

        return $medico;
    }

    private function guardarP12(User $user, Medico $medico): void
    {
        $path = $this->p12_file->store("certificados/{$user->id}", 'private');
        $medico->update([
            'p12_path' => $path,
            'p12_password_encrypted' => SriSignService::encryptPassword((string) $this->p12_password),
        ]);
    }

    public function ordenarPor($field): void
    {
        if (!in_array($field, self::ORDENABLES, true)) {
            return;
        }

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
