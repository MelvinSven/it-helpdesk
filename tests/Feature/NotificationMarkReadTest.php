<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationMarkReadTest extends TestCase
{
    use RefreshDatabase;

    private function makeNotification(User $user, ?string $readAt = null): string
    {
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\TicketCommentedNotification',
            'data' => ['message' => 'hi', 'ticket_id' => 1],
            'read_at' => $readAt,
        ]);

        return $id;
    }

    public function test_mark_single_notification_read(): void
    {
        $user = User::factory()->create();
        $id = $this->makeNotification($user);

        $this->actingAs($user)
            ->postJson(route('notifications.read', $id))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNotNull($user->fresh()->notifications()->find($id)->read_at);
    }

    public function test_mark_all_notifications_read(): void
    {
        $user = User::factory()->create();
        $this->makeNotification($user);
        $this->makeNotification($user);

        $this->actingAs($user)
            ->postJson(route('notifications.read-all'))
            ->assertOk();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_index_returns_unread_count(): void
    {
        $user = User::factory()->create();
        $this->makeNotification($user);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }
}
