<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Barang</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #6b7280; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        th { background: #4f46e5; color: #ffffff; text-align: left; padding: 6px; font-weight: bold; }
        td { padding: 5px 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        tr { page-break-inside: avoid; }
        tr:nth-child(even) td { background: #f9fafb; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; }
        .empty { text-align: center; color: #6b7280; padding: 24px; }
    </style>
</head>
<body>
    <h1>Daftar Barang</h1>
    <div class="meta">Dicetak {{ $printedAt }} &middot; {{ $items->count() }} barang</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Merek</th>
                <th>MAC Address</th>
                <th>Tipe</th>
                <th>Kondisi</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="mono">{{ $item->kode_barang }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->brand_name }}</td>
                    <td class="mono">{{ $item->mac_address ?? '—' }}</td>
                    <td>{{ $item->type }}</td>
                    <td>{{ $conditionLabels[$item->condition] ?? $item->condition }}</td>
                    <td>{{ $statusLabels[$item->status] ?? $item->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">Tidak ada barang yang cocok dengan filter saat ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
