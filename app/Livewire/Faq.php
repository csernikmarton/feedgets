<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.base', ['layout' => 'auth', 'widthClass' => 'sm:max-w-4xl'])]
#[Title('FAQ')]
class Faq extends Component
{
    public function render()
    {
        return view('livewire.faq');
    }
}
