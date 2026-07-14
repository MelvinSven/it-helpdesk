<?php

namespace Tests\Feature;

use App\Models\BorrowRecord;
use App\Models\Item;
use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase;

    private function item(string $serial = 'SN-DET-0001', string $name = 'Laptop'): Item
    {
        return Item::create([
            'serial_number' => $serial,
            'item_name' => $name,
            'brand_name' => 'Acme',
            'mac_address' => null,
            'type' => $name,
            'condition' => Item::CONDITION_GOOD,
            'status' => Item::STATUS_AVAILABLE,
        ]);
    }

    private function borrow(Item $item): BorrowRecord
    {
        return BorrowRecord::create([
            'item_id' => $item->id,
            'borrower_id' => null,
            'item_name' => $item->item_name,
            'serial_number' => $item->serial_number,
            'borrower_name' => 'Andi',
            'borrow_date' => '2026-06-01',
            'purpose' => 'Presentasi',
            'status' => BorrowRecord::STATUS_RETURNED,
            'return_date' => '2026-06-02',
            'return_condition' => Item::CONDITION_GOOD,
        ]);
    }

    private function procurement(): ProcurementRequest
    {
        static $n = 0;
        $n++;

        return ProcurementRequest::create([
            'request_number' => 'PB-2026-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'employee_name' => 'Budi',
            'requested_item' => 'Laptop Dell',
            'request_date' => '2026-06-03',
            'form_file' => 'procurements/form.pdf',
        ]);
    }

    public function test_admin_sees_logs_linked_requests_and_the_available_pool(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();
        $this->borrow($item);

        $linked = $this->procurement();
        $item->procurementRequests()->attach($linked->id);
        $this->procurement(); // unlinked → belongs to the available pool

        $this->actingAs($admin)->get(route('items.show', $item))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Items/Show')
                ->where('item.id', $item->id)
                ->has('borrows', 1)
                ->has('procurements', 1)
                ->where('procurements.0.id', $linked->id)
                ->has('availableProcurements', 1)
                ->where('can.manage', true)
            );
    }

    public function test_non_admin_cannot_view_item_detail(): void
    {
        $item = $this->item();

        foreach ([User::ROLE_STAFF, User::ROLE_IT_SUPPORT] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('items.show', $item))
                ->assertForbidden();
        }
    }

    public function test_non_admin_cannot_view_item_index(): void
    {
        $this->item();

        foreach ([User::ROLE_STAFF, User::ROLE_IT_SUPPORT] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('items.index'))
                ->assertForbidden();
        }
    }

    public function test_admin_can_attach_a_request_by_number(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();
        $request = $this->procurement();

        $this->actingAs($admin)->post(route('items.procurements.attach', $item), [
            'procurement_request_id' => $request->id,
        ])->assertRedirect();

        $this->assertTrue($item->procurementRequests()->whereKey($request->id)->exists());
    }

    public function test_one_request_can_be_attached_to_multiple_items(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $itemA = $this->item('SN-A', 'Laptop');
        $itemB = $this->item('SN-B', 'Monitor');
        $request = $this->procurement();

        foreach ([$itemA, $itemB] as $item) {
            $this->actingAs($admin)->post(route('items.procurements.attach', $item), [
                'procurement_request_id' => $request->id,
            ])->assertRedirect();
        }

        $this->assertEqualsCanonicalizing(
            [$itemA->id, $itemB->id],
            $request->fresh()->items()->pluck('items.id')->all(),
        );
    }

    public function test_cannot_attach_the_same_request_twice_to_one_item(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();
        $request = $this->procurement();
        $item->procurementRequests()->attach($request->id);

        $this->actingAs($admin)->post(route('items.procurements.attach', $item), [
            'procurement_request_id' => $request->id,
        ])->assertSessionHasErrors('procurement_request_id');

        $this->assertEquals(1, $item->procurementRequests()->count());
    }

    public function test_non_admin_cannot_attach(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $item = $this->item();
        $request = $this->procurement();

        $this->actingAs($staff)->post(route('items.procurements.attach', $item), [
            'procurement_request_id' => $request->id,
        ])->assertForbidden();

        $this->assertEquals(0, $item->procurementRequests()->count());
    }

    public function test_admin_can_detach_a_linked_request(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();
        $request = $this->procurement();
        $item->procurementRequests()->attach($request->id);

        $this->actingAs($admin)->delete(route('items.procurements.detach', [$item, $request]))
            ->assertRedirect();

        $this->assertFalse($item->procurementRequests()->whereKey($request->id)->exists());
    }

    public function test_detaching_from_one_item_leaves_the_other_link_intact(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $itemA = $this->item('SN-A', 'Laptop');
        $itemB = $this->item('SN-B', 'Monitor');
        $request = $this->procurement();
        $itemA->procurementRequests()->attach($request->id);
        $itemB->procurementRequests()->attach($request->id);

        $this->actingAs($admin)->delete(route('items.procurements.detach', [$itemA, $request]))
            ->assertRedirect();

        $this->assertFalse($itemA->procurementRequests()->whereKey($request->id)->exists());
        $this->assertTrue($itemB->procurementRequests()->whereKey($request->id)->exists());
    }

    public function test_first_uploaded_image_becomes_the_main_image_and_the_rest_the_gallery(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();

        $this->actingAs($admin)->post(route('items.images.store', $item), [
            'images' => [
                UploadedFile::fake()->image('main.jpg'),
                UploadedFile::fake()->image('extra-1.jpg'),
                UploadedFile::fake()->image('extra-2.jpg'),
            ],
        ])->assertRedirect();

        $item->refresh();
        $this->assertNotNull($item->item_image);
        Storage::disk('public')->assertExists($item->item_image);

        $this->assertCount(2, $item->images);
        foreach ($item->images as $image) {
            Storage::disk('public')->assertExists($image->image_path);
        }
    }

    public function test_uploads_go_to_the_gallery_when_a_main_image_exists(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();

        $main = UploadedFile::fake()->image('main.jpg')->store('items', 'public');
        $item->update(['item_image' => $main]);

        $this->actingAs($admin)->post(route('items.images.store', $item), [
            'images' => [UploadedFile::fake()->image('extra.jpg')],
        ])->assertRedirect();

        $item->refresh();
        $this->assertEquals($main, $item->item_image);
        $this->assertCount(1, $item->images);
    }

    public function test_image_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();

        $this->actingAs($admin)->post(route('items.images.store', $item), [
            'images' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors('images.0');

        $this->assertNull($item->fresh()->item_image);
        $this->assertCount(0, $item->images);
    }

    public function test_non_admin_cannot_upload_item_images(): void
    {
        Storage::fake('public');
        $item = $this->item();

        foreach ([User::ROLE_STAFF, User::ROLE_IT_SUPPORT] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->post(route('items.images.store', $item), [
                'images' => [UploadedFile::fake()->image('laptop.jpg')],
            ])->assertForbidden();
        }

        $this->assertNull($item->fresh()->item_image);
        $this->assertCount(0, $item->images);
    }

    public function test_admin_can_delete_a_gallery_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();

        $path = UploadedFile::fake()->image('extra.jpg')->store('items', 'public');
        $image = $item->images()->create(['image_path' => $path]);

        $this->actingAs($admin)->delete(route('items.images.destroy', [$item, $image]))
            ->assertRedirect();

        $this->assertDatabaseMissing('item_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_cannot_delete_a_gallery_image_of_another_item(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $itemA = $this->item('SN-A', 'Laptop');
        $itemB = $this->item('SN-B', 'Monitor');

        $image = $itemB->images()->create([
            'image_path' => UploadedFile::fake()->image('extra.jpg')->store('items', 'public'),
        ]);

        $this->actingAs($admin)->delete(route('items.images.destroy', [$itemA, $image]))
            ->assertNotFound();

        $this->assertDatabaseHas('item_images', ['id' => $image->id]);
    }

    public function test_non_admin_cannot_delete_a_gallery_image(): void
    {
        Storage::fake('public');
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $item = $this->item();

        $image = $item->images()->create([
            'image_path' => UploadedFile::fake()->image('extra.jpg')->store('items', 'public'),
        ]);

        $this->actingAs($staff)->delete(route('items.images.destroy', [$item, $image]))
            ->assertForbidden();

        $this->assertDatabaseHas('item_images', ['id' => $image->id]);
    }

    public function test_item_can_be_created_and_updated_with_a_description(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('items.store'), [
            'serial_number' => 'SN-DESC-001',
            'item_name' => 'Laptop',
            'brand_name' => 'Acme',
            'mac_address' => null,
            'type' => 'Laptop',
            'condition' => Item::CONDITION_GOOD,
            'description' => "Core i7, RAM 16GB\nWindows 11, Office 2024",
        ])->assertRedirect(route('items.index'));

        $item = Item::where('serial_number', 'SN-DESC-001')->firstOrFail();
        $this->assertEquals("Core i7, RAM 16GB\nWindows 11, Office 2024", $item->description);

        $this->actingAs($admin)->patch(route('items.update', $item), [
            'serial_number' => $item->serial_number,
            'item_name' => $item->item_name,
            'brand_name' => $item->brand_name,
            'mac_address' => null,
            'type' => $item->type,
            'condition' => $item->condition,
            'description' => 'RAM upgrade ke 32GB',
        ])->assertRedirect(route('items.index'));

        $this->assertEquals('RAM upgrade ke 32GB', $item->fresh()->description);
    }

    public function test_description_is_optional(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('items.store'), [
            'serial_number' => 'SN-DESC-002',
            'item_name' => 'Mouse',
            'brand_name' => 'Acme',
            'mac_address' => null,
            'type' => 'Mouse',
            'condition' => Item::CONDITION_GOOD,
        ])->assertRedirect(route('items.index'));

        $this->assertNull(Item::where('serial_number', 'SN-DESC-002')->firstOrFail()->description);
    }

    public function test_cannot_detach_a_request_not_linked_to_this_item(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();
        $request = $this->procurement(); // never linked

        $this->actingAs($admin)->delete(route('items.procurements.detach', [$item, $request]))
            ->assertNotFound();
    }
}
