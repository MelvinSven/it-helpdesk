<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Notifications\NewUnassignedTicketNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Ticket::class);

        $user = $request->user();
        $query = Ticket::query()->with(['requestor:id,name', 'assignee:id,name', 'category:id,name']);

        $this->applyScope($query, $user);
        $this->applyFilters($query, $request);

        $tickets = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'categories' => Category::where('is_active', true)->get(['id', 'name']),
            'filters' => $request->only(['status', 'priority', 'category_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Ticket::class);

        return Inertia::render('Tickets/Create', [
            'categories' => Category::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Ticket::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets', 'public');
        }

        $ticket = Ticket::create([
            'ticket_code' => Ticket::generateCode(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'priority' => $validated['priority'],
            'status' => Ticket::STATUS_NEW,
            'requestor_id' => $request->user()->id,
            'attachment_path' => $attachmentPath,
        ]);

        TicketActivity::record($ticket, $request->user(), TicketActivity::ACTION_CREATED);

        $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->get();
        Notification::send($admins, new NewUnassignedTicketNotification($ticket));

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_code} berhasil dibuat.");
    }

    public function show(Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'requestor:id,name,user_id,role',
            'assignee:id,name,user_id,role',
            'category:id,name',
            'comments.user:id,name,role',
            'activities.user:id,name,role',
        ]);

        $user = request()->user();

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
            'can' => [
                'assign' => $user->can('assign', $ticket),
                'update_status' => $user->can('updateStatus', $ticket),
                'comment' => $user->can('comment', $ticket),
                'delete' => $user->can('delete', $ticket),
            ],
            'it_support_users' => $user->isAdmin()
                ? User::where('role', User::ROLE_IT_SUPPORT)
                    ->where('is_active', true)
                    ->get(['id', 'name', 'user_id'])
                : [],
        ]);
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);

        $code = $ticket->ticket_code;

        if ($ticket->attachment_path) {
            Storage::disk('public')->delete($ticket->attachment_path);
        }

        $ticket->delete();

        return redirect()->route('tickets.index')
            ->with('success', "Tiket {$code} berhasil dihapus.");
    }

    protected function applyScope($query, User $user): void
    {
        // All roles can see every ticket in the list. Conversation access
        // (pelapor + penangan) is enforced separately by TicketPolicy::comment.
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
