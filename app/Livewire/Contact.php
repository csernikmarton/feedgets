<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;

#[Layout('components.layouts.base', ['layout' => 'auth'])]
#[Title('Contact Us')]
class Contact extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|email|max:255')]
    public string $email = '';

    #[Validate('required|string|min:10')]
    public string $message = '';

    public string $turnstileResponse = '';

    public function submit()
    {
        $this->validate([
            'turnstileResponse' => ['required', app(Turnstile::class)],
        ], [
            'turnstileResponse.required' => 'Please verify you are human.',
        ]);

        $this->validate();

        Mail::raw("Name: {$this->name}\nEmail: {$this->email}\nMessage: {$this->message}", function ($message) {
            $message->to(config('admin.email'))
                ->subject('Feedgets Contact Form Submission')
                ->replyTo($this->email, $this->name);
        });

        session()->flash('status', 'Your message has been sent successfully!');

        $this->reset(['name', 'email', 'message', 'turnstileResponse']);
        $this->dispatch('refresh-turnstile');
    }

    public function render()
    {
        return view('livewire.contact');
    }
}
