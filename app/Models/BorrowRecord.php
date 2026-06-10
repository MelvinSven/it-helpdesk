<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowRecord extends Model
{
    use HasFactory;

    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'item_id',
        'borrower_id',
        'item_name',
        'serial_number',
        'borrower_name',
        'borrow_date',
        'purpose',
        'borrow_image',
        'status',
        'return_date',
        'return_condition',
        'notes',
        'return_image',
    ];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'return_date' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function isBorrowed(): bool
    {
        return $this->status === self::STATUS_BORROWED;
    }
}
