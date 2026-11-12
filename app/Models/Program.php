<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function scopeAssignedToPtj(Builder $query, int $ptjId): Builder
    {
        return $query->where(function (Builder $query) use ($ptjId): void {
            $query
                ->whereHas(
                    'aktiviti.ptjs',
                    fn (Builder $ptjQuery): Builder => $ptjQuery->where('ptjs.id', $ptjId),
                )
                ->orWhereHas(
                    'ptjs',
                    fn (Builder $ptjQuery): Builder => $ptjQuery->where('ptjs.id', $ptjId),
                );
        });
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->nama_program.($this->desc_program ? ' - '.$this->desc_program : ''));
    }

    public function syncPtjsFromAktiviti(): void
    {
        $ids = $this->aktiviti()
            ->with('ptjs')
            ->get()
            ->flatMap(fn (Aktiviti $aktiviti) => $aktiviti->ptjs->pluck('id'))
            ->unique()
            ->values()
            ->all();

        $this->ptjs()->sync($ids);
    }

    protected static function booted(): void
    {
        static::deleting(function (Program $program): void {
            $program->aktiviti()->delete();
            $program->ptjs()->detach();
        });
    }
}
