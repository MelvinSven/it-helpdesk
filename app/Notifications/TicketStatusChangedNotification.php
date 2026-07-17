<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Concerns\DeliversByMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use DeliversByMail;
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $oldStatus,
        public string $newStatus,
        public ?User $actor = null
    ) {}

    public function toArray(object $notifiable): array
    {
        $statusLabel = [
            'new' => 'Baru',
            'in_progress' => 'Dikerjakan',
            'resolved' => 'Selesai',
        ];

        return [
            'type' => 'ticket_status_changed',
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'ticket_title' => $this->ticket->title,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'actor_name' => $this->actor?->name,
            'message' => "Status tiket {$this->ticket->ticket_code} berubah menjadi {$statusLabel[$this->newStatus]}.",
        ];
    }
}
