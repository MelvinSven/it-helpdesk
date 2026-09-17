<?php

namespace Tests\Feature;

use App\Models\BorrowRecord;
use App\Models\Category;
use App\Models\Item;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserDataPropagationTest extends TestCase
{
    use RefreshDatabase;

    private int $itemSeq = 0;

    private function borrowFor(User $borrower, array $overrides = []): BorrowRecord
    {
        $this->itemSeq++;

        $item = Item::create([
            'kode_barang' => "LIX-EL-SYNC-{$this->itemSeq}",
            'serial_number' => "SN-SYNC-{$this->itemSeq}",
            'item_name' => 'Laptop',
            'brand_name' => 'Acme',
            'type' => 'Laptop',
            'condition' => Item::CONDITION_GOOD,
            'status' => Item::STATUS_BORROWED,
        ]);

        return BorrowRecord::create(array_merge([
            'item_id' => $item->id,
            'borrower_id' => $borrower->id,
            'item_name' => $item->item_name,
            'kode_barang' => $item->kode_barang,
            'borrower_name' => $borrower->name,
            'borrower_proyek' => $borrower->proyek,
            'borrow_date' => '2026-09-01',
            'purpose' => 'Kerja lapangan',
            'status' => BorrowRecord::STATUS_BORROWED,
        ], $overrides));
    }

    private function ticketFor(User $requestor): Ticket
    {
        $category = Category::firstOrCreate(['name' => 'Perangkat Keras'], ['is_active' => true]);

        return Ticket::create([
            'ticket_code' => Ticket::generateCode(),
            'title' => 'Printer rusak',
            'description' => 'Tidak bisa cetak.',
            'requestor_id' => $requestor->id,
            'category_id' => $category->id,
            'priority' => Ticket::PRIORITY_MEDIUM,
            'status' => Ticket::STATUS_NEW,
        ]);
    }

    public function test_admin_edit_updates_the_users_borrow_records(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $borrower = User::factory()->create(['name' => 'Budi Lama', 'proyek' => 'Proyek A']);
        $other = User::factory()->create(['name' => 'Citra', 'proyek' => 'Proyek B']);
        $mine = $this->borrowFor($borrower);
        $returned = $this->borrowFor($borrower, ['status' => BorrowRecord::STATUS_RETURNED]);
        $theirs = $this->borrowFor($other);

        $this->actingAs($admin)->patch(route('admin.users.update', $borrower), [
            'name' => 'Budi Baru',
            'email' => $borrower->email,
            'role' => $borrower->role,
            'department' => 'Manajer',
            'proyek' => 'Proyek C',
        ])->assertRedirect(route('admin.users.index'));

        foreach ([$mine, $returned] as $record) {
            $record->refresh();
            $this->assertSame('Budi Baru', $record->borrower_name);
            $this->assertSame('Proyek C', $record->borrower_proyek);
        }

        $theirs->refresh();
        $this->assertSame('Citra', $theirs->borrower_name);
        $this->assertSame('Proyek B', $theirs->borrower_proyek);
    }

    public function test_clearing_proyek_clears_it_on_borrow_records(): void
    {
        $borrower = User::factory()->create(['proyek' => 'Proyek A']);
        $record = $this->borrowFor($borrower);

        $borrower->update(['proyek' => null]);

        $this->assertNull($record->fresh()->borrower_proyek);
    }

    public function test_profile_name_change_updates_borrow_records(): void
    {
        $borrower = User::factory()->create(['name' => 'Dewi Lama']);
        $record = $this->borrowFor($borrower);

        $this->actingAs($borrower)->patch(route('profile.update'), [
            'name' => 'Dewi Baru',
            'email' => $borrower->email,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Dewi Baru', $record->fresh()->borrower_name);
    }

    public function test_borrow_list_filters_on_the_users_new_proyek(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'proyek' => null]);
        $borrower = User::factory()->create(['proyek' => 'Proyek Lama']);
        $this->borrowFor($borrower);

        $borrower->update(['proyek' => 'Proyek Z']);

        $this->actingAs($admin)->get(route('borrows.index', ['proyek' => 'Proyek Z']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('borrows.data', 1)
                ->where('borrows.data.0.borrower_proyek', 'Proyek Z')
                ->where('projects', fn ($projects) => ! collect($projects)->contains('Proyek Lama'))
            );
    }

    public function test_activity_log_shows_the_assignees_current_name(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $itSupport = User::factory()->create(['role' => User::ROLE_IT_SUPPORT, 'name' => 'Eko Lama']);
        $ticket = $this->ticketFor($staff);

        $this->actingAs($admin)->patch(route('tickets.assign', $ticket), [
            'assignee_id' => $itSupport->id,
        ])->assertSessionHasNoErrors();

        $itSupport->update(['name' => 'Eko Baru']);

        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->has('ticket.activities', 1)
                ->where('ticket.activities.0.meta.assignee_id', $itSupport->id)
                ->where('ticket.activities.0.meta.assignee_name', 'Eko Baru')
            );
    }

    public function test_resync_migration_repairs_existing_stale_copies(): void
    {
        $borrower = User::factory()->create(['name' => 'Fajar Baru', 'proyek' => 'Proyek Baru']);
        $stale = $this->borrowFor($borrower, [
            'borrower_name' => 'Fajar Lama',
            'borrower_proyek' => 'Proyek Lama',
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $first = User::factory()->create(['role' => User::ROLE_IT_SUPPORT, 'name' => 'Gita']);
        $current = User::factory()->create(['role' => User::ROLE_IT_SUPPORT, 'name' => 'Hadi Baru']);
        $ticket = $this->ticketFor($staff);
        $ticket->update(['assignee_id' => $current->id]);

        // Old-format rows store the name only. The first assignee still has
        // that name; the current one was renamed after being assigned.
        $firstActivity = TicketActivity::record($ticket, $staff, TicketActivity::ACTION_ASSIGNED, ['assignee_name' => 'Gita']);
        $firstActivity->forceFill(['created_at' => now()->subHour()])->save();
        $latestActivity = TicketActivity::record($ticket, $staff, TicketActivity::ACTION_REASSIGNED, ['assignee_name' => 'Hadi Lama']);

        // A row whose name matches nobody and whose ticket has no assignee
        // must be left untouched.
        $orphanTicket = $this->ticketFor($staff);
        $orphanActivity = TicketActivity::record($orphanTicket, $staff, TicketActivity::ACTION_ASSIGNED, ['assignee_name' => 'Tidak Ada']);
        DB::table('tickets')->where('id', $orphanTicket->id)->update(['assignee_id' => null]);

        $migration = require database_path('migrations/2026_09_17_000001_resync_user_data_copies.php');
        $migration->up();

        $stale->refresh();
        $this->assertSame('Fajar Baru', $stale->borrower_name);
        $this->assertSame('Proyek Baru', $stale->borrower_proyek);

        $this->assertSame($first->id, $firstActivity->fresh()->meta['assignee_id']);
        $this->assertSame($current->id, $latestActivity->fresh()->meta['assignee_id']);
        $this->assertSame(['assignee_name' => 'Tidak Ada'], $orphanActivity->fresh()->meta);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->where('ticket.activities.0.meta.assignee_name', 'Gita')
                ->where('ticket.activities.1.meta.assignee_name', 'Hadi Baru')
            );
    }
}
