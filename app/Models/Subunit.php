<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subunit extends Model
{
    protected $fillable = [
        'unit_id',
        'dun_id',
        'nama_subunit',
        'parlimen_id',
        'aktiviti_id',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function dun(): BelongsTo
    {
        return $this->belongsTo(Dun::class, 'dun_id');
    }

    public function parlimen(): BelongsTo
    {
        return $this->belongsTo(Parlimen::class);
    }

    public function aktiviti(): BelongsTo
    {
        return $this->belongsTo(Aktiviti::class, 'aktiviti_id');
    }
}
