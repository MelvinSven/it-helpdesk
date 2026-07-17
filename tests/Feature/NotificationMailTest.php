<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewUnassignedTicketNotification;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationMailTest extends TestCase
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
            'ticket_code' => 'TKT-20260715-0002',
            'title' => 'Printer tidak terdeteksi',
            'description' => 'Driver hilang setelah update.',
            'requestor_id' => $requestor->id,
            'category_id' => $categoryId,
            'priority' => 'medium',
            'status' => 'new',
        ]);
    }

    public function test_ticket_notifications_use_mail_and_database_channels(): void
    {
        $requestor = User::factory()->create();
        $assignee = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $ticket = $this->makeTicket($requestor);

        $assigned = new TicketAssignedNotification($ticket, $requestor);
        $unassigned = new NewUnassignedTicketNotification($ticket);

        $this->assertEqualsCanonicalizing(['database', 'mail'], $assigned->via($assignee));
        $this->assertEqualsCanonicalizing(['database', 'mail'], $unassigned->via($assignee));
    }

    public function test_mail_message_contains_ticket_code_and_link(): void
    {
        $requestor = User::factory()->create();
        $assignee = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $ticket = $this->makeTicket($requestor);

        $mail = (new TicketAssignedNotification($ticket, $requestor))->toMail($assignee);

        $this->assertStringContainsString('TKT-20260715-0002', $mail->subject);
        $this->assertSame(route('tickets.show', $ticket->id), $mail->actionUrl);
    }
}
