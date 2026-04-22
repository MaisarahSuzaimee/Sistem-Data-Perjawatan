<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jawatan_Gred extends Model
{
    protected $table = 'jawatan__greds';

    protected $fillable = [
        'jawatan_id',
        'gred_id'
    ];

    public function jawatan()
    {
        return $this->belongsTo(Jawatan::class, 'jawatan_id');
    }

    public function gred()
    {
        return $this->belongsTo(Gred::class, 'gred_id');
    }
}
