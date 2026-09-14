<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aktiviti extends Model
{
    protected $fillable = [
        'program_id',
        'no_aktivit',
        'nama_aktiviti',
        'desc_aktiviti',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function ptjs(): BelongsToMany
    {
        return $this->belongsToMany(Ptj::class, 'aktiviti_ptj')->withTimestamps();
    }

    public function butiran(): HasMany
    {
        return $this->hasMany(Butiran::class, 'aktiviti_id');
    }
}
