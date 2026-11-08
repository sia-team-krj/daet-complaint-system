<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->locale ?? 'en';
        app()->setLocale($locale);
        Lang::setLocale($locale);

        return (new MailMessage)
            ->subject(\App\Models\SystemSetting::getValue('subject_reset') ?: Lang::get('mail.reset_subject'))
            ->view('mail.password-reset', [
                'url' => $this->url,
                'user' => $notifiable,
            ]);
    }
}
