<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Notifications\Concerns\DeliversByMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewUnassignedTicketNotification extends Notification implements ShouldQueue
{
    use DeliversByMail;
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_unassigned_ticket',
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'ticket_title' => $this->ticket->title,
            'actor_name' => $this->ticket->requestor?->name,
            'message' => "Tiket baru {$this->ticket->ticket_code} menunggu penugasan.",
        ];
    }
}
