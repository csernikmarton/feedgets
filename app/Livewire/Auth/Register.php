<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;

#[Layout('components.layouts.base', ['layout' => 'auth'])]
#[Title('Registration')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $turnstileResponse = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validator = Validator::make(
            [
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'turnstileResponse' => $this->turnstileResponse,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'string', 'confirmed', Rules\Password::min(10)->mixedCase()->numbers()->symbols()->uncompromised()],
                'turnstileResponse' => ['required', app(Turnstile::class)],
            ],
            [
                'turnstileResponse.required' => 'Please verify you are human.',
            ]
        );

        if ($validator->fails()) {
            $this->dispatch('refresh-turnstile');
        }

        $validated = $validator->validated();
        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}
