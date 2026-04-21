<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jawatan extends Model
{
    protected $table = 'jawatans';
    protected $fillable = [
        'kod_jawatan',
        'desc_jawatan',
    ];
}
