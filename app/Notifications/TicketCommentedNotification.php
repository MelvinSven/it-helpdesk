<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Notifications\Concerns\DeliversByMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
            'ticket_date' => $this->ticket->created_at?->format('d/m/Y H:i'),
            'ticket_description' => $this->ticket->description,
            'requestor_name' => $this->ticket->requestor?->name,
            'requestor_proyek' => $this->ticket->requestor?->proyek,
            'assignee_name' => $this->ticket->assignee?->name,
            'ticket_status' => $this->ticket->status,
            'ticket_status_label' => $this->ticket->statusLabel(),
            'comment_body' => $this->comment->body,
            'actor_name' => $this->comment->user?->name,
            'message' => "Balasan baru pada tiket {$this->ticket->ticket_code} oleh {$this->comment->user?->name}.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('['.config('app.name').'] '.$data['message'])
            ->view('mail.new-ticket', [
                'leadLine' => 'Balasan baru pada tiket. Pelapor:',
                'ticketCode' => $data['ticket_code'],
                'ticketTitle' => $data['ticket_title'],
                'ticketDate' => $data['ticket_date'],
                'requestorName' => $data['requestor_name'],
                'requestorProyek' => $data['requestor_proyek'],
                'ticketStatus' => $data['ticket_status_label'],
                'assigneeName' => $data['assignee_name'],
                'ticketDescription' => $data['ticket_description'],
                'commentAuthor' => $data['actor_name'],
                'commentBody' => $data['comment_body'],
                'ticketUrl' => route('tickets.show', $data['ticket_id']),
            ]);
    }
}
