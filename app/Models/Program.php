<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = [
        'nama_program',
        'desc_program',
    ];

    public function aktiviti(): HasMany
    {
        return $this->hasMany(Aktiviti::class);
    }

    public function ptjs(): BelongsToMany
    {
        return $this->belongsToMany(Ptj::class, 'program_ptj')->withTimestamps();
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->nama_program.($this->desc_program ? ' - '.$this->desc_program : ''));
    }

    protected static function booted(): void
    {
        static::deleting(function (Program $program): void {
            $program->aktiviti()->delete();
            $program->ptjs()->detach();
        });
    }
}
