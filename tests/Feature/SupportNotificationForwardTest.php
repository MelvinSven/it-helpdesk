<?php

namespace Tests\Feature;

use App\Mail\SupportNotificationCopy;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SupportNotificationForwardTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(User $requestor): Ticket
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Perangkat Keras',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Ticket::create([
            'ticket_code' => 'TKT-20260715-0001',
            'title' => 'Laptop mati total',
            'description' => 'Tidak menyala sama sekali.',
            'requestor_id' => $requestor->id,
            'category_id' => $categoryId,
            'priority' => 'high',
            'status' => 'new',
        ]);
    }

    public function test_notification_is_forwarded_to_support_email(): void
    {
        Mail::fake();
        config(['services.support.notification_email' => 'support@lixicon.com']);

        $requestor = User::factory()->create();
        $assignee = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $ticket = $this->makeTicket($requestor);

        $assignee->notify(new TicketAssignedNotification($ticket, $requestor));

        Mail::assertSent(SupportNotificationCopy::class, function (SupportNotificationCopy $mail) {
            return $mail->hasTo('support@lixicon.com')
                && str_contains($mail->body, 'TKT-20260715-0001');
        });
    }

    public function test_multi_recipient_notification_is_forwarded_only_once(): void
    {
        Mail::fake();
        config(['services.support.notification_email' => 'support@lixicon.com']);

        $requestor = User::factory()->create();
        $recipients = User::factory()->count(2)->create(['role' => User::ROLE_IT_SUPPORT]);
        $ticket = $this->makeTicket($requestor);

        Notification::send($recipients, new TicketAssignedNotification($ticket, $requestor));

        Mail::assertSent(SupportNotificationCopy::class, 1);
    }

    public function test_no_mail_sent_when_support_email_not_configured(): void
    {
        Mail::fake();
        config(['services.support.notification_email' => null]);

        $requestor = User::factory()->create();
        $assignee = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $ticket = $this->makeTicket($requestor);

        $assignee->notify(new TicketAssignedNotification($ticket, $requestor));

        Mail::assertNothingSent();
    }
}
