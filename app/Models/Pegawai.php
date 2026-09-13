<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pegawai extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ptj_id',
        'bahagian_id',
        'unit_id',
        'subunit_id',
        'jawatan_gred_id',
        'opsyen_pencen_id',
        'nama',
        'nokp',
        'jantina',
        'tarikh_lantikan',
        'tarikh_sah_jawatan',
        'tarikh_pencen',
        'is_tetap',
        'is_kontrak_interim',
        'is_kontrak',
        'is_kontrak_isi_tetap',
        'is_kup',
        'is_kupj',
        'is_jtw',
        'tarikh_pinjam',
        'tarikh_sandang',
        'emel',
        'ada_unit',
        'ada_subunit',
    ];

    protected static function booted()
    {
        static::addGlobalScope('ptj_access', function (Builder $query) {
            $user = auth()->user();

            // No authenticated user (Artisan, Queue, etc.)
            if (! $user) {
                return;
            }

            // Superadmin & Admin can see all
            if (in_array($user->role, [1, 2])) {
                return;
            }

            $query->where(function ($q) use ($user) {
                $q->where('ptj_id', $user->ptj_id)
                    ->orWhereHas('waranJawatan', function ($waranQuery) use ($user) {
                        $waranQuery->where('ptj_id', $user->ptj_id);
                    });
            });
        });
    }

    public function ptj()
    {
        return $this->belongsTo(Ptj::class, 'ptj_id');
    }

    public function bahagian()
    {
        return $this->belongsTo(Bahagian::class, 'bahagian_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function subunit()
    {
        return $this->belongsTo(Subunit::class, 'subunit_id');
    }

    public function jawatan_gred()
    {
        return $this->belongsTo(Jawatan_Gred::class, 'jawatan_gred_id');
    }

    public function opsyenPencen()
    {
        return $this->belongsTo(OpsyenPencen::class, 'opsyen_pencen_id');
    }

    public function pegawaiKontrak()
    {
        return $this->hasOne(PegawaiKontrak::class);
    }

    public function waranJawatan()
    {
        return $this->hasOne(WaranJawatan::class, 'pegawai_id');
    }

    public function isTidakLengkap(): bool
    {
        if ($this->ptj_id === null) {
            return true;
        }

        if ($this->ptj?->usesBahagianHierarchy() && $this->bahagian_id === null) {
            return true;
        }

        // Unit: must be filled or "Tiada Unit" checked
        if ($this->unit_id === null && (int) $this->ada_unit === 0) {
            return true;
        }

        // Subunit: must be filled or "Tiada Subunit" checked
        if ($this->subunit_id === null && (int) $this->ada_subunit === 0) {
            return true;
        }

        $hasWaran = $this->waranJawatan()
            ->withoutGlobalScopes()
            ->whereHas('waran')
            ->exists();

        if (! $hasWaran) {
            return true;
        }

        return false;
    }

    /**
     * @param  Builder<Pegawai>  $query
     * @return Builder<Pegawai>
     */
    public function scopeTidakLengkap(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('ptj_id')
                ->orWhere(function (Builder $q): void {
                    $q->whereNull('bahagian_id')
                        ->whereHas('ptj', fn (Builder $ptj): Builder => $ptj->where('is_jkn', true));
                })
                ->orWhere(function (Builder $q): void {
                    $q->whereNull('unit_id')->where('ada_unit', 0);
                })
                ->orWhere(function (Builder $q): void {
                    $q->whereNull('subunit_id')->where('ada_subunit', 0);
                })
                ->orWhereDoesntHave('waranJawatan.waran');
        });
    }

    /**
     * @param  Builder<Pegawai>  $query
     * @return Builder<Pegawai>
     */
    public function scopeLengkap(Builder $query): Builder
    {
        return $query
            ->whereNotNull('ptj_id')
            ->where(function (Builder $q): void {
                $q->whereDoesntHave('ptj', fn (Builder $ptj): Builder => $ptj->where('is_jkn', true))
                    ->orWhereNotNull('bahagian_id');
            })
            ->where(function (Builder $q): void {
                $q->whereNotNull('unit_id')->orWhere('ada_unit', 1);
            })
            ->where(function (Builder $q): void {
                $q->whereNotNull('subunit_id')->orWhere('ada_subunit', 1);
            })
            ->whereHas('waranJawatan.waran');
    }
}
