<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketResolvedNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketStatusNotificationTest extends TestCase
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
            'ticket_code' => 'TKT-20260721-0009',
            'title' => 'Printer tidak terdeteksi',
            'description' => 'Driver hilang setelah update.',
            'requestor_id' => $requestor->id,
            'category_id' => $categoryId,
            'priority' => 'medium',
            'status' => Ticket::STATUS_NEW,
        ]);
    }

    public function test_status_change_notifies_requestor_and_admins(): void
    {
        Notification::fake();

        $requestor = User::factory()->create(['role' => User::ROLE_STAFF]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $itSupport = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $ticket = $this->makeTicket($requestor);
        $ticket->update(['assignee_id' => $itSupport->id]);

        $this->actingAs($itSupport)
            ->patch(route('tickets.status', $ticket), ['status' => Ticket::STATUS_IN_PROGRESS])
            ->assertRedirect();

        Notification::assertSentTo($requestor, TicketStatusChangedNotification::class);
        Notification::assertSentTo($admin, TicketStatusChangedNotification::class);
        Notification::assertNotSentTo($itSupport, TicketStatusChangedNotification::class);
    }

    public function test_admin_who_changed_the_status_is_not_notified(): void
    {
        Notification::fake();

        $requestor = User::factory()->create(['role' => User::ROLE_STAFF]);
        $actingAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $otherAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $ticket = $this->makeTicket($requestor);

        $this->actingAs($actingAdmin)
            ->patch(route('tickets.status', $ticket), ['status' => Ticket::STATUS_IN_PROGRESS])
            ->assertRedirect();

        Notification::assertNotSentTo($actingAdmin, TicketStatusChangedNotification::class);
        Notification::assertSentTo($otherAdmin, TicketStatusChangedNotification::class);
    }

    public function test_resolving_sends_confirmation_to_requestor_and_status_change_to_admin(): void
    {
        Notification::fake();

        $requestor = User::factory()->create(['role' => User::ROLE_STAFF]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $itSupport = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $ticket = $this->makeTicket($requestor);
        $ticket->update(['assignee_id' => $itSupport->id]);

        $this->actingAs($itSupport)
            ->patch(route('tickets.status', $ticket), ['status' => Ticket::STATUS_RESOLVED])
            ->assertRedirect();

        Notification::assertSentTo($requestor, TicketResolvedNotification::class);
        Notification::assertSentTo($admin, TicketStatusChangedNotification::class);
    }

    public function test_inactive_admins_are_not_notified(): void
    {
        Notification::fake();

        $requestor = User::factory()->create(['role' => User::ROLE_STAFF]);
        $inactiveAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => false]);
        $itSupport = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $ticket = $this->makeTicket($requestor);
        $ticket->update(['assignee_id' => $itSupport->id]);

        $this->actingAs($itSupport)
            ->patch(route('tickets.status', $ticket), ['status' => Ticket::STATUS_IN_PROGRESS])
            ->assertRedirect();

        Notification::assertNotSentTo($inactiveAdmin, TicketStatusChangedNotification::class);
    }

    public function test_status_change_mail_shows_proyek_and_new_status(): void
    {
        $requestor = User::factory()->create(['role' => User::ROLE_STAFF, 'proyek' => 'ECOGREEN']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $actor = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $ticket = $this->makeTicket($requestor);
        $ticket->status = Ticket::STATUS_IN_PROGRESS;

        $notification = new TicketStatusChangedNotification(
            $ticket,
            Ticket::STATUS_NEW,
            Ticket::STATUS_IN_PROGRESS,
            $actor
        );

        $this->assertEqualsCanonicalizing(['database', 'mail'], $notification->via($admin));

        $html = (string) $notification->toMail($admin)->render();

        $this->assertStringContainsString('ECOGREEN', $html);
        $this->assertStringContainsString('Status Tiket', $html);
        $this->assertStringContainsString('Dikerjakan', $html);
        $this->assertStringNotContainsString($requestor->email, $html);
        $this->assertStringContainsString(route('tickets.show', $ticket->id), $html);
    }
}
