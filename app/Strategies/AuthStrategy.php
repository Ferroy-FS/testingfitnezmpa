<?php

namespace App\Strategies;

use App\Models\User;
use App\Models\PersonalAccessToken;

/**
 * ══════════════════════════════════════════════════════════════
 *  STRATEGY — Behavioral Pattern #2
 * ══════════════════════════════════════════════════════════════
 *
 * Memungkinkan pemilihan algoritma autentikasi pada runtime.
 * Middleware bisa memilih strategi verifikasi yang berbeda:
 * - SessionStrategy: cek PHP session
 * - BearerTokenStrategy: cek Bearer token di header
 * - CookieTokenStrategy: cek token di cookie HttpOnly
 *
 * Manfaat maintenance:
 * - Tambah metode auth baru (misal API key) → tambah strategy baru
 * - Middleware tetap bersih, hanya loop strategies
 * - Bisa prioritaskan strategi tertentu tanpa ubah yang lain
 *
 * Urutan berurutan (poin a.5): Session → Bearer → Cookie
 * ══════════════════════════════════════════════════════════════
 */

// ── Strategy Interface ────────────────────────────────────────

interface AuthStrategyInterface
{
    /**
     * Coba autentikasi dari request
     * @return User|null User jika berhasil, null jika gagal
     */
    public function authenticate(mixed $request): ?User;

    /**
     * Nama strategi (untuk logging/debug)
     */
    public function getName(): string;
}

// ── Concrete Strategy: PHP Session ────────────────────────────

class SessionAuthStrategy implements AuthStrategyInterface
{
    public function getName(): string
    {
        return 'session';
    }

    public function authenticate(mixed $request): ?User
    {
        $userId = session('user_id');
        if (!$userId) return null;

        return User::with('role')
            ->where('id', $userId)
            ->where('is_active', true)
            ->first();
    }
}

// ── Concrete Strategy: Bearer Token ───────────────────────────

class BearerTokenAuthStrategy implements AuthStrategyInterface
{
    public function getName(): string
    {
        return 'bearer_token';
    }

    public function authenticate(mixed $request): ?User
    {
        $bearer = $request->bearerToken();
        if (!$bearer) return null;

        return $this->resolveUserFromToken($bearer);
    }

    private function resolveUserFromToken(string $plainToken): ?User
    {
        $hash = hash('sha256', $plainToken);

        $pat = PersonalAccessToken::where('token', $hash)
            ->where('tokenable_type', 'user')
            ->first();

        if (!$pat) return null;

        $pat->update(['last_used_at' => now()]);

        return User::with('role')
            ->where('id', $pat->tokenable_id)
            ->where('is_active', true)
            ->first();
    }
}

// ── Concrete Strategy: Cookie Token ───────────────────────────

class CookieTokenAuthStrategy implements AuthStrategyInterface
{
    public function getName(): string
    {
        return 'cookie_token';
    }

    public function authenticate(mixed $request): ?User
    {
        $cookieToken = $request->cookie('fitnez_token');
        if (!$cookieToken) return null;

        $hash = hash('sha256', $cookieToken);

        $pat = PersonalAccessToken::where('token', $hash)
            ->where('tokenable_type', 'user')
            ->first();

        if (!$pat) return null;

        $pat->update(['last_used_at' => now()]);

        return User::with('role')
            ->where('id', $pat->tokenable_id)
            ->where('is_active', true)
            ->first();
    }
}

// ── Context: AuthVerifier menggunakan strategies ──────────────

class AuthVerifier
{
    /** @var AuthStrategyInterface[] */
    private array $strategies;

    public function __construct(AuthStrategyInterface ...$strategies)
    {
        $this->strategies = $strategies;
    }

    /**
     * Coba semua strategi berurutan sampai salah satu berhasil
     * Implementasi poin a.5: penggunaan faktor identitas berurutan
     */
    public function verify(mixed $request): ?User
    {
        foreach ($this->strategies as $strategy) {
            $user = $strategy->authenticate($request);
            if ($user !== null) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Tambah strategi baru (Open/Closed Principle)
     */
    public function addStrategy(AuthStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }
}
