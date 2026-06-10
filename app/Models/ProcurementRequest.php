<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProcurementRequest extends Model
{
    protected $fillable = [
        'request_number',
        'employee_name',
        'requested_item',
        'request_date',
        'notes',
        'form_file',
    ];

    protected $casts = [
        'request_date' => 'date',
    ];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class);
    }
}
