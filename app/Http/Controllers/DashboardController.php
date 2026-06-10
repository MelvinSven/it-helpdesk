<?php

namespace App\Http\Controllers;

use App\Models\BorrowRecord;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return match (true) {
            $user->isAdmin() => $this->admin(),
            $user->isItSupport() => $this->itSupport($user),
            default => $this->staff($user),
        };
    }

    private function admin(): Response
    {
        $statusCounts = Ticket::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $categoryCounts = Ticket::query()
            ->join('categories', 'categories.id', '=', 'tickets.category_id')
            ->select('categories.name', DB::raw('count(*) as count'))
            ->groupBy('categories.name')
            ->pluck('count', 'name');

        $workload = User::query()
            ->where('role', User::ROLE_IT_SUPPORT)
            ->withCount(['assignedTickets as open_count' => function ($q) {
                $q->whereIn('status', [Ticket::STATUS_NEW, Ticket::STATUS_IN_PROGRESS]);
            }])
            ->orderByDesc('open_count')
            ->get(['id', 'name', 'user_id'])
            ->map(fn ($u) => ['name' => $u->name, 'count' => $u->open_count]);

        $monthlyTrend = $this->monthlyTrend(
            Ticket::query(),
            'created_at',
            6,
            Ticket::query()->where('status', Ticket::STATUS_RESOLVED),
            'resolved_at',
        );

        return Inertia::render('Dashboard/Admin', [
            'stats' => [
                'total' => Ticket::count(),
                'open' => (int) ($statusCounts[Ticket::STATUS_NEW] ?? 0)
                    + (int) ($statusCounts[Ticket::STATUS_IN_PROGRESS] ?? 0),
                'in_progress' => (int) ($statusCounts[Ticket::STATUS_IN_PROGRESS] ?? 0),
                'resolved_this_month' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereMonth('resolved_at', now()->month)
                    ->whereYear('resolved_at', now()->year)
                    ->count(),
            ],
            'status_counts' => $statusCounts,
            'category_counts' => $categoryCounts,
            'workload' => $workload,
            'monthly_trend' => $monthlyTrend,
            'recent_tickets' => Ticket::with(['requestor:id,name', 'assignee:id,name', 'category:id,name'])
                ->latest()
                ->take(10)
                ->get(),
            'borrowed_items' => BorrowRecord::where('status', BorrowRecord::STATUS_BORROWED)
                ->latest()
                ->get(['id', 'item_name', 'serial_number', 'borrower_name', 'borrow_date', 'purpose']),
        ]);
    }

    private function itSupport(User $user): Response
    {
        $assigned = Ticket::where('assignee_id', $user->id);

        $resolvedThisMonth = (clone $assigned)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereMonth('resolved_at', now()->month)
            ->whereYear('resolved_at', now()->year);

        $monthlyTrend = $this->monthlyTrend(
            Ticket::where('assignee_id', $user->id),
            'created_at',
            6,
            Ticket::where('assignee_id', $user->id)
                ->where('status', Ticket::STATUS_RESOLVED),
            'resolved_at',
        );

        return Inertia::render('Dashboard/ItSupport', [
            'stats' => [
                'new' => (clone $assigned)->where('status', Ticket::STATUS_NEW)->count(),
                'in_progress' => (clone $assigned)->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
                'resolved_this_month' => $resolvedThisMonth->count(),
            ],
            'recent_tickets' => (clone $assigned)
                ->with(['requestor:id,name', 'category:id,name'])
                ->latest()
                ->take(10)
                ->get(),
            'unassigned_count' => Ticket::whereNull('assignee_id')->count(),
            'monthly_trend' => $monthlyTrend,
            'borrowed_items' => BorrowRecord::where('status', BorrowRecord::STATUS_BORROWED)
                ->where('borrower_id', $user->id)
                ->latest()
                ->get(['id', 'item_name', 'serial_number', 'borrower_name', 'borrow_date', 'purpose']),
        ]);
    }

    /**
     * Build monthly aggregation for the last N months (inclusive of current).
     *
     * @return array<int, array{label: string, ym: string, created: int, resolved: int}>
     */
    private function monthlyTrend(
        Builder $createdQuery,
        string $createdColumn,
        int $months,
        Builder $resolvedQuery,
        string $resolvedColumn,
    ): array {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);
        $end = CarbonImmutable::now()->endOfMonth();

        $buckets = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $ym = $cursor->format('Y-m');
            $buckets[$ym] = [
                'label' => $this->monthLabel($cursor),
                'ym' => $ym,
                'created' => 0,
                'resolved' => 0,
            ];
            $cursor = $cursor->addMonth();
        }

        $createdRows = (clone $createdQuery)
            ->whereBetween($createdColumn, [$start, $end])
            ->get([$createdColumn]);
        foreach ($createdRows as $row) {
            $ym = $row->{$createdColumn}->format('Y-m');
            if (isset($buckets[$ym])) {
                $buckets[$ym]['created']++;
            }
        }

        $resolvedRows = (clone $resolvedQuery)
            ->whereNotNull($resolvedColumn)
            ->whereBetween($resolvedColumn, [$start, $end])
            ->get([$resolvedColumn]);
        foreach ($resolvedRows as $row) {
            $ym = $row->{$resolvedColumn}->format('Y-m');
            if (isset($buckets[$ym])) {
                $buckets[$ym]['resolved']++;
            }
        }

        return array_values($buckets);
    }

    private function monthLabel(CarbonImmutable $date): string
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        return $months[(int) $date->format('n')].' '.$date->format('y');
    }

    private function staff(User $user): Response
    {
        $mine = Ticket::where('requestor_id', $user->id);

        return Inertia::render('Dashboard/Staff', [
            'stats' => [
                'open' => (clone $mine)->whereIn('status', [Ticket::STATUS_NEW, Ticket::STATUS_IN_PROGRESS])->count(),
                'resolved' => (clone $mine)->where('status', Ticket::STATUS_RESOLVED)->count(),
                'total' => (clone $mine)->count(),
            ],
            'recent_tickets' => (clone $mine)
                ->with(['assignee:id,name', 'category:id,name'])
                ->latest()
                ->take(10)
                ->get(),
            'borrowed_items' => BorrowRecord::where('status', BorrowRecord::STATUS_BORROWED)
                ->where('borrower_id', $user->id)
                ->latest()
                ->get(['id', 'item_name', 'serial_number', 'borrower_name', 'borrow_date', 'purpose']),
        ]);
    }
}
