<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemExportTest extends TestCase
{
    use RefreshDatabase;

    private function item(array $overrides = []): Item
    {
        return Item::create(array_merge([
            'kode_barang' => 'BRG-TEST-0001',
            'serial_number' => 'SN-TEST-0001',
            'item_name' => 'Test Laptop',
            'brand_name' => 'Acme',
            'mac_address' => null,
            'type' => 'Laptop',
            'condition' => Item::CONDITION_GOOD,
            'status' => Item::STATUS_AVAILABLE,
        ], $overrides));
    }

    public function test_staff_cannot_export_items(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->item();

        $this->actingAs($staff)->get(route('items.export'))
            ->assertForbidden();
    }

    public function test_it_support_cannot_export_items(): void
    {
        $itSupport = User::factory()->create(['role' => User::ROLE_IT_SUPPORT]);
        $this->item();

        $this->actingAs($itSupport)->get(route('items.export'))
            ->assertForbidden();
    }

    public function test_admin_can_export_items_as_pdf(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->item();

        $response = $this->actingAs($admin)->get(route('items.export'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            $response->headers->get('content-type'),
        );
        $this->assertStringContainsString(
            '.pdf',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_admin_can_export_filtered_items(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->item();
        $this->item([
            'kode_barang' => 'BRG-TEST-0002',
            'serial_number' => 'SN-TEST-0002',
            'status' => Item::STATUS_BORROWED,
        ]);

        $this->actingAs($admin)
            ->get(route('items.export', ['status' => Item::STATUS_BORROWED, 'search' => 'Laptop']))
            ->assertOk();
    }
}
