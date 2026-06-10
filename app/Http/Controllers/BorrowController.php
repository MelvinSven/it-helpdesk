<?php

namespace App\Http\Controllers;

use App\Models\BorrowRecord;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BorrowController extends Controller
{
    public function index(Request $request): Response
    {
        $query = BorrowRecord::query()->with('item:id,status');

        if (! $request->user()->isAdmin()) {
            $query->where('borrower_id', $request->user()->id);
        }

        // Show every record by default (borrowed + returned); returned rows stay
        // in the table with a "Dikembalikan" status rather than disappearing.
        $status = $request->string('status')->toString() ?: 'all';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('borrower_name', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Borrows/Index', [
            'borrows' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => ['status' => $status, 'search' => $request->string('search')->toString()],
            'can' => ['delete' => $request->user()->isAdmin()],
        ]);
    }

    public function show(Request $request, BorrowRecord $borrow): Response
    {
        return Inertia::render('Borrows/Show', [
            'borrow' => $borrow,
            // The borrower may still return it from the detail page.
            'can_return' => $borrow->isBorrowed()
                && ($request->user()->isAdmin() || $request->user()->id === $borrow->borrower_id),
            'can_delete' => $request->user()->isAdmin(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Borrows/Create', [
            // Only available (not borrowed) items can be picked by serial number.
            'items' => Item::where('status', Item::STATUS_AVAILABLE)
                ->orderBy('serial_number')
                ->get(['id', 'serial_number', 'item_name', 'brand_name', 'type']),
            'users' => User::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'user_id', 'department']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Item is chosen by serial number in the form; only available ones qualify.
            'item_id' => ['required', Rule::exists('items', 'id')->where('status', Item::STATUS_AVAILABLE)],
            // Borrower is a real system user, not free text.
            'borrower_id' => ['required', Rule::exists('users', 'id')->where('is_active', true)],
            'borrow_date' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:1000'],
            'borrow_image' => ['required', 'image', 'max:5120'],
        ]);

        $borrower = User::findOrFail($validated['borrower_id']);

        $itemName = DB::transaction(function () use ($validated, $request, $borrower) {
            // Re-read with a lock and re-check availability inside the transaction
            // so two concurrent requests cannot both borrow the same item.
            $locked = Item::lockForUpdate()->findOrFail($validated['item_id']);
            abort_if(! $locked->isAvailable(), 422, 'Barang ini sedang tidak tersedia untuk dipinjam.');

            // Store the upload only after the guard passes so a lost race
            // doesn't leave an orphaned file on disk.
            $imagePath = $request->hasFile('borrow_image')
                ? $request->file('borrow_image')->store('borrows', 'public')
                : null;

            BorrowRecord::create([
                'item_id' => $locked->id,
                'borrower_id' => $borrower->id,
                'item_name' => $locked->item_name,
                'serial_number' => $locked->serial_number,
                // Snapshot the name so history survives a later rename.
                'borrower_name' => $borrower->name,
                'borrow_date' => $validated['borrow_date'],
                'purpose' => $validated['purpose'],
                'borrow_image' => $imagePath,
                'status' => BorrowRecord::STATUS_BORROWED,
            ]);

            $locked->update(['status' => Item::STATUS_BORROWED]);

            return $locked->item_name;
        });

        return redirect()->route('borrows.index')
            ->with('success', "{$itemName} berhasil dipinjam.");
    }

    public function returnForm(Request $request, BorrowRecord $borrow): Response
    {
        abort_if(! $borrow->isBorrowed(), 404);

        return Inertia::render('Borrows/Return', [
            'borrow' => $borrow->only(['id', 'item_name', 'serial_number', 'borrower_name']),
            'can_return' => $request->user()->isAdmin() || $request->user()->id === $borrow->borrower_id,
        ]);
    }

    public function returnStore(Request $request, BorrowRecord $borrow): RedirectResponse
    {
        abort_if(! $borrow->isBorrowed(), 404);

        $this->authorize('return', $borrow);

        $validated = $request->validate([
            'return_date' => ['required', 'date'],
            'return_condition' => ['required', Rule::in(Item::CONDITIONS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'return_image' => ['required', 'image', 'max:5120'],
        ]);

        DB::transaction(function () use ($borrow, $validated, $request) {
            $locked = BorrowRecord::lockForUpdate()->findOrFail($borrow->id);
            abort_if(! $locked->isBorrowed(), 422, 'Barang ini sudah dikembalikan.');

            // Store the upload only after the guard passes so a lost race
            // doesn't leave an orphaned file on disk.
            $imagePath = $request->hasFile('return_image')
                ? $request->file('return_image')->store('returns', 'public')
                : null;

            $locked->update([
                'status' => BorrowRecord::STATUS_RETURNED,
                'return_date' => $validated['return_date'],
                'return_condition' => $validated['return_condition'],
                'notes' => $validated['notes'] ?? null,
                'return_image' => $imagePath,
            ]);

            // Free the item up again and sync its condition to what came back.
            $locked->item?->update([
                'status' => Item::STATUS_AVAILABLE,
                'condition' => $validated['return_condition'],
            ]);
        });

        return redirect()->route('borrows.index')
            ->with('success', "{$borrow->item_name} berhasil dikembalikan.");
    }

    public function destroy(BorrowRecord $borrow): RedirectResponse
    {
        $this->authorize('delete', $borrow);

        $itemName = $borrow->item_name;

        DB::transaction(function () use ($borrow) {
            // If the record is still active, deleting it would leave the item
            // stuck as borrowed with no record — free it back to available.
            if ($borrow->isBorrowed() && $borrow->item?->status === Item::STATUS_BORROWED) {
                $borrow->item->update(['status' => Item::STATUS_AVAILABLE]);
            }

            // Remove any uploaded photos so they don't orphan on disk.
            foreach ([$borrow->borrow_image, $borrow->return_image] as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }

            $borrow->delete();
        });

        return redirect()->route('borrows.index')
            ->with('success', "Data peminjaman {$itemName} berhasil dihapus.");
    }
}
