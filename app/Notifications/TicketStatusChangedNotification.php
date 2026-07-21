<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Concerns\DeliversByMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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

    /**
     * Human-readable summary. Not stored in the payload — the frontend and the
     * support copy compose their own text from the status labels.
     */
    public function headline(): string
    {
        $label = Ticket::STATUS_LABELS[$this->newStatus] ?? $this->newStatus;

        return "Status tiket {$this->ticket->ticket_code} berubah menjadi {$label}.";
    }

    public function toArray(object $notifiable): array
    {
        $statusLabel = Ticket::STATUS_LABELS;

        return [
            'type' => 'ticket_status_changed',
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'ticket_title' => $this->ticket->title,
            'ticket_date' => $this->ticket->created_at?->format('d/m/Y H:i'),
            'ticket_description' => $this->ticket->description,
            'requestor_name' => $this->ticket->requestor?->name,
            'requestor_proyek' => $this->ticket->requestor?->proyek,
            'assignee_name' => $this->ticket->assignee?->name,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'old_status_label' => $statusLabel[$this->oldStatus] ?? $this->oldStatus,
            'new_status_label' => $statusLabel[$this->newStatus] ?? $this->newStatus,
            'actor_name' => $this->actor?->name,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('['.config('app.name').'] '.$this->headline())
            ->view('mail.new-ticket', [
                'leadLine' => 'Status tiket diperbarui. Pelapor:',
                'ticketCode' => $data['ticket_code'],
                'ticketTitle' => $data['ticket_title'],
                'ticketDate' => $data['ticket_date'],
                'requestorName' => $data['requestor_name'],
                'requestorProyek' => $data['requestor_proyek'],
                'assigneeName' => $data['assignee_name'],
                'ticketStatus' => $data['new_status_label'],
                'ticketDescription' => $data['ticket_description'],
                'ticketUrl' => route('tickets.show', $data['ticket_id']),
            ]);
    }
}
