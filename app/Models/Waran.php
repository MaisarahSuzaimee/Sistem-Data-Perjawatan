<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Waran extends Model
{
    protected $fillable = [
        'no_waran',
        'puncakuasa',
        'jik',
        'catatan',
        'parent_id'
    ];

    // public function ptj()
    // {
    //     return $this->hasMany(Ptj::class);
    // }

    public function waranJawatans()
{
    return $this->hasMany(WaranJawatan::class, 'waran_id');
}

public function getAktivitiListAttribute()
{
    return $this->waranJawatans
        ->pluck('aktiviti.nama_aktiviti')
        ->filter()
        ->unique()
        ->join('<br>');
}

public function getPenempatanListAttribute()
{
    return $this->waranJawatans
        ->groupBy(fn ($wj) => $wj->ptj?->nama_ptj)
        ->map(function ($items, $ptjName) {
            return $ptjName . ' (' . $items->count() . ')';
        })
        ->filter()
        ->join('<br>');
}

public function getButiranListAttribute()
{
    return $this->waranJawatans
        ->groupBy(fn ($wj) => $wj->butiran)
        ->map(function ($items, $butiran) {
            return $butiran . ' (' . $items->count() . ')';
        })
        ->filter()
        ->join('<br>');
}

protected static function booted()
{
    static::deleting(function ($waran) {
        $waran->waranJawatans()->delete();
    });

    // static::saved(function ($waran) {

    //     if (! $waran->jik) return;

    //     $count = $waran->waranJawatan()->count();

    //     if ($waran->jik > $count) {

    //         for ($i = $count; $i < $waran->jik; $i++) {
    //             $waran->waranJawatan()->create([]);
    //         }
    //     }

    //     if ($waran->jik < $count) {

    //         $waran->waranJawatan()
    //             ->latest()
    //             ->take($count - $waran->jik)
    //             ->delete();
    //     }
    // });

     static::created(function ($waran) {

        // only generate rows for ANY waran (parent or child)
        $count = $waran->jik ?? 0;

        for ($i = 0; $i < $count; $i++) {
            $waran->waranJawatans()->create([
                'ptj_id' => null,
                'aktiviti_id' => null,
                'butiran' => null,
                'jawatan_id' => null,
                'gred_id' => null,
                'jawatan_gred_id' => null,
                'pegawai_id' => null,
                'catatan_jawatan' => null,
            ]);
        }

    });
}

public function parent()
{
    return $this->belongsTo(Waran::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Waran::class, 'parent_id');
}

// public function allWaranJawatan()
// {
//     return $this->waranJawatan->merge(
//         $this->children->flatMap->waranJawatan
//     );
// }

public function allWaranIds(): array
{
    return collect([$this->id])
        ->merge($this->children->pluck('id'))
        ->toArray();
}

}
