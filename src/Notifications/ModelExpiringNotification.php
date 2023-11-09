<?php

namespace Maize\ModelExpires\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ModelExpiringNotification extends Notification
{
    public function __construct(
        private Model $model
    ) {
        //
    }

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting(__('Expiration notification'))
            ->line(__('The :Model :key is expiring in :amount days', [
                'model' => $this->model->getMorphClass(),
                'key' => $this->model->getKey(),
                /** @phpstan-ignore-next-line */
                'amount' => $this->model->getDaysLeftToExpiration(),
            ]));
    }
}
