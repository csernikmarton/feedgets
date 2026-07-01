<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ApiTokens extends Component
{
    public string $tokenName = '';

    /**
     * The plain-text token shown once, immediately after creation.
     */
    public ?string $plainTextToken = null;

    public function render()
    {
        return view('livewire.settings.api-tokens', [
            'tokens' => Auth::user()->tokens()->latest()->get(),
        ]);
    }

    public function createToken(): void
    {
        $this->validate([
            'tokenName' => ['required', 'string', 'max:255'],
        ]);

        $this->plainTextToken = Auth::user()->createToken($this->tokenName)->plainTextToken;

        $this->reset('tokenName');
        $this->dispatch('token-created');
    }

    public function deleteToken(string $tokenId): void
    {
        Auth::user()->tokens()->whereKey($tokenId)->delete();

        session()->flash('token_message', __('Token revoked.'));
    }
}
