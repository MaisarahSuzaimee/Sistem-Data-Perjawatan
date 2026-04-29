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

   public function jawatanGred()
{
    return $this->belongsToMany(
        Jawatan_Gred::class,
        'butiran__jawatans',
        'butiran_id',        // FK for this model
        'jawatan_gred_id'    // FK for related model ✅ FIX
    );
}
}
