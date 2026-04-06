<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffWelcomeNotification extends Notification
{
    use Queueable;

    public string $tempPassword;

    public function __construct(string $tempPassword)
    {
        $this->tempPassword = $tempPassword;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to Daet Listens — Staff Account Created')
            ->view('mail.staff-welcome', [
                'user' => $notifiable,
                'tempPassword' => $this->tempPassword,
            ]);
    }
}
