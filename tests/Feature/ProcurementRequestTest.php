<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProcurementRequestTest extends TestCase
{
    use RefreshDatabase;

    private function record(array $overrides = []): ProcurementRequest
    {
        return ProcurementRequest::create(array_merge([
            'request_number' => 'PB-2026-001',
            'employee_name' => 'Andi Karyawan',
            'requested_item' => 'Laptop',
            'request_date' => '2026-06-01',
            'notes' => null,
        ], $overrides));
    }

    public function test_admin_can_view_the_archive(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->record();

        $this->actingAs($admin)->get(route('admin.procurements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Procurements/Index')
                ->has('procurements.data', 1)
            );
    }

    public function test_non_admin_is_forbidden_from_the_archive(): void
    {
        foreach ([User::ROLE_STAFF, User::ROLE_IT_SUPPORT] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('admin.procurements.index'))->assertForbidden();
        }
    }

    public function test_admin_can_view_a_request_detail_with_linked_items(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = $this->record();
        $item = Item::create([
            'serial_number' => 'SN-PR-1',
            'item_name' => 'Laptop',
            'brand_name' => 'Acme',
            'type' => 'Laptop',
            'condition' => Item::CONDITION_GOOD,
            'status' => Item::STATUS_AVAILABLE,
        ]);
        $request->items()->attach($item->id);

        $this->actingAs($admin)->get(route('admin.procurements.show', $request))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Procurements/Show')
                ->where('procurement.id', $request->id)
                ->has('procurement.items', 1)
                ->where('procurement.items.0.id', $item->id)
            );
    }

    public function test_non_admin_cannot_view_a_request_detail(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $request = $this->record();

        $this->actingAs($staff)->get(route('admin.procurements.show', $request))->assertForbidden();
    }

    public function test_admin_can_create_a_request_with_a_pdf_form(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.procurements.store'), [
            'request_number' => 'PB-2026-010',
            'employee_name' => 'Budi',
            'requested_item' => 'Monitor 24"',
            'request_date' => '2026-06-08',
            'notes' => 'Pengganti monitor rusak.',
            'form_file' => UploadedFile::fake()->create('form.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('admin.procurements.index'));

        $record = ProcurementRequest::firstWhere('request_number', 'PB-2026-010');
        $this->assertNotNull($record);
        $this->assertEquals('Budi', $record->employee_name);
        $this->assertNotNull($record->form_file);
        Storage::disk('public')->assertExists($record->form_file);
    }

    public function test_form_file_is_required_on_create(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.procurements.store'), [
            'request_number' => 'PB-2026-099',
            'employee_name' => 'Dewi',
            'requested_item' => 'Printer',
            'request_date' => '2026-06-08',
        ])->assertSessionHasErrors('form_file');

        $this->assertDatabaseMissing('procurement_requests', ['request_number' => 'PB-2026-099']);
    }

    public function test_request_number_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->record(['request_number' => 'PB-DUP']);

        $this->actingAs($admin)->post(route('admin.procurements.store'), [
            'request_number' => 'PB-DUP',
            'employee_name' => 'Citra',
            'requested_item' => 'Keyboard',
            'request_date' => '2026-06-08',
        ])->assertSessionHasErrors('request_number');

        $this->assertEquals(1, ProcurementRequest::count());
    }

    public function test_non_admin_cannot_create(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($staff)->post(route('admin.procurements.store'), [
            'request_number' => 'PB-X',
            'employee_name' => 'X',
            'requested_item' => 'X',
            'request_date' => '2026-06-08',
        ])->assertForbidden();

        $this->assertDatabaseMissing('procurement_requests', ['request_number' => 'PB-X']);
    }

    public function test_admin_can_update_replacing_the_form_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $old = UploadedFile::fake()->image('old.jpg')->store('procurements', 'public');
        $record = $this->record(['form_file' => $old]);

        $this->actingAs($admin)->patch(route('admin.procurements.update', $record), [
            'request_number' => $record->request_number,
            'employee_name' => 'Andi Diperbarui',
            'requested_item' => $record->requested_item,
            'request_date' => '2026-06-01',
            'notes' => 'Catatan baru.',
            'form_file' => UploadedFile::fake()->image('new.jpg'),
        ])->assertRedirect(route('admin.procurements.index'));

        $record = $record->fresh();
        $this->assertEquals('Andi Diperbarui', $record->employee_name);
        $this->assertNotEquals($old, $record->form_file);
        Storage::disk('public')->assertExists($record->form_file);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_admin_can_delete_and_the_file_is_removed(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $file = UploadedFile::fake()->create('form.pdf', 50, 'application/pdf')->store('procurements', 'public');
        $record = $this->record(['form_file' => $file]);

        $this->actingAs($admin)->delete(route('admin.procurements.destroy', $record))
            ->assertRedirect(route('admin.procurements.index'));

        $this->assertDatabaseMissing('procurement_requests', ['id' => $record->id]);
        Storage::disk('public')->assertMissing($file);
    }
}
