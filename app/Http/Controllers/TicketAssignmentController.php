<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketAssignmentController extends Controller
{
    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $validated = $request->validate([
            'assignee_id' => ['required', 'exists:users,id'],
        ]);

        $assignee = User::findOrFail($validated['assignee_id']);
        abort_unless($assignee->isItSupport() && $assignee->is_active, 422, 'Penangan harus Dukungan TI yang aktif.');

        $previousAssigneeId = $ticket->assignee_id;
        $ticket->assignee_id = $assignee->id;

        if ($ticket->status === Ticket::STATUS_NEW) {
            $ticket->status = Ticket::STATUS_IN_PROGRESS;
        }

        $ticket->save();

        TicketActivity::record(
            $ticket,
            $request->user(),
            $previousAssigneeId
                ? TicketActivity::ACTION_REASSIGNED
                : TicketActivity::ACTION_ASSIGNED,
            ['assignee_name' => $assignee->name]
        );

        $assignee->notify(new TicketAssignedNotification($ticket, $request->user()));

        return back()->with('success', "Tiket ditugaskan ke {$assignee->name}.");
    }
}
