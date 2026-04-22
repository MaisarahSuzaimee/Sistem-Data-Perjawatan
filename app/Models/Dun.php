<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dun extends Model
{
    protected $fillable = [
        'parlimen_id',
        'nama_dun'
    ];

    public function parlimen()
    {
        $this->belongsTo(Parlimen::class, 'parlimen_id');
    }
}
