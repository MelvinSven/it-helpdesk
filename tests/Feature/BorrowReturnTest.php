<?php

namespace Tests\Feature;

use App\Models\BorrowRecord;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BorrowReturnTest extends TestCase
{
    use RefreshDatabase;

    private function item(array $overrides = []): Item
    {
        return Item::create(array_merge([
            'serial_number' => 'SN-TEST-0001',
            'item_name' => 'Test Laptop',
            'brand_name' => 'Acme',
            'mac_address' => null,
            'type' => 'Laptop',
            'condition' => Item::CONDITION_GOOD,
            'status' => Item::STATUS_AVAILABLE,
        ], $overrides));
    }

    public function test_any_user_can_borrow_an_available_item_and_status_flips(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);
        $borrower = User::factory()->create(['name' => 'Andi Peminjam']);
        $item = $this->item();

        $response = $this->actingAs($user)->post(route('borrows.store'), [
            'item_id' => $item->id,
            'borrower_id' => $borrower->id,
            'borrow_date' => '2026-06-02',
            'purpose' => 'Presentasi klien',
        ]);

        $response->assertRedirect(route('borrows.index'));

        $this->assertEquals(Item::STATUS_BORROWED, $item->fresh()->status);
        $this->assertDatabaseHas('borrow_records', [
            'item_id' => $item->id,
            'item_name' => 'Test Laptop',
            'serial_number' => 'SN-TEST-0001',
            // Borrower is linked to the real user and the name is snapshotted.
            'borrower_id' => $borrower->id,
            'borrower_name' => 'Andi Peminjam',
            'status' => BorrowRecord::STATUS_BORROWED,
        ]);
    }

    public function test_borrower_must_be_an_existing_active_user(): void
    {
        $user = User::factory()->create();
        $inactive = User::factory()->create(['is_active' => false]);
        $item = $this->item();

        // Missing borrower.
        $this->actingAs($user)->post(route('borrows.store'), [
            'item_id' => $item->id,
            'borrow_date' => '2026-06-02',
            'purpose' => 'Tanpa peminjam',
        ])->assertSessionHasErrors('borrower_id');

        // Non-existent / inactive borrower.
        $this->actingAs($user)->post(route('borrows.store'), [
            'item_id' => $item->id,
            'borrower_id' => $inactive->id,
            'borrow_date' => '2026-06-02',
            'purpose' => 'Peminjam nonaktif',
        ])->assertSessionHasErrors('borrower_id');

        $this->assertEquals(0, BorrowRecord::count());
        $this->assertEquals(Item::STATUS_AVAILABLE, $item->fresh()->status);
    }

    public function test_a_borrowed_item_cannot_be_borrowed_again(): void
    {
        $user = User::factory()->create();
        $item = $this->item(['status' => Item::STATUS_BORROWED]);

        // A borrowed item isn't a valid choice — validation rejects item_id.
        $this->actingAs($user)->post(route('borrows.store'), [
            'item_id' => $item->id,
            'borrower_id' => $user->id,
            'borrow_date' => '2026-06-02',
            'purpose' => 'Coba-coba',
        ])->assertSessionHasErrors('item_id');

        $this->assertEquals(0, BorrowRecord::count());
        $this->assertEquals(Item::STATUS_BORROWED, $item->fresh()->status);
    }

    public function test_borrow_form_loads(): void
    {
        $user = User::factory()->create();
        $this->item();

        $this->actingAs($user)->get(route('borrows.create'))->assertOk();
    }

    public function test_returning_an_item_frees_it_and_syncs_condition(): void
    {
        $user = User::factory()->create();
        $item = $this->item(['status' => Item::STATUS_BORROWED, 'condition' => Item::CONDITION_GOOD]);
        $record = BorrowRecord::create([
            'item_id' => $item->id,
            'borrower_id' => $user->id,
            'item_name' => $item->item_name,
            'serial_number' => $item->serial_number,
            'borrower_name' => 'Andi',
            'borrow_date' => '2026-06-01',
            'purpose' => 'Presentasi',
            'status' => BorrowRecord::STATUS_BORROWED,
        ]);

        $this->actingAs($user)->patch(route('borrows.return.store', $record), [
            'return_date' => '2026-06-02',
            'return_condition' => Item::CONDITION_MINOR_DAMAGE,
            'notes' => 'Lecet di sudut.',
        ])->assertRedirect(route('borrows.index'));

        $item = $item->fresh();
        $this->assertEquals(Item::STATUS_AVAILABLE, $item->status);
        $this->assertEquals(Item::CONDITION_MINOR_DAMAGE, $item->condition);

        $record = $record->fresh();
        $this->assertEquals(BorrowRecord::STATUS_RETURNED, $record->status);
        $this->assertEquals(Item::CONDITION_MINOR_DAMAGE, $record->return_condition);
    }

    public function test_an_already_returned_record_cannot_be_returned_again(): void
    {
        $user = User::factory()->create();
        $item = $this->item();
        $record = BorrowRecord::create([
            'item_id' => $item->id,
            'borrower_id' => $user->id,
            'item_name' => $item->item_name,
            'serial_number' => $item->serial_number,
            'borrower_name' => 'Andi',
            'borrow_date' => '2026-06-01',
            'purpose' => 'Presentasi',
            'status' => BorrowRecord::STATUS_RETURNED,
        ]);

        $this->actingAs($user)->patch(route('borrows.return.store', $record), [
            'return_date' => '2026-06-02',
            'return_condition' => Item::CONDITION_GOOD,
        ])->assertNotFound();
    }

    public function test_only_the_borrower_can_submit_a_return(): void
    {
        $borrower = User::factory()->create();
        $other = User::factory()->create();
        $item = $this->item(['status' => Item::STATUS_BORROWED]);
        $record = BorrowRecord::create([
            'item_id' => $item->id,
            'borrower_id' => $borrower->id,
            'item_name' => $item->item_name,
            'serial_number' => $item->serial_number,
            'borrower_name' => $borrower->name,
            'borrow_date' => '2026-06-01',
            'purpose' => 'Presentasi',
            'status' => BorrowRecord::STATUS_BORROWED,
        ]);

        // A non-borrower is blocked, and nothing changes.
        $this->actingAs($other)->patch(route('borrows.return.store', $record), [
            'return_date' => '2026-06-02',
            'return_condition' => Item::CONDITION_GOOD,
        ])->assertForbidden();

        $this->assertEquals(Item::STATUS_BORROWED, $item->fresh()->status);
        $this->assertEquals(BorrowRecord::STATUS_BORROWED, $record->fresh()->status);

        // The borrower succeeds.
        $this->actingAs($borrower)->patch(route('borrows.return.store', $record), [
            'return_date' => '2026-06-02',
            'return_condition' => Item::CONDITION_GOOD,
        ])->assertRedirect(route('borrows.index'));

        $this->assertEquals(BorrowRecord::STATUS_RETURNED, $record->fresh()->status);
    }

    public function test_a_non_borrower_can_still_view_the_return_page(): void
    {
        $borrower = User::factory()->create();
        $other = User::factory()->create();
        $item = $this->item(['status' => Item::STATUS_BORROWED]);
        $record = BorrowRecord::create([
            'item_id' => $item->id,
            'borrower_id' => $borrower->id,
            'item_name' => $item->item_name,
            'serial_number' => $item->serial_number,
            'borrower_name' => $borrower->name,
            'borrow_date' => '2026-06-01',
            'purpose' => 'Presentasi',
            'status' => BorrowRecord::STATUS_BORROWED,
        ]);

        $this->actingAs($other)->get(route('borrows.return.create', $record))->assertOk();
    }

    public function test_returned_records_remain_in_the_list_by_default(): void
    {
        $user = User::factory()->create();
        $item = $this->item();
        // One returned record — it must not disappear from the default view.
        BorrowRecord::create([
            'item_id' => $item->id,
            'borrower_id' => $user->id,
            'item_name' => $item->item_name,
            'serial_number' => $item->serial_number,
            'borrower_name' => $user->name,
            'borrow_date' => '2026-06-01',
            'purpose' => 'Presentasi',
            'status' => BorrowRecord::STATUS_RETURNED,
            'return_date' => '2026-06-02',
            'return_condition' => Item::CONDITION_GOOD,
        ]);

        $this->actingAs($user)->get(route('borrows.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Borrows/Index')
                ->has('borrows.data', 1)
                ->where('borrows.data.0.status', BorrowRecord::STATUS_RETURNED)
            );
    }

    public function test_borrow_detail_page_loads(): void
    {
        $user = User::factory()->create();
        $item = $this->item(['status' => Item::STATUS_BORROWED]);
        $record = BorrowRecord::create([
            'item_id' => $item->id,
            'borrower_id' => $user->id,
            'item_name' => $item->item_name,
            'serial_number' => $item->serial_number,
            'borrower_name' => $user->name,
            'borrow_date' => '2026-06-01',
            'purpose' => 'Presentasi',
            'status' => BorrowRecord::STATUS_BORROWED,
        ]);

        $this->actingAs($user)->get(route('borrows.show', $record))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Borrows/Show')
                ->where('borrow.id', $record->id)
                ->where('can_return', true)
            );
    }

    public function test_admin_can_delete_a_borrow_record(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        $item = $this->item();
        $record = BorrowRecord::create([
            'item_id' => $item->id,
            'borrower_id' => $user->id,
            'item_name' => $item->item_name,
            'serial_number' => $item->serial_number,
            'borrower_name' => $user->name,
            'borrow_date' => '2026-06-01',
            'purpose' => 'Presentasi',
            'status' => BorrowRecord::STATUS_RETURNED,
            'return_date' => '2026-06-02',
            'return_condition' => Item::CONDITION_GOOD,
        ]);

        $this->actingAs($admin)->delete(route('borrows.destroy', $record))
            ->assertRedirect(route('borrows.index'));

        $this->assertDatabaseMissing('borrow_records', ['id' => $record->id]);
    }

    public function test_deleting_an_active_borrow_frees_the_item(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        $item = $this->item(['status' => Item::STATUS_BORROWED]);
        $record = BorrowRecord::create([
            'item_id' => $item->id,
            'borrower_id' => $user->id,
            'item_name' => $item->item_name,
            'serial_number' => $item->serial_number,
            'borrower_name' => $user->name,
            'borrow_date' => '2026-06-01',
            'purpose' => 'Presentasi',
            'status' => BorrowRecord::STATUS_BORROWED,
        ]);

        $this->actingAs($admin)->delete(route('borrows.destroy', $record))
            ->assertRedirect(route('borrows.index'));

        $this->assertDatabaseMissing('borrow_records', ['id' => $record->id]);
        $this->assertEquals(Item::STATUS_AVAILABLE, $item->fresh()->status);
    }

    public function test_non_admin_cannot_delete_a_borrow_record(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $item = $this->item();
        $record = BorrowRecord::create([
            'item_id' => $item->id,
            'borrower_id' => $staff->id,
            'item_name' => $item->item_name,
            'serial_number' => $item->serial_number,
            'borrower_name' => $staff->name,
            'borrow_date' => '2026-06-01',
            'purpose' => 'Presentasi',
            'status' => BorrowRecord::STATUS_RETURNED,
            'return_date' => '2026-06-02',
            'return_condition' => Item::CONDITION_GOOD,
        ]);

        $this->actingAs($staff)->delete(route('borrows.destroy', $record))
            ->assertForbidden();

        $this->assertDatabaseHas('borrow_records', ['id' => $record->id]);
    }

    public function test_only_admin_can_create_items(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $payload = [
            'serial_number' => 'SN-NEW-9999',
            'item_name' => 'New Mouse',
            'brand_name' => 'Logitech',
            'type' => 'Mouse',
            'condition' => Item::CONDITION_NEW,
        ];

        $this->actingAs($staff)->post(route('items.store'), $payload)->assertForbidden();
        $this->assertDatabaseMissing('items', ['serial_number' => 'SN-NEW-9999']);

        $this->actingAs($admin)->post(route('items.store'), $payload)
            ->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', ['serial_number' => 'SN-NEW-9999']);
    }

    public function test_admin_can_update_the_item_image_replacing_the_old_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $old = UploadedFile::fake()->image('old.jpg')->store('items', 'public');
        $item = $this->item(['item_image' => $old]);

        $this->actingAs($admin)->patch(route('items.update', $item), [
            'serial_number' => $item->serial_number,
            'item_name' => $item->item_name,
            'brand_name' => $item->brand_name,
            'mac_address' => '',
            'type' => $item->type,
            'condition' => $item->condition,
            'item_image' => UploadedFile::fake()->image('new.jpg'),
        ])->assertRedirect(route('items.index'));

        $new = $item->fresh()->item_image;
        $this->assertNotNull($new);
        $this->assertNotEquals($old, $new);
        Storage::disk('public')->assertExists($new);
        Storage::disk('public')->assertMissing($old); // old file cleaned up
    }

    public function test_updating_an_item_without_a_new_image_keeps_the_existing_one(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $existing = UploadedFile::fake()->image('keep.jpg')->store('items', 'public');
        $item = $this->item(['item_image' => $existing]);

        $this->actingAs($admin)->patch(route('items.update', $item), [
            'serial_number' => $item->serial_number,
            'item_name' => 'Renamed',
            'brand_name' => $item->brand_name,
            'mac_address' => '',
            'type' => $item->type,
            'condition' => $item->condition,
        ])->assertRedirect(route('items.index'));

        $item = $item->fresh();
        $this->assertEquals('Renamed', $item->item_name);
        $this->assertEquals($existing, $item->item_image);
        Storage::disk('public')->assertExists($existing);
    }

    public function test_only_admin_can_read_the_item_list(): void
    {
        $this->item();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('items.index'))->assertOk();

        foreach ([User::ROLE_STAFF, User::ROLE_IT_SUPPORT] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('items.index'))->assertForbidden();
        }
    }
}
