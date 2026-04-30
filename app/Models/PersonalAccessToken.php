<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalAccessToken extends Model
{
    protected $fillable = [
        'tokenable_type', 'tokenable_id', 'name', 'token',
        'abilities', 'last_used_at', 'expires_at',
    ];

    protected $hidden = ['token'];

    protected $casts = [
        'abilities'    => 'array',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tokenable_id');
    }
}
