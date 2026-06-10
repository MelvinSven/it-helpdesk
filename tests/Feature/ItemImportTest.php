<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ItemImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build an .xlsx upload from a header row + data rows.
     *
     * @param  string[]  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    private function excel(array $headers, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers, ...$rows], null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'import').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'items.xlsx', null, null, true);
    }

    private function defaultHeaders(): array
    {
        return ['Nomor Seri', 'Nama Barang', 'Merek', 'MAC Address', 'Tipe', 'Kondisi'];
    }

    public function test_admin_can_import_items_with_normalised_condition(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $file = $this->excel($this->defaultHeaders(), [
            ['SN-001', 'Laptop Dell', 'Dell', '00:11:22:33:44:55', 'Laptop', 'Baru'],
            ['SN-002', 'Monitor LG', 'LG', '', 'Monitor', 'Rusak Ringan'],
        ]);

        $this->actingAs($admin)
            ->post(route('items.import'), ['file' => $file])
            ->assertRedirect(route('items.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('items', [
            'serial_number' => 'SN-001',
            'condition' => Item::CONDITION_NEW,
            'status' => Item::STATUS_AVAILABLE,
            'mac_address' => '00:11:22:33:44:55',
        ]);

        // "Rusak Ringan" → rusak_ringan; empty MAC stored as null.
        $this->assertDatabaseHas('items', [
            'serial_number' => 'SN-002',
            'condition' => Item::CONDITION_MINOR_DAMAGE,
            'mac_address' => null,
        ]);
    }

    public function test_rows_with_unknown_condition_are_skipped(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $file = $this->excel($this->defaultHeaders(), [
            ['SN-001', 'Laptop Dell', 'Dell', '', 'Laptop', 'Baik'],
            ['SN-002', 'Monitor LG', 'LG', '', 'Monitor', 'Hancur'],
        ]);

        $this->actingAs($admin)
            ->post(route('items.import'), ['file' => $file])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('items', ['serial_number' => 'SN-001']);
        $this->assertDatabaseMissing('items', ['serial_number' => 'SN-002']);
        $this->assertSame(1, Item::count());
    }

    public function test_duplicate_serial_numbers_are_skipped(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Item::create([
            'serial_number' => 'SN-EXISTING',
            'item_name' => 'Old',
            'brand_name' => 'Acme',
            'type' => 'Laptop',
            'condition' => Item::CONDITION_GOOD,
            'status' => Item::STATUS_AVAILABLE,
        ]);

        $file = $this->excel($this->defaultHeaders(), [
            ['SN-EXISTING', 'Laptop Dell', 'Dell', '', 'Laptop', 'Baru'],
            ['SN-DUP', 'Monitor LG', 'LG', '', 'Monitor', 'Baik'],
            ['SN-DUP', 'Monitor LG 2', 'LG', '', 'Monitor', 'Baik'],
        ]);

        $this->actingAs($admin)
            ->post(route('items.import'), ['file' => $file])
            ->assertSessionHas('error');

        // Existing serial untouched, in-file duplicate imported once.
        $this->assertSame(1, Item::where('serial_number', 'SN-EXISTING')->count());
        $this->assertSame(1, Item::where('serial_number', 'SN-DUP')->count());
        $this->assertSame('Old', Item::where('serial_number', 'SN-EXISTING')->value('item_name'));
    }

    public function test_missing_required_header_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $file = $this->excel(['Nomor Seri', 'Nama Barang', 'Merek', 'Tipe'], [
            ['SN-001', 'Laptop', 'Dell', 'Laptop'],
        ]);

        $this->actingAs($admin)
            ->post(route('items.import'), ['file' => $file])
            ->assertSessionHas('error');

        $this->assertSame(0, Item::count());
    }

    public function test_non_admin_cannot_import(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $file = $this->excel($this->defaultHeaders(), [
            ['SN-001', 'Laptop Dell', 'Dell', '', 'Laptop', 'Baru'],
        ]);

        $this->actingAs($staff)
            ->post(route('items.import'), ['file' => $file])
            ->assertForbidden();

        $this->assertSame(0, Item::count());
    }
}
