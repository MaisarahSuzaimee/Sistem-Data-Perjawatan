<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aktiviti extends Model
{
    protected $fillable = [
        'program_id',
        'no_aktivit',
        'nama_aktiviti',
        'desc_aktiviti'
    ];

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
}
