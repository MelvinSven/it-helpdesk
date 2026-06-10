<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $user = $request->user();
        $query = Ticket::query()->with([
            'requestor:id,name,user_id',
            'assignee:id,name,user_id',
            'category:id,name',
        ]);

        $this->applyScope($query, $user);
        $this->applyFilters($query, $request);

        $priorityLabels = [
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'urgent' => 'Mendesak',
        ];
        $statusLabels = [
            'new' => 'Baru',
            'in_progress' => 'Dikerjakan',
            'resolved' => 'Selesai',
        ];

        $filename = 'tickets-'.now()->format('Ymd-His').'.xlsx';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tiket');

        $headers = [
            'Kode', 'Judul', 'Kategori', 'Prioritas', 'Status',
            'Pelapor', 'Penangan', 'Dibuat', 'Selesai',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = $sheet->getStyle('A1:I1');
        $headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4F46E5');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $row = 2;
        $query->orderBy('created_at')->chunk(500, function ($tickets) use (&$row, $sheet, $priorityLabels, $statusLabels) {
            foreach ($tickets as $t) {
                $sheet->fromArray([
                    $t->ticket_code,
                    $t->title,
                    $t->category?->name,
                    $priorityLabels[$t->priority] ?? $t->priority,
                    $statusLabels[$t->status] ?? $t->status,
                    $t->requestor?->name,
                    $t->assignee?->name,
                    $t->created_at?->format('Y-m-d H:i'),
                    $t->resolved_at?->format('Y-m-d H:i'),
                ], null, 'A'.$row);
                $row++;
            }
        });

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function applyScope($query, User $user): void
    {
        // All roles can export every ticket, matching the index list and
        // TicketPolicy::view. Conversation access is gated separately.
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($priority = $request->string('priority')->toString()) {
            $query->where('priority', $priority);
        }
        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('ticket_code', 'like', "%{$search}%");
            });
        }
    }
}
