<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleDriveConnection extends Model
{
    protected $fillable = [
        'token',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted:array',
        ];
    }

    public static function isConnected(): bool
    {
        $token = static::query()->latest('id')->first()?->token;

        return is_array($token) && ! empty($token['refresh_token']);
    }
}
