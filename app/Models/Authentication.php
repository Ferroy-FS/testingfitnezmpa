<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Authentication extends Model
{
    protected $fillable = [
        'user_id', 'email', 'password_hash', 'salt',
        'provider', 'is_active', 'failed_login_attempts', 'last_login',
    ];

    protected $hidden = ['password_hash', 'salt'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
