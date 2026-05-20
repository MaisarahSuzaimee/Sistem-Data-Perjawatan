<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PegawaiKontrak extends Model
{
    protected $fillable = [
        'pegawai_id',
        'tarikh_lantikan1',
        'tarikh_tamat1',
        'tarikh_lantikan2',
        'tarikh_tamat2',
        'tarikh_lantikan3',
        'tarikh_tamat3',
        'tarikh_lantikan4',
        'tarikh_tamat4',
        'tarikh_lantikan5',
        'tarikh_tamat5',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    
}
