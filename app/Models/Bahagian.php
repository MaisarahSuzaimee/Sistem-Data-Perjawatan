<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bahagian extends Model
{
    use SoftDeletes;

    protected $table = 'bahagians';

    protected $fillable = [
        'ptj_id',
        'nama_bahagian',
        'parlimen_id',
        'dun_id',
    ];

    public function ptj(): BelongsTo
    {
        return $this->belongsTo(Ptj::class, 'ptj_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function parlimen(): BelongsTo
    {
        return $this->belongsTo(Parlimen::class);
    }

    public function dun(): BelongsTo
    {
        return $this->belongsTo(Dun::class);
    }
}
