<?php

namespace Tests\Feature;

use App\Models\BorrowRecord;
use App\Models\Item;
use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_non_admin_sees_logs_but_no_procurements(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $item = $this->item();
        $this->borrow($item);
        $item->procurementRequests()->attach($this->procurement()->id);

        $this->actingAs($staff)->get(route('items.show', $item))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Items/Show')
                ->has('borrows', 1)
                ->has('procurements', 0)
                ->has('availableProcurements', 0)
                ->where('can.manage', false)
            );
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

    public function test_cannot_detach_a_request_not_linked_to_this_item(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->item();
        $request = $this->procurement(); // never linked

        $this->actingAs($admin)->delete(route('items.procurements.detach', [$item, $request]))
            ->assertNotFound();
    }
}
