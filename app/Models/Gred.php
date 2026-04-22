<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gred extends Model
{
    protected $table = 'greds';
    protected $fillable = [
        'kod_gred',
        'desc_gred',
    ];
    
}

