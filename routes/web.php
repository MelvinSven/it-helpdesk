<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserImportController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProcurementRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketAssignmentController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketExportController;
use App\Http\Controllers\TicketStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/export', TicketExportController::class)->name('tickets.export');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::patch('/tickets/{ticket}/assign', [TicketAssignmentController::class, 'update'])->name('tickets.assign');
    Route::patch('/tickets/{ticket}/status', [TicketStatusController::class, 'update'])->name('tickets.status');
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');

    // Electronic item master data. Admin-only end to end (read + write), gated
    // by ItemPolicy inside the controller. Staff/IT Support never hit these
    // routes; they borrow via the borrow form, which loads items on its own.
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::post('/items/import', [ItemImportController::class, 'store'])->name('items.import');
    Route::get('/items/import/template', [ItemImportController::class, 'template'])->name('items.import.template');
    Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
    Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
    Route::patch('/items/{item}', [ItemController::class, 'update'])->name('items.update');
    Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');

    // Link/unlink a procurement request to an item from the item detail page.
    Route::post('/items/{item}/procurements', [ItemController::class, 'attachProcurement'])->name('items.procurements.attach');
    Route::delete('/items/{item}/procurements/{procurement}', [ItemController::class, 'detachProcurement'])->name('items.procurements.detach');

    // Borrow & return. Any authenticated user may borrow an available item;
    // the item is chosen by serial number inside the form.
    Route::get('/borrows', [BorrowController::class, 'index'])->name('borrows.index');
    Route::get('/borrows/create', [BorrowController::class, 'create'])->name('borrows.create');
    Route::post('/borrows', [BorrowController::class, 'store'])->name('borrows.store');
    Route::get('/borrows/{borrow}', [BorrowController::class, 'show'])->name('borrows.show');
    Route::get('/borrows/{borrow}/return', [BorrowController::class, 'returnForm'])->name('borrows.return.create');
    Route::patch('/borrows/{borrow}/return', [BorrowController::class, 'returnStore'])->name('borrows.return.store');
    Route::delete('/borrows/{borrow}', [BorrowController::class, 'destroy'])->name('borrows.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate'])->name('users.activate');
        Route::post('/users/import', [UserImportController::class, 'store'])->name('users.import');
        Route::get('/users/import/template', [UserImportController::class, 'template'])->name('users.import.template');

        // Item procurement request archive. Admin-only CRUD over an immutable
        // paper trail; the scanned form is stored as image/PDF on the public disk.
        Route::get('/procurements', [ProcurementRequestController::class, 'index'])->name('procurements.index');
        Route::get('/procurements/create', [ProcurementRequestController::class, 'create'])->name('procurements.create');
        Route::post('/procurements', [ProcurementRequestController::class, 'store'])->name('procurements.store');
        Route::get('/procurements/{procurement}', [ProcurementRequestController::class, 'show'])->name('procurements.show');
        Route::get('/procurements/{procurement}/edit', [ProcurementRequestController::class, 'edit'])->name('procurements.edit');
        Route::patch('/procurements/{procurement}', [ProcurementRequestController::class, 'update'])->name('procurements.update');
        Route::delete('/procurements/{procurement}', [ProcurementRequestController::class, 'destroy'])->name('procurements.destroy');
    });
});

require __DIR__.'/auth.php';
