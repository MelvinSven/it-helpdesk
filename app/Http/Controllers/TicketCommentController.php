<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Notifications\TicketCommentedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketCommentController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('comment', $ticket);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('comments', 'public');
        }

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'attachment_path' => $attachmentPath,
        ]);

        $comment->load('user');

        $author = $request->user();
        $recipients = collect();

        if ($ticket->requestor_id !== $author->id) {
            $recipients->push($ticket->requestor);
        }

        if ($ticket->assignee_id && $ticket->assignee_id !== $author->id) {
            $recipients->push($ticket->assignee);
        }

        foreach ($recipients->filter() as $recipient) {
            $recipient->notify(new TicketCommentedNotification($ticket, $comment));
        }

        return back()->with('success', 'Balasan terkirim.');
    }
}
