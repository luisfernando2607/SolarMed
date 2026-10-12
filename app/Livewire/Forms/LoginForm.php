<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Valida las credenciales. Si el usuario no usa verificación en dos pasos inicia sesión y devuelve null;
     * si la usa, NO inicia sesión y devuelve el usuario para pedir el código.
     *
     * @throws ValidationException
     */
    public function authenticate(): ?User
    {
        $this->ensureIsNotRateLimited();

        $provider = Auth::getProvider();
        $user = $provider->retrieveByCredentials(['email' => $this->email]);

        if (! $user || ! $provider->validateCredentials($user, ['password' => $this->password])) {
            RateLimiter::hit($this->throttleKey());
            app(AuditoriaService::class)->registrar('login_fallido', 'sesion', null, null,
                ($user ? 'Contraseña incorrecta' : 'Correo no registrado') . ': ' . mb_substr($this->email, 0, 120), $user?->id);

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        if ($user->tieneDosFactores()) {
            return $user;
        }

        Auth::login($user, $this->remember);

        return null;
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));
        app(AuditoriaService::class)->registrar('bloqueo', 'sesion', null, null, 'Demasiados intentos: ' . mb_substr($this->email, 0, 120),
            User::where('email', $this->email)->value('id'));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
