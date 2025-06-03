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
    public $name;

    public $email;

    public $current_password;

    public $password;

    public $password_confirmation;

    public function mount()
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
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

    public function resendVerificationEmail()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        session()->flash('profile_message', __('Verification link sent! Please check your email.'));
    }
}
