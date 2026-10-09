<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notificação genérica armazenada no banco: ícone + texto + link.
 */
class PetDayNotification extends Notification
{
    public function __construct(
        public string $icon,
        public string $text,
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['icon' => $this->icon, 'text' => $this->text, 'url' => $this->url];
    }
}
