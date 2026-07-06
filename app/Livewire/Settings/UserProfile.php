<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.base', ['layout' => 'app'])]
#[Title('Profile')]
class UserProfile extends Component
{
    /**
     * Available color schemes: key => [label, swatch color].
     * `default` keeps the current blue theme (no CSS override).
     *
     * @var array<string, array{label: string, swatch: string}>
     */
    public const SCHEMES = [
        'default' => ['label' => 'Blue (default)', 'swatch' => '#2563eb'],
        'indigo' => ['label' => 'Indigo', 'swatch' => '#4f46e5'],
        'violet' => ['label' => 'Violet', 'swatch' => '#7c3aed'],
        'fuchsia' => ['label' => 'Fuchsia', 'swatch' => '#c026d3'],
        'rose' => ['label' => 'Rose', 'swatch' => '#e11d48'],
        'orange' => ['label' => 'Orange', 'swatch' => '#ea580c'],
        'amber' => ['label' => 'Amber', 'swatch' => '#d97706'],
        'emerald' => ['label' => 'Emerald', 'swatch' => '#059669'],
        'teal' => ['label' => 'Teal', 'swatch' => '#0d9488'],
        'cyan' => ['label' => 'Cyan', 'swatch' => '#0891b2'],
    ];

    public $name;

    public $email;

    public $current_password;

    public $password;

    public $password_confirmation;

    public $colorScheme;

    public function mount()
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->colorScheme = Auth::user()->color_scheme ?? 'default';
    }

    public function render()
    {
        return view('livewire.settings.user-profile');
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.Auth::id(),
        ]);

        $user = User::find(Auth::id());
        $user->name = $this->name;

        if ($this->email !== $user->email) {
            $user->email = $this->email;
            $user->email_verified_at = null;
        }

        $user->save();

        session()->flash('profile_message', __('Profile updated successfully.'));
        $this->dispatch('saved');

        if ($user->wasChanged('email')) {
            Auth::user()->sendEmailVerificationNotification();
            session()->flash('profile_message', __('Profile updated successfully. Please verify your new email address.'));
        }
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::find(Auth::id());
        $user->password = Hash::make($this->password);
        $user->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('password_message', __('Password updated successfully.'));
        $this->dispatch('password-saved');
    }

    public function updateColorScheme(string $scheme)
    {
        if (! array_key_exists($scheme, self::SCHEMES)) {
            return;
        }

        $user = User::find(Auth::id());
        $user->color_scheme = $scheme === 'default' ? null : $scheme;
        $user->save();

        $this->colorScheme = $scheme;

        session()->flash('color_scheme_message', __('Color scheme updated.'));
    }

    public function resendVerificationEmail()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        session()->flash('profile_message', __('Verification link sent! Please check your email.'));
    }
}
