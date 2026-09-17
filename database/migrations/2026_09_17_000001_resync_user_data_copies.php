<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bring existing copies of user data back in line with the users table.
     * From here on User::booted() keeps borrow records in sync and the ticket
     * page resolves assignee names by id; this fixes rows that already drifted.
     */
    public function up(): void
    {
        // Borrow records: refresh the borrower's name and proyek. COALESCE keeps
        // the old name if the linked user somehow no longer exists (the column
        // is NOT NULL).
        DB::table('borrow_records')->whereNotNull('borrower_id')->update([
            'borrower_name' => DB::raw('COALESCE((select name from users where users.id = borrow_records.borrower_id), borrower_name)'),
            'borrower_proyek' => DB::raw('(select proyek from users where users.id = borrow_records.borrower_id)'),
        ]);

        // Ticket activities: older assign/reassign rows only stored the
        // assignee's name. Add the assignee's id so the log can show the
        // current name.
        $activities = DB::table('ticket_activities')
            ->whereIn('action', ['assigned', 'reassigned'])
            ->orderBy('ticket_id')->orderBy('created_at')->orderBy('id')
            ->get(['id', 'ticket_id', 'meta']);

        if ($activities->isEmpty()) {
            return;
        }

        // Only assignment writes tickets.assignee_id, so the latest assign or
        // reassign row on a ticket always points at its current assignee.
        $currentAssignee = DB::table('tickets')
            ->whereIn('id', $activities->pluck('ticket_id')->unique())
            ->pluck('assignee_id', 'id');
        $latestActivityIds = $activities->groupBy('ticket_id')
            ->map(fn ($rows) => $rows->last()->id)
            ->flip();

        // Earlier rows fall back to matching the stored name, but only when
        // exactly one user has it; ambiguous or renamed ones keep the old name.
        $userIdByUniqueName = DB::table('users')->get(['id', 'name'])
            ->groupBy('name')
            ->filter(fn ($users) => $users->count() === 1)
            ->map(fn ($users) => $users->first()->id);

        foreach ($activities as $activity) {
            $meta = json_decode((string) $activity->meta, true) ?: [];

            if (isset($meta['assignee_id'])) {
                continue;
            }

            $assigneeId = $latestActivityIds->has($activity->id)
                ? $currentAssignee[$activity->ticket_id] ?? null
                : null;
            $assigneeId ??= $userIdByUniqueName[$meta['assignee_name'] ?? ''] ?? null;

            if ($assigneeId !== null) {
                $meta['assignee_id'] = (int) $assigneeId;

                DB::table('ticket_activities')
                    ->where('id', $activity->id)
                    ->update(['meta' => json_encode($meta)]);
            }
        }
    }

    public function down(): void
    {
        // Nothing to undo: the previous copies were stale values, and the extra
        // assignee_id key in activity meta is ignored by older code.
    }
};
