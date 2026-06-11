<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketActivityVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function ticketFor(User $requestor, ?User $assignee = null): Ticket
    {
        $category = Category::create(['name' => 'Perangkat Keras', 'is_active' => true]);

        $ticket = Ticket::create([
            'ticket_code' => Ticket::generateCode(),
            'title' => 'Printer rusak',
            'description' => 'Tidak bisa cetak.',
            'requestor_id' => $requestor->id,
            'assignee_id' => $assignee?->id,
            'category_id' => $category->id,
            'priority' => Ticket::PRIORITY_MEDIUM,
            'status' => Ticket::STATUS_NEW,
        ]);

        TicketActivity::record($ticket, $requestor, TicketActivity::ACTION_CREATED);

        return $ticket;
    }

    public function test_staff_owner_cannot_see_the_activity_log(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $ticket = $this->ticketFor($staff);

        $this->actingAs($staff)->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tickets/Show')
                ->where('can.view_activities', false)
                ->missing('ticket.activities')
            );
    }

    public function test_it_support_can_see_the_activity_log(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $itSupport = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $ticket = $this->ticketFor($staff, $itSupport);

        $this->actingAs($itSupport)->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tickets/Show')
                ->where('can.view_activities', true)
                ->has('ticket.activities', 1)
            );
    }

    public function test_admin_can_see_the_activity_log(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $ticket = $this->ticketFor($staff);

        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tickets/Show')
                ->where('can.view_activities', true)
                ->has('ticket.activities', 1)
            );
    }
}
