<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Production deliverability probe for the ticket-email pipeline.
 *
 * Renders a real ticket notification and sends it SYNCHRONOUSLY to a dedicated
 * monitoring inbox, so a broken Gmail refresh token / bad creds / quota block
 * surfaces as a non-zero exit code instead of silently failing in a queue.
 *
 * It builds throwaway in-memory models — nothing is written to the database
 * and no real user is notified. Because the send is synchronous it bypasses
 * the queue, so it does NOT prove a worker is running; check that separately
 * (`systemctl status helpdesk-worker`).
 */
class NotificationsHealthcheck extends Command
{
    protected $signature = 'notifications:healthcheck
                            {--to= : Override recipient (defaults to HELPDESK_HEALTHCHECK_EMAIL)}';

    protected $description = 'Send one real ticket email to a monitoring inbox to verify mail delivery';

    public function handle(): int
    {
        $to = $this->option('to')
            ?? env('HELPDESK_HEALTHCHECK_EMAIL')
            ?? config('mail.from.address');

        if (empty($to)) {
            $this->error('No recipient. Set HELPDESK_HEALTHCHECK_EMAIL or pass --to=.');

            return self::FAILURE;
        }

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER=log — email goes to the log, not '.$to.'. Not a real delivery test.');
        }

        $ticket = $this->buildProbeTicket($to);
        $notification = new TicketResolvedNotification($ticket, $ticket->assignee);

        // toMail() ignores the notifiable; reuse its view/subject and send now.
        $message = $notification->toMail($ticket->requestor);

        try {
            Mail::send($message->view, $message->viewData, function ($mail) use ($message, $to) {
                $mail->to($to)->subject($message->subject);
            });
        } catch (Throwable $e) {
            $this->error('Send FAILED: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sent healthcheck email to '.$to.' via mailer ['.config('mail.default').']. Verify the inbox.');

        return self::SUCCESS;
    }

    /** In-memory ticket + users; never persisted. */
    private function buildProbeTicket(string $to): Ticket
    {
        $requestor = new User;
        $requestor->forceFill([
            'name' => 'Healthcheck Bot',
            'email' => $to,
            'proyek' => 'Monitoring',
        ]);
        $requestor->id = 0;

        $assignee = new User;
        $assignee->forceFill(['name' => 'IT Support']);
        $assignee->id = 0;

        $ticket = new Ticket;
        $ticket->forceFill([
            'ticket_code' => 'TKT-HEALTHCHECK',
            'title' => 'Healthcheck — abaikan email ini',
            'description' => 'Email uji otomatis. Konfirmasi pipeline notifikasi berjalan. Tidak perlu tindakan.',
            'status' => Ticket::STATUS_RESOLVED,
        ]);
        $ticket->id = 0;
        $ticket->created_at = now();
        $ticket->setRelation('requestor', $requestor);
        $ticket->setRelation('assignee', $assignee);

        return $ticket;
    }
}
