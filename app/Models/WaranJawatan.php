<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaranJawatan extends Model
{
    protected $fillable = [
        'waran_id',
        'ptj_id',
        'aktiviti_id',
        'pegawai_id',
        'jawatan_gred_id',
        'butiran',
        'catatan_jawatan'
    ];

    public function waran()
    {
        return $this->belongsTo(Waran::class, 'waran_id');
    }

    public function ptj()
    {
        return $this->belongsTo(Ptj::class, 'ptj_id');
    }

    public function aktiviti()
    {
        return $this->belongsTo(Aktiviti::class, 'aktiviti_id');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    public function jawatan()
    {
        return $this->belongsTo(Jawatan::class);
    }

    public function gred()
    {
        return $this->belongsTo(Gred::class);
    }

    public function jawatanGred()
    {
        return $this->belongsTo(Jawatan_Gred::class, 'jawatan_gred_id');
    }
}
