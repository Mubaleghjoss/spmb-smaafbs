<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Log Penggunaan Token Global
 */
class TokenGlobalLog extends Model
{
    use HasFactory;

    protected $table = 'token_global_log';

    protected $fillable = [
        'token_global_id',
        'peserta_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * Relasi ke token global
     */
    public function tokenGlobal(): BelongsTo
    {
        return $this->belongsTo(TokenGlobal::class, 'token_global_id');
    }

    /**
     * Relasi ke peserta
     */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }
}
