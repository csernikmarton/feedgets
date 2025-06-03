<?php

namespace App\Listeners;

use App\Notifications\NewUserRegisteredNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;

class UserRegisteredListener
{
    public function __construct() {}

    public function handle(Registered $event): void
    {
        Notification::route('mail', config('admin.email'))
            ->notify(new NewUserRegisteredNotification($event->user));
    }
}
