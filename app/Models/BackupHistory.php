<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupHistory extends Model
{
    protected $fillable = [
        'filename',
        'path',
        'size_bytes',
        'status',
        'created_by',
        'completed_at',
        'google_drive_status',
        'google_drive_file_id',
        'google_drive_web_view_link',
        'google_drive_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'completed_at' => 'datetime',
            'google_drive_uploaded_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
