<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class OtpCode extends Model
{
    protected $fillable = [
        'user_id', 'code', 'purpose', 'is_used', 'expires_at',
    ];

    protected $casts = [
        'is_used'    => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cek apakah OTP masih valid (belum expired, belum dipakai)
     */
    public function isValid(): bool
    {
        return !$this->is_used && Carbon::now()->lt($this->expires_at);
    }
}
