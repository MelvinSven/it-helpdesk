<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_CREATED = 'created';
    public const ACTION_ASSIGNED = 'assigned';
    public const ACTION_REASSIGNED = 'reassigned';
    public const ACTION_STATUS_CHANGED = 'status_changed';

    protected $fillable = ['ticket_id', 'user_id', 'action', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        Ticket $ticket,
        ?User $actor,
        string $action,
        array $meta = []
    ): self {
        return static::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id,
            'action' => $action,
            'meta' => $meta,
        ]);
    }
}
