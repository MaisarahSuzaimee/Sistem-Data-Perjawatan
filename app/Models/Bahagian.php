<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bahagian extends Model
{
    protected $table = 'bahagians';
    protected $fillable = [
        'ptj_id',
        'nama_bahagian',
    ];

    public function ptj()
    {
        return $this->belongsTo(PTJ::class, 'ptj_id');
    }

    public function units()
{
    return $this->hasMany(Unit::class);
}
}
