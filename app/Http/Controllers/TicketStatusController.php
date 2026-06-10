<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketStatusController extends Controller
{
    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('updateStatus', $ticket);

        $validated = $request->validate([
            'status' => ['required', 'in:new,in_progress,resolved'],
        ]);

        $oldStatus = $ticket->status;
        $ticket->status = $validated['status'];

        if ($validated['status'] === Ticket::STATUS_RESOLVED && ! $ticket->resolved_at) {
            $ticket->resolved_at = now();
        }

        if ($validated['status'] !== Ticket::STATUS_RESOLVED) {
            $ticket->resolved_at = null;
        }

        $ticket->save();

        if ($oldStatus !== $ticket->status) {
            TicketActivity::record(
                $ticket,
                $request->user(),
                TicketActivity::ACTION_STATUS_CHANGED,
                ['old' => $oldStatus, 'new' => $ticket->status]
            );

            if ($ticket->requestor && $ticket->requestor_id !== $request->user()->id) {
                $ticket->requestor->notify(
                    new TicketStatusChangedNotification($ticket, $oldStatus, $ticket->status, $request->user())
                );
            }
        }

        return back()->with('success', 'Status tiket diperbarui.');
    }
}
