<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemImage;
use App\Models\ProcurementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Item::class);

        $query = Item::query();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($condition = $request->string('condition')->toString()) {
            $query->where('condition', $condition);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('brand_name', 'like', "%{$search}%")
                    ->orWhere('mac_address', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Items/Index', [
            'items' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'condition', 'search']),
            'can' => [
                'manage' => $request->user()->isAdmin(),
            ],
        ]);
    }

    public function show(Request $request, Item $item): Response
    {
        $this->authorize('view', $item);

        // Borrowing logs are linked by FK; full history, newest first.
        $borrows = $item->borrowRecords()
            ->latest('borrow_date')->latest('id')->get();

        $isAdmin = $request->user()->isAdmin();

        // The procurement archive is admin-only, so only admins see linked
        // requests and the pool of ones they can still attach by number.
        $procurements = $isAdmin
            ? $item->procurementRequests()
                ->orderByDesc('request_date')
                ->orderByDesc('procurement_requests.id')
                ->get()
            : [];

        // A request may belong to several items, so the pool is every request
        // not already linked to this one.
        $availableProcurements = $isAdmin
            ? ProcurementRequest::whereDoesntHave('items', fn ($q) => $q->whereKey($item->id))
                ->orderBy('request_number')
                ->get(['procurement_requests.id', 'request_number', 'requested_item', 'employee_name'])
            : [];

        return Inertia::render('Items/Show', [
            'item' => $item->load('images'),
            'borrows' => $borrows,
            'procurements' => $procurements,
            'availableProcurements' => $availableProcurements,
            'can' => [
                'manage' => $isAdmin,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Item::class);

        return Inertia::render('Items/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Item::class);

        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:100', 'unique:items,serial_number'],
            'item_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['required', 'string', 'max:255'],
            'mac_address' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:100'],
            'condition' => ['required', Rule::in(Item::CONDITIONS)],
            'description' => ['nullable', 'string', 'max:5000'],
            'item_image' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('item_image')) {
            $validated['item_image'] = $request->file('item_image')->store('items', 'public');
        }

        $validated['status'] = Item::STATUS_AVAILABLE;

        Item::create($validated);

        return redirect()->route('items.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit(Item $item): Response
    {
        $this->authorize('update', $item);

        return Inertia::render('Items/Edit', [
            'item' => $item,
        ]);
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $this->authorize('update', $item);

        // Status is driven by borrow/return, so it stays out of the edit form.
        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:100', Rule::unique('items', 'serial_number')->ignore($item->id)],
            'item_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['required', 'string', 'max:255'],
            'mac_address' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:100'],
            'condition' => ['required', Rule::in(Item::CONDITIONS)],
            'description' => ['nullable', 'string', 'max:5000'],
            'item_image' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('item_image')) {
            // Replace the old image, removing it from disk to avoid orphans.
            if ($item->item_image) {
                Storage::disk('public')->delete($item->item_image);
            }
            $validated['item_image'] = $request->file('item_image')->store('items', 'public');
        } else {
            unset($validated['item_image']); // keep the existing image
        }

        $item->update($validated);

        return redirect()->route('items.index')->with('success', 'Barang berhasil diperbarui.');
    }

    public function storeImages(Request $request, Item $item): RedirectResponse
    {
        $this->authorize('update', $item);

        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $files = $request->file('images');

        // The first upload becomes the main image when the item has none yet;
        // everything else lands in the gallery.
        if (! $item->item_image) {
            $item->update(['item_image' => array_shift($files)->store('items', 'public')]);
        }

        $item->images()->createMany(array_map(
            fn ($file) => ['image_path' => $file->store('items', 'public')],
            $files,
        ));

        return back()->with('success', 'Gambar barang berhasil diunggah.');
    }

    public function destroyImage(Item $item, ItemImage $image): RedirectResponse
    {
        $this->authorize('update', $item);

        // Guard against deleting an image that belongs to another item.
        abort_unless($image->item_id === $item->id, 404);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return back()->with('success', 'Gambar barang berhasil dihapus.');
    }

    public function attachProcurement(Request $request, Item $item): RedirectResponse
    {
        // Managing an item's links is an admin-level update of the item.
        $this->authorize('update', $item);

        $validated = $request->validate([
            'procurement_request_id' => [
                'required',
                Rule::exists('procurement_requests', 'id'),
                // A request can link to many items, but only once per item.
                Rule::unique('item_procurement_request', 'procurement_request_id')
                    ->where('item_id', $item->id),
            ],
        ]);

        $item->procurementRequests()->syncWithoutDetaching([$validated['procurement_request_id']]);

        return back()->with('success', 'Pengajuan barang berhasil dihubungkan.');
    }

    public function detachProcurement(Item $item, ProcurementRequest $procurement): RedirectResponse
    {
        $this->authorize('update', $item);

        // Guard against detaching a request that isn't linked to this item.
        abort_unless($item->procurementRequests()->whereKey($procurement->id)->exists(), 404);

        $item->procurementRequests()->detach($procurement->id);

        return back()->with('success', 'Tautan pengajuan barang berhasil dilepas.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        $this->authorize('delete', $item);

        abort_if($item->status === Item::STATUS_BORROWED, 422, 'Barang yang sedang dipinjam tidak dapat dihapus.');

        $name = $item->item_name;

        if ($item->item_image) {
            Storage::disk('public')->delete($item->item_image);
        }

        // Gallery rows cascade with the item, but their files don't.
        foreach ($item->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $item->delete();

        return redirect()->route('items.index')->with('success', "Barang {$name} berhasil dihapus.");
    }
}
