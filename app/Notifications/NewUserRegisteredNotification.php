<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification
{
    public function __construct(protected User $user) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New User Registration: '.$this->user->name)
            ->line('A new user has registered on your site.')
            ->line('Name: '.$this->user->name)
            ->line('Email: '.$this->user->email)
            ->line('Registered at: '.$this->user->created_at);
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
