<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    protected $fillable = [
        'email', 'password_hash', 'full_name', 'role_id',
        'phone', 'bio', 'has_trainer_cert', 'trainer_cert_file',
        'is_active', 'last_login',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'has_trainer_cert' => 'boolean',
        'is_active'        => 'boolean',
        'last_login'       => 'datetime',
    ];

    // ── Relasi ──

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function authentication(): HasOne
    {
        return $this->hasOne(Authentication::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(PersonalAccessToken::class, 'tokenable_id')
                    ->where('tokenable_type', 'user');
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'owner_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class);
    }

    // ── Helper ──

    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role->name, $roles);
    }

    /**
     * Format user data untuk API response
     */
    public function toApiResponse(): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->full_name,
            'email'            => $this->email,
            'role'             => $this->role->name,
            'phone'            => $this->phone,
            'bio'              => $this->bio ?? '',
            'has_trainer_cert' => $this->has_trainer_cert,
            'joined'           => $this->created_at?->format('d M Y') ?? '-',
        ];
    }
}
