<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parlimen extends Model
{
    protected $fillable = [
        'nama_parlimen'
    ];

    public function duns()
    {
        return $this->hasMany(Dun::class);
    }
}
