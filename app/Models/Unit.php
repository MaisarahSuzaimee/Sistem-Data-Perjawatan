<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'bahagian_id',
        'nama_unit'
    ];

    public function bahagian()
    {
        return $this->belongsTo(Bahagian::class, 'bahagian_id');

    }

   
}
