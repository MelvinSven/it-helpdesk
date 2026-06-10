<?php

namespace App\Http\Controllers;

use App\Models\ProcurementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProcurementRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProcurementRequest::class);

        $query = ProcurementRequest::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('employee_name', 'like', "%{$search}%")
                    ->orWhere('requested_item', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Procurements/Index', [
            'procurements' => $query->latest('request_date')->latest('id')
                ->paginate(15)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(ProcurementRequest $procurement): Response
    {
        $this->authorize('view', $procurement);

        // Items the request is linked to (many-to-many), for the detail view.
        $procurement->load([
            'items' => fn ($q) => $q->orderBy('serial_number')
                ->select('items.id', 'serial_number', 'item_name', 'brand_name', 'type', 'status'),
        ]);

        return Inertia::render('Procurements/Show', [
            'procurement' => $procurement,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ProcurementRequest::class);

        return Inertia::render('Procurements/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ProcurementRequest::class);

        $validated = $request->validate([
            'request_number' => ['required', 'string', 'max:100', 'unique:procurement_requests,request_number'],
            'employee_name' => ['required', 'string', 'max:255'],
            'requested_item' => ['required', 'string', 'max:255'],
            'request_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'form_file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $validated['form_file'] = $request->file('form_file')->store('procurements', 'public');

        ProcurementRequest::create($validated);

        return redirect()->route('admin.procurements.index')->with('success', 'Pengajuan barang berhasil ditambahkan.');
    }

    public function edit(ProcurementRequest $procurement): Response
    {
        $this->authorize('update', $procurement);

        return Inertia::render('Procurements/Edit', [
            'procurement' => $procurement,
        ]);
    }

    public function update(Request $request, ProcurementRequest $procurement): RedirectResponse
    {
        $this->authorize('update', $procurement);

        $validated = $request->validate([
            'request_number' => ['required', 'string', 'max:100', Rule::unique('procurement_requests', 'request_number')->ignore($procurement->id)],
            'employee_name' => ['required', 'string', 'max:255'],
            'requested_item' => ['required', 'string', 'max:255'],
            'request_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'form_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        if ($request->hasFile('form_file')) {
            // Replace the old file, removing it from disk to avoid orphans.
            if ($procurement->form_file) {
                Storage::disk('public')->delete($procurement->form_file);
            }
            $validated['form_file'] = $request->file('form_file')->store('procurements', 'public');
        } else {
            unset($validated['form_file']); // keep the existing file
        }

        $procurement->update($validated);

        return redirect()->route('admin.procurements.index')->with('success', 'Pengajuan barang berhasil diperbarui.');
    }

    public function destroy(ProcurementRequest $procurement): RedirectResponse
    {
        $this->authorize('delete', $procurement);

        $number = $procurement->request_number;

        if ($procurement->form_file) {
            Storage::disk('public')->delete($procurement->form_file);
        }

        $procurement->delete();

        return redirect()->route('admin.procurements.index')->with('success', "Pengajuan {$number} berhasil dihapus.");
    }
}
