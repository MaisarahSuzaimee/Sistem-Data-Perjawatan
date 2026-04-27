<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Waran extends Model
{
    protected $fillable = [
        'no_waran',
        'puncakuasa',
        'jik',
        'catatan'
    ];

    public function ptj()
    {
        return $this->hasMany(Ptj::class);
    }
}
