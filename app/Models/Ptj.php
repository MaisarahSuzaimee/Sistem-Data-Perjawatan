<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ptj extends Model
{
    protected $table = 'ptjs';

    protected $fillable = [
        'nama_ptj',
        'kod_ptj',
        'alamat',
        'pengarah',
        'is_jkn',
        'rujukan_surat',
        'parlimen_id',
        'dun_id',
    ];

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_ptj')->withTimestamps();
    }

    public function bahagians(): HasMany
    {
        return $this->hasMany(Bahagian::class);
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

    /**
     * Hierarchy 1 (Program → PTJ → Bahagian → Unit → Subunit) for JKN PTJs.
     * Hierarchy 2 (Program → PTJ → Unit → Subunit) for all others.
     */
    public function usesBahagianHierarchy(): bool
    {
        return (bool) $this->is_jkn;
    }

    public static function usesBahagianHierarchyFor(?int $ptjId): bool
    {
        if ($ptjId === null) {
            return false;
        }

        return (bool) static::query()->whereKey($ptjId)->value('is_jkn');
    }
}
