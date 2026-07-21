<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserImportController extends Controller
{
    private const ROLE_MAP = [
        'admin' => User::ROLE_ADMIN,
        'administrator' => User::ROLE_ADMIN,
        'staf' => User::ROLE_STAFF,
        'staff' => User::ROLE_STAFF,
        'it support' => User::ROLE_IT_SUPPORT,
        'dukungan ti' => User::ROLE_IT_SUPPORT,
        'it_support' => User::ROLE_IT_SUPPORT,
    ];

    public function template(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(
            ['ID Pengguna', 'Nama', 'Email', 'Peran', 'Departemen', 'Proyek'],
            null,
            'A1',
        );
        $sheet->fromArray(
            ['EMP-001', 'Budi Santoso', 'budi@example.com', 'Staf', 'Keuangan', 'Proyek A'],
            null,
            'A2',
        );

        $tmp = tempnam(sys_get_temp_dir(), 'user_template_').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tmp);

        return response()->download($tmp, 'template_impor_pengguna.xlsx')->deleteFileAfterSend(true);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (empty($rows)) {
            return back()->with('error', 'File Excel kosong.');
        }

        // Normalise header row
        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $rows[0]);

        $colMap = [];
        foreach (['id pengguna', 'nama', 'email', 'peran', 'departemen', 'proyek'] as $col) {
            $idx = array_search($col, $headers, true);
            if ($idx !== false) {
                $colMap[$col] = $idx;
            }
        }

        $required = ['id pengguna', 'nama', 'peran'];
        foreach ($required as $col) {
            if (! isset($colMap[$col])) {
                return back()->with('error', "Kolom wajib tidak ditemukan: \"{$col}\". Pastikan header Excel sesuai.");
            }
        }

        $imported = 0;
        $skipped = [];
        $defaultPassword = Hash::make('lixicon123');

        foreach (array_slice($rows, 1) as $lineNumber => $row) {
            $userId = trim((string) ($row[$colMap['id pengguna']] ?? ''));
            $name = trim((string) ($row[$colMap['nama']] ?? ''));
            $rawRole = mb_strtolower(trim((string) ($row[$colMap['peran']] ?? '')));
            $email = isset($colMap['email']) ? trim((string) ($row[$colMap['email']] ?? '')) : '';
            $department = isset($colMap['departemen']) ? trim((string) ($row[$colMap['departemen']] ?? '')) : '';
            $proyek = isset($colMap['proyek']) ? trim((string) ($row[$colMap['proyek']] ?? '')) : '';

            $line = $lineNumber + 2; // 1-based, +1 for header

            if ($userId === '' && $name === '' && $rawRole === '' && $email === '' && $department === '' && $proyek === '') {
                continue; // fully empty row (trailing Excel rows) — ignore silently
            }

            if ($userId === '' || $name === '') {
                $skipped[] = "Baris {$line}: ID Pengguna atau Nama kosong.";

                continue;
            }

            $role = self::ROLE_MAP[$rawRole] ?? null;
            if ($role === null) {
                $skipped[] = "Baris {$line} ({$userId}): Peran tidak dikenal \"{$rawRole}\".";

                continue;
            }

            if (User::where('user_id', $userId)->exists()) {
                $skipped[] = "Baris {$line} ({$userId}): ID Pengguna sudah terdaftar.";

                continue;
            }

            if ($email !== '' && User::where('email', $email)->exists()) {
                $skipped[] = "Baris {$line} ({$userId}): Email sudah terdaftar.";

                continue;
            }

            User::create([
                'user_id' => $userId,
                'name' => $name,
                'email' => $email !== '' ? $email : null,
                'role' => $role,
                'department' => $department !== '' ? $department : null,
                'proyek' => $proyek !== '' ? $proyek : null,
                'password' => $defaultPassword,
                'is_active' => true,
            ]);

            $imported++;
        }

        $message = "{$imported} pengguna berhasil diimpor.";
        if (! empty($skipped)) {
            $message .= ' '.count($skipped).' baris dilewati: '.implode(' | ', $skipped);
        }

        $flashKey = empty($skipped) ? 'success' : 'error';

        return redirect()->route('admin.users.index')->with($flashKey, $message);
    }
}
