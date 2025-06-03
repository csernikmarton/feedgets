<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;

#[Layout('components.layouts.base', ['layout' => 'auth'])]
#[Title('Forgotten Password')]
class ForgotPassword extends Component
{
    public string $email = '';

    public string $turnstileResponse = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $validator = Validator::make(
            [
                'email' => $this->email,
                'turnstileResponse' => $this->turnstileResponse,
            ],
            [
                'email' => ['required', 'string', 'email'],
                'turnstileResponse' => ['required', app(Turnstile::class)],
            ],
            [
                'turnstileResponse.required' => 'Please verify you are human.',
            ]
        );

        if ($validator->fails()) {
            $this->dispatch('refresh-turnstile');
        }

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }
}
