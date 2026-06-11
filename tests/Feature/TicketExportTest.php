<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketExportTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(User $requestor): Ticket
    {
        $category = Category::create(['name' => 'Jaringan', 'is_active' => true]);

        return Ticket::create([
            'ticket_code' => Ticket::generateCode(),
            'title' => 'Wifi mati',
            'description' => 'Tidak ada koneksi.',
            'requestor_id' => $requestor->id,
            'category_id' => $category->id,
            'priority' => Ticket::PRIORITY_HIGH,
            'status' => Ticket::STATUS_NEW,
        ]);
    }

    public function test_staff_cannot_export_tickets(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->ticket($staff);

        $this->actingAs($staff)->get(route('tickets.export'))
            ->assertForbidden();
    }

    public function test_it_support_can_export_tickets(): void
    {
        $itSupport = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $this->ticket($itSupport);

        $response = $this->actingAs($itSupport)->get(route('tickets.export'));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            $response->headers->get('content-type'),
        );
    }

    public function test_admin_can_export_tickets(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->ticket($admin);

        $this->actingAs($admin)->get(route('tickets.export'))->assertOk();
    }
}
