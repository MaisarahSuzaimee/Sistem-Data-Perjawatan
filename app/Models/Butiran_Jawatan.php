<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Butiran_Jawatan extends Model
{
    protected $fillable =[
        'butiran_id',
        'jawatan_gred_id'
    ];

    public function butiran()
    {
        return $this->belongsTo(Butiran::class, 'butiran_id');
    }

   public function jawatanGred()
{
    return $this->belongsTo(Jawatan_Gred::class, 'jawatan_gred_id');
}
}
