<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Notifications\Concerns\DeliversByMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
            'ticket_date' => $this->ticket->created_at?->format('d/m/Y H:i'),
            'ticket_description' => $this->ticket->description,
            'requestor_name' => $this->ticket->requestor?->name,
            'ticket_status' => $this->ticket->status,
            'ticket_status_label' => $this->ticket->statusLabel(),
            'requestor_proyek' => $this->ticket->requestor?->proyek,
            'assignee_name' => $this->ticket->assignee?->name,
            'actor_name' => $this->ticket->requestor?->name,
            'message' => "Tiket baru {$this->ticket->ticket_code} menunggu penugasan.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('['.config('app.name').'] '.$data['message'])
            ->view('mail.new-ticket', [
                'leadLine' => 'Tiket baru dibuat oleh:',
                'ticketCode' => $data['ticket_code'],
                'ticketTitle' => $data['ticket_title'],
                'ticketDate' => $data['ticket_date'],
                'requestorName' => $data['requestor_name'],
                'ticketStatus' => $data['ticket_status_label'],
                'requestorProyek' => $data['requestor_proyek'],
                'assigneeName' => $data['assignee_name'],
                'ticketDescription' => $data['ticket_description'],
                'ticketUrl' => route('tickets.show', $data['ticket_id']),
            ]);
    }
}
