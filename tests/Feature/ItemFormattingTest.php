<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ItemFormattingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'kode_barang' => 'LIX-EL-LAPTOP-001',
            'item_name' => 'Laptop ThinkPad',
            'brand_name' => 'Lenovo',
            'type' => 'Laptop',
            'condition' => Item::CONDITION_GOOD,
        ], $overrides);
    }

    public function test_store_uppercases_name_fields_and_formats_the_code(): void
    {
        $this->actingAs($this->admin())
            ->post(route('items.store'), $this->payload(['kode_barang' => 'lix-el-laptop 12']))
            ->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('items', [
            'kode_barang' => 'LIX-EL-LAPTOP-12',
            'item_name' => 'LAPTOP THINKPAD',
            'brand_name' => 'LENOVO',
            'type' => 'LAPTOP',
        ]);
    }

    public function test_store_adds_the_prefix_to_bare_segments(): void
    {
        $this->actingAs($this->admin())
            ->post(route('items.store'), $this->payload(['kode_barang' => 'laptop 12']))
            ->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('items', ['kode_barang' => 'LIX-EL-LAPTOP-12']);
    }

    /** @return array<string, array{string}> */
    public static function incompleteCodes(): array
    {
        return [
            'bare prefix' => ['LIX-EL'],
            'prefix only' => ['LIX-EL-'],
            'one segment' => ['LIX-EL-LAPTOP'],
            'trailing dash' => ['LIX-EL-LAPTOP-'],
        ];
    }

    #[DataProvider('incompleteCodes')]
    public function test_store_rejects_incomplete_codes(string $code): void
    {
        $this->actingAs($this->admin())
            ->post(route('items.store'), $this->payload(['kode_barang' => $code]))
            ->assertSessionHasErrors('kode_barang');

        $this->assertSame(0, Item::count());
    }

    public function test_unique_check_compares_the_formatted_code(): void
    {
        Item::create([...$this->payload(), 'status' => Item::STATUS_AVAILABLE]);

        $this->actingAs($this->admin())
            ->post(route('items.store'), $this->payload(['kode_barang' => 'lix-el-laptop-001']))
            ->assertSessionHasErrors('kode_barang');

        $this->assertSame(1, Item::count());
    }

    public function test_update_uppercases_name_fields(): void
    {
        $item = Item::create([...$this->payload(), 'status' => Item::STATUS_AVAILABLE]);

        $this->actingAs($this->admin())
            ->patch(route('items.update', $item), $this->payload([
                'kode_barang' => 'lix-el-laptop-001',
                'item_name' => 'Laptop Baru',
            ]))
            ->assertRedirect(route('items.index'));

        $item->refresh();
        $this->assertSame('LIX-EL-LAPTOP-001', $item->kode_barang);
        $this->assertSame('LAPTOP BARU', $item->item_name);
    }

    public function test_update_rejects_a_legacy_code_instead_of_renaming_it(): void
    {
        $item = Item::create([
            ...$this->payload(['kode_barang' => 'TES-002']),
            'status' => Item::STATUS_AVAILABLE,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('items.update', $item), $this->payload(['kode_barang' => 'TES-002']))
            ->assertSessionHasErrors('kode_barang');

        $this->assertSame('TES-002', $item->fresh()->kode_barang);
    }
}
