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
        $locale = $notifiable->locale ?? 'en';
        app()->setLocale($locale);
        \Illuminate\Support\Facades\Lang::setLocale($locale);
        $sender = \App\Models\SystemSetting::getValue('sender_name', 'Daet Listens');

        return (new MailMessage)
            ->from(config('mail.from.address'), $sender)
            ->subject(\App\Models\SystemSetting::getValue('subject_staff_welcome') ?: __('mail.welcome_subject'))
            ->view('mail.staff-welcome', [
                'user' => $notifiable,
                'tempPassword' => $this->tempPassword,
            ]);
    }
}
