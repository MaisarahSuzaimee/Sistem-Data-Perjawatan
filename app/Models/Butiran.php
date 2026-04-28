<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Butiran extends Model
{
    protected $fillable =[
        'aktiviti_id',
        'butiran'
    ];

    public function aktiviti()
    {
        return $this->belongsTo(Aktiviti::class, 'aktiviti_id');
    }
}
