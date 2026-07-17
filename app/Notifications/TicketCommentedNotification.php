<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Notifications\Concerns\DeliversByMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketCommentedNotification extends Notification implements ShouldQueue
{
    use DeliversByMail;
    use Queueable;

    public function __construct(public Ticket $ticket, public TicketComment $comment) {}

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_commented',
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'ticket_title' => $this->ticket->title,
            'actor_name' => $this->comment->user?->name,
            'message' => "Balasan baru pada tiket {$this->ticket->ticket_code} oleh {$this->comment->user?->name}.",
        ];
    }
}
