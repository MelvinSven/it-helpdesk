<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ItemImportController extends Controller
{
    /**
     * Header aliases per field. The first header found in the sheet wins, so
     * both the Indonesian labels shown in the import modal and a few common
     * English/snake_case variants are accepted.
     *
     * @var array<string, string[]>
     */
    private const COLUMNS = [
        'serial_number' => ['nomor seri', 'nomor', 'serial number', 'serial_number'],
        'item_name' => ['nama barang', 'nama'],
        'brand_name' => ['merek', 'brand'],
        'mac_address' => ['mac address', 'mac_address', 'mac'],
        'type' => ['tipe', 'type'],
        'condition' => ['kondisi', 'condition'],
    ];

    private const REQUIRED = ['serial_number', 'item_name', 'brand_name', 'type', 'condition'];

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', Item::class);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(
            ['Nomor Seri', 'Nama Barang', 'Merek', 'MAC Address', 'Tipe', 'Kondisi'],
            null,
            'A1',
        );
        $sheet->fromArray(
            ['SN-001', 'Laptop Dell', 'Dell', '00:1A:2B:3C:4D:5E', 'Laptop', 'Baru'],
            null,
            'A2',
        );

        $tmp = tempnam(sys_get_temp_dir(), 'item_template_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tmp);

        return response()->download($tmp, 'template_impor_barang.xlsx')->deleteFileAfterSend(true);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Item::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (empty($rows)) {
            return back()->with('error', 'File Excel kosong.');
        }

        // Normalise header row, then resolve each field to its column index.
        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $rows[0]);

        $colMap = [];
        foreach (self::COLUMNS as $field => $aliases) {
            foreach ($aliases as $alias) {
                $idx = array_search($alias, $headers, true);
                if ($idx !== false) {
                    $colMap[$field] = $idx;
                    break;
                }
            }
        }

        foreach (self::REQUIRED as $field) {
            if (! isset($colMap[$field])) {
                $label = self::COLUMNS[$field][0];

                return back()->with('error', "Kolom wajib tidak ditemukan: \"{$label}\". Pastikan header Excel sesuai.");
            }
        }

        $imported = 0;
        $skipped = [];
        $seenSerials = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            $line = $offset + 2; // 1-based, +1 for the header row

            $serial = trim((string) ($row[$colMap['serial_number']] ?? ''));
            $name = trim((string) ($row[$colMap['item_name']] ?? ''));
            $brand = trim((string) ($row[$colMap['brand_name']] ?? ''));
            $type = trim((string) ($row[$colMap['type']] ?? ''));
            $mac = isset($colMap['mac_address']) ? trim((string) ($row[$colMap['mac_address']] ?? '')) : '';

            // "Rusak Ringan" / "rusak ringan" / "rusak_ringan" all normalise to
            // the stored value rusak_ringan.
            $rawCondition = mb_strtolower(trim((string) ($row[$colMap['condition']] ?? '')));
            $condition = str_replace(' ', '_', $rawCondition);

            // Skip fully blank rows silently (trailing rows from Excel).
            if ($serial === '' && $name === '' && $brand === '' && $type === '' && $rawCondition === '') {
                continue;
            }

            if ($serial === '' || $name === '' || $brand === '' || $type === '') {
                $skipped[] = "Baris {$line}: Nomor Seri, Nama Barang, Merek, atau Tipe kosong.";

                continue;
            }

            if (! in_array($condition, Item::CONDITIONS, true)) {
                $skipped[] = "Baris {$line} ({$serial}): Kondisi tidak dikenal \"{$rawCondition}\".";

                continue;
            }

            if (isset($seenSerials[$serial])) {
                $skipped[] = "Baris {$line} ({$serial}): Nomor Seri duplikat di dalam file.";

                continue;
            }

            if (Item::where('serial_number', $serial)->exists()) {
                $skipped[] = "Baris {$line} ({$serial}): Nomor Seri sudah terdaftar.";

                continue;
            }

            Item::create([
                'serial_number' => $serial,
                'item_name' => $name,
                'brand_name' => $brand,
                'mac_address' => $mac !== '' ? $mac : null,
                'type' => $type,
                'condition' => $condition,
                'status' => Item::STATUS_AVAILABLE,
            ]);

            $seenSerials[$serial] = true;
            $imported++;
        }

        $message = "{$imported} barang berhasil diimpor.";
        if (! empty($skipped)) {
            $message .= ' '.count($skipped).' baris dilewati: '.implode(' | ', $skipped);
        }

        $flashKey = empty($skipped) ? 'success' : 'error';

        return redirect()->route('items.index')->with($flashKey, $message);
    }
}
