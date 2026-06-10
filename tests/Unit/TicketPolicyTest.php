<?php

namespace Tests\Unit;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use PHPUnit\Framework\TestCase;

class TicketPolicyTest extends TestCase
{
    private TicketPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TicketPolicy;
    }

    private function user(int $id, string $role): User
    {
        return (new User)->forceFill(['id' => $id, 'role' => $role]);
    }

    private function ticket(int $requestorId, ?int $assigneeId): Ticket
    {
        return (new Ticket)->forceFill([
            'requestor_id' => $requestorId,
            'assignee_id' => $assigneeId,
        ]);
    }

    // --- view: everyone can see any ticket ---

    public function test_staff_can_view_a_ticket_they_do_not_own(): void
    {
        $ticket = $this->ticket(requestorId: 1, assigneeId: 2);
        $otherStaff = $this->user(99, User::ROLE_STAFF);

        $this->assertTrue($this->policy->view($otherStaff, $ticket));
    }

    public function test_it_support_can_view_a_ticket_not_assigned_to_them(): void
    {
        $ticket = $this->ticket(requestorId: 1, assigneeId: 2);
        $otherSupport = $this->user(99, User::ROLE_IT_SUPPORT);

        $this->assertTrue($this->policy->view($otherSupport, $ticket));
    }

    public function test_admin_can_view_any_ticket(): void
    {
        $ticket = $this->ticket(requestorId: 1, assigneeId: 2);

        $this->assertTrue($this->policy->view($this->user(99, User::ROLE_ADMIN), $ticket));
    }

    // --- comment: only Pelapor (requestor) + Penangan (assignee) + admin ---

    public function test_requestor_can_comment(): void
    {
        $ticket = $this->ticket(requestorId: 5, assigneeId: 2);

        $this->assertTrue($this->policy->comment($this->user(5, User::ROLE_STAFF), $ticket));
    }

    public function test_assignee_can_comment(): void
    {
        $ticket = $this->ticket(requestorId: 5, assigneeId: 2);

        $this->assertTrue($this->policy->comment($this->user(2, User::ROLE_IT_SUPPORT), $ticket));
    }

    public function test_it_support_who_is_the_requestor_can_comment_even_if_not_assigned(): void
    {
        // Regression: the old role-based policy blocked an it_support user who
        // was the Pelapor but not the Penangan.
        $ticket = $this->ticket(requestorId: 7, assigneeId: 2);

        $this->assertTrue($this->policy->comment($this->user(7, User::ROLE_IT_SUPPORT), $ticket));
    }

    public function test_admin_can_comment(): void
    {
        $ticket = $this->ticket(requestorId: 5, assigneeId: 2);

        $this->assertTrue($this->policy->comment($this->user(99, User::ROLE_ADMIN), $ticket));
    }

    public function test_unrelated_staff_cannot_comment(): void
    {
        $ticket = $this->ticket(requestorId: 5, assigneeId: 2);

        $this->assertFalse($this->policy->comment($this->user(99, User::ROLE_STAFF), $ticket));
    }

    public function test_unrelated_it_support_cannot_comment(): void
    {
        $ticket = $this->ticket(requestorId: 5, assigneeId: 2);

        $this->assertFalse($this->policy->comment($this->user(98, User::ROLE_IT_SUPPORT), $ticket));
    }
}
