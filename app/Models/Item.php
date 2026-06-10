<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BORROWED = 'borrowed';

    public const CONDITION_NEW = 'baru';

    public const CONDITION_GOOD = 'baik';

    public const CONDITION_MINOR_DAMAGE = 'rusak_ringan';

    public const CONDITION_MAJOR_DAMAGE = 'rusak_berat';

    /**
     * Controlled vocabulary shared by Item::condition and
     * BorrowRecord::return_condition so a returned condition always maps back
     * onto a value the item form can display.
     */
    public const CONDITIONS = [
        self::CONDITION_NEW,
        self::CONDITION_GOOD,
        self::CONDITION_MINOR_DAMAGE,
        self::CONDITION_MAJOR_DAMAGE,
    ];

    protected $fillable = [
        'serial_number',
        'item_name',
        'brand_name',
        'mac_address',
        'type',
        'condition',
        'status',
        'item_image',
    ];

    public function borrowRecords(): HasMany
    {
        return $this->hasMany(BorrowRecord::class);
    }

    public function procurementRequests(): BelongsToMany
    {
        return $this->belongsToMany(ProcurementRequest::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
