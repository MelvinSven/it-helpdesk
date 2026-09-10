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

    public const KODE_PREFIX = 'LIX-EL-';

    /** LIX-EL-<SEGMENT>-<SEGMENT>, each segment uppercase letters/digits. */
    public const KODE_PATTERN = '/^LIX-EL-[A-Z0-9]+-[A-Z0-9]+$/';

    protected $fillable = [
        'kode_barang',
        'serial_number',
        'item_name',
        'brand_name',
        'mac_address',
        'type',
        'condition',
        'description',
        'status',
        'item_image',
    ];

    public function borrowRecords(): HasMany
    {
        return $this->hasMany(BorrowRecord::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItemImage::class);
    }

    public function procurementRequests(): BelongsToMany
    {
        return $this->belongsToMany(ProcurementRequest::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Builds a full Kode Barang from free-typed input: uppercases, turns any
     * run of spaces/punctuation into a single dash, and caps it at two
     * segments ("laptop 12" → "LIX-EL-LAPTOP-12"). Same rules as
     * formatKodeBarang() in resources/js/lib/itemFormat.ts.
     */
    public static function formatKodeBarang(string $value): string
    {
        $rest = mb_strtoupper($value);

        // A bare or truncated prefix ("LIX-EL") carries no segments; don't let
        // it round-trip into the valid-looking "LIX-EL-LIX-EL".
        if (str_starts_with(self::KODE_PREFIX, $rest)) {
            return self::KODE_PREFIX;
        }

        while (str_starts_with($rest, self::KODE_PREFIX)) {
            $rest = substr($rest, strlen(self::KODE_PREFIX));
        }

        $cleaned = ltrim(preg_replace('/[^A-Z0-9]+/', '-', $rest), '-');
        $parts = explode('-', $cleaned, 2);

        return self::KODE_PREFIX.$parts[0]
            .(isset($parts[1]) ? '-'.str_replace('-', '', $parts[1]) : '');
    }
}
