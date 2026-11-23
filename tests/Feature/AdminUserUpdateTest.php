<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function payload(User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ], $overrides);
    }

    public function test_admin_can_change_user_id(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['user_id' => 'old-id']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), $this->payload($user, ['user_id' => 'new-id']))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('new-id', $user->fresh()->user_id);
    }

    public function test_user_id_must_stay_unique(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['user_id' => 'mine']);
        User::factory()->create(['user_id' => 'taken']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), $this->payload($user, ['user_id' => 'taken']))
            ->assertSessionHasErrors('user_id');

        $this->assertSame('mine', $user->fresh()->user_id);
    }
}
