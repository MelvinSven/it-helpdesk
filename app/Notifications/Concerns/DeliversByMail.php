<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait DeliversByMail
{
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('['.config('app.name').'] '.$data['message'])
            ->greeting("Halo {$notifiable->name},")
            ->line($data['message'])
            ->line("Tiket: {$data['ticket_code']} — {$data['ticket_title']}")
            ->action('Lihat Tiket', route('tickets.show', $data['ticket_id']));
    }
}
