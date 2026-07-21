<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Notifications\TicketResolvedNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

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
                $notification = $ticket->status === Ticket::STATUS_RESOLVED
                    ? new TicketResolvedNotification($ticket, $request->user())
                    : new TicketStatusChangedNotification($ticket, $oldStatus, $ticket->status, $request->user());

                $ticket->requestor->notify($notification);
            }

            // Admins keep an eye on every status change, except the one they made
            // themselves and the requestor who was already notified above.
            $admins = User::where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->whereNotIn('id', array_filter([$request->user()->id, $ticket->requestor_id]))
                ->get();

            Notification::send(
                $admins,
                new TicketStatusChangedNotification($ticket, $oldStatus, $ticket->status, $request->user())
            );
        }

        return back()->with('success', 'Status tiket diperbarui.');
    }
}
