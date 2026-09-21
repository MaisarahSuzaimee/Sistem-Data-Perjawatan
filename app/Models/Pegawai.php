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

    /**
     * Pegawai whose jawatan_gred matches the waran jawatan, and who are not assigned to another waran.
     *
     * @param  array<int, mixed>  $jawatanIds
     * @param  array<int, mixed>  $gredIds
     * @return array<int, string>
     */
    public static function penyandangOptions(array $jawatanIds, array $gredIds, ?int $exceptWaranJawatanId = null, ?int $currentPegawaiId = null): array
    {
        $jawatanIds = collect($jawatanIds)
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $gredIds = collect($gredIds)
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $options = [];

        if ($jawatanIds !== [] && $gredIds !== []) {
            $jawatanGredIds = Jawatan_Gred::query()
                ->whereIn('jawatan_id', $jawatanIds)
                ->whereIn('gred_id', $gredIds)
                ->pluck('id');

            $query = static::query()
                ->where('is_kontrak', false)
                ->whereIn('jawatan_gred_id', $jawatanGredIds)
                ->whereNotIn('id', function ($assigned) use ($exceptWaranJawatanId): void {
                    $assigned->select('pegawai_id')
                        ->from('waran_jawatans')
                        ->whereNotNull('pegawai_id')
                        ->whereNull('deleted_at');

                    if ($exceptWaranJawatanId !== null) {
                        $assigned->where('id', '!=', $exceptWaranJawatanId);
                    }
                });

            $user = auth()->user();

            if ($user && ! in_array($user->role, [1, 2], true)) {
                $query->where('ptj_id', $user->ptj_id);
            }

            $options = $query
                ->orderBy('nama')
                ->get()
                ->mapWithKeys(fn (Pegawai $pegawai): array => [
                    $pegawai->id => "{$pegawai->nama} ({$pegawai->nokp})",
                ])
                ->all();
        }

        if ($currentPegawaiId && ! array_key_exists($currentPegawaiId, $options)) {
            $current = static::withoutGlobalScopes()->find($currentPegawaiId);

            if ($current) {
                $options[$current->id] = "{$current->nama} ({$current->nokp})";
            }
        }

        return $options;
    }

    public function isTidakLengkap(): bool
    {
        return $this->tidakLengkapReasons() !== [];
    }

    /**
     * Missing fields that make this pegawai "Tidak Lengkap".
     *
     * @return list<string>
     */
    public function tidakLengkapReasons(): array
    {
        $reasons = [];

        if ($this->ptj_id === null) {
            $reasons[] = 'PTJ';
        }

        if ($this->ptj?->usesBahagianHierarchy() && $this->bahagian_id === null) {
            $reasons[] = 'bahagian';
        }

        $missingUnit = $this->unit_id === null && (int) $this->ada_unit === 0;
        $missingSubunit = $this->subunit_id === null && (int) $this->ada_subunit === 0;

        if ($missingUnit && $missingSubunit) {
            $reasons[] = 'unit/subunit';
        } elseif ($missingUnit) {
            $reasons[] = 'unit';
        } elseif ($missingSubunit) {
            $reasons[] = 'subunit';
        }

        // Kontrak and Jawatan Tanpa Waran do not need a waran assignment.
        if ((int) $this->is_kontrak === 1 || (int) $this->is_jtw === 1) {
            return $reasons;
        }

        $hasWaran = $this->waranJawatan()
            ->withoutGlobalScopes()
            ->whereHas('waran')
            ->exists();

        if (! $hasWaran) {
            $reasons[] = 'waran';
        }

        return $reasons;
    }

    /**
     * Hover text for the "Tidak Lengkap" badge, e.g. "Sila tetapkan unit/subunit / waran."
     */
    public function tidakLengkapTooltip(): ?string
    {
        $reasons = $this->tidakLengkapReasons();

        if ($reasons === []) {
            return null;
        }

        return 'Sila tetapkan '.implode(' / ', $reasons).'.';
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
                ->orWhere(function (Builder $q): void {
                    $q->where('is_kontrak', 0)
                        ->where('is_jtw', 0)
                        ->whereDoesntHave('waranJawatan.waran');
                });
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
            ->where(function (Builder $q): void {
                $q->where('is_kontrak', 1)
                    ->orWhere('is_jtw', 1)
                    ->orWhereHas('waranJawatan.waran');
            });
    }
}
