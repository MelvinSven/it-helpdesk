<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STAFF = 'staff';

    public const ROLE_IT_SUPPORT = 'it_support';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'password',
        'role',
        'department',
        'proyek',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Borrow records keep a copy of the borrower's name and proyek so the
        // borrow list can search/filter on them without joining users. Keep
        // those copies in step with the user so a rename or project move shows
        // up everywhere. Any Eloquent save fires this (admin edit, profile
        // page, seeders); raw query-builder updates on users bypass it.
        static::updated(function (User $user) {
            if (! $user->wasChanged(['name', 'proyek'])) {
                return;
            }

            BorrowRecord::where('borrower_id', $user->id)->update([
                'borrower_name' => $user->name,
                'borrower_proyek' => $user->proyek,
            ]);
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isItSupport(): bool
    {
        return $this->role === self::ROLE_IT_SUPPORT;
    }

    public function requestedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requestor_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    public function ticketComments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }
}
