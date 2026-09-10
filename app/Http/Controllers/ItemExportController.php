<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ItemExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('export', Item::class);

        $query = Item::query();

        $this->applyFilters($query, $request);

        $conditionLabels = [
            Item::CONDITION_NEW => 'Baru',
            Item::CONDITION_GOOD => 'Baik',
            Item::CONDITION_MINOR_DAMAGE => 'Rusak Ringan',
            Item::CONDITION_MAJOR_DAMAGE => 'Rusak Berat',
        ];
        $statusLabels = [
            Item::STATUS_AVAILABLE => 'Tersedia',
            Item::STATUS_BORROWED => 'Dipinjam',
        ];

        $filename = 'barang-'.now()->format('Ymd-His').'.pdf';

        // Images are left out: dompdf can't resolve /storage URLs, and the
        // list is meant as a printable inventory, not a gallery.
        return Pdf::loadView('exports.items', [
            'items' => $query->orderBy('kode_barang')->get(),
            'conditionLabels' => $conditionLabels,
            'statusLabels' => $statusLabels,
            'printedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    // Mirrors ItemController::index so the PDF matches the filtered list.
    protected function applyFilters($query, Request $request): void
    {
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($condition = $request->string('condition')->toString()) {
            $query->where('condition', $condition);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('brand_name', 'like', "%{$search}%")
                    ->orWhere('mac_address', 'like', "%{$search}%");
            });
        }
    }
}
