<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * ══════════════════════════════════════════════════════════════
 *  CHAIN OF RESPONSIBILITY — Behavioral Pattern #3
 * ══════════════════════════════════════════════════════════════
 *
 * Setiap handler dalam chain memproses request atau meneruskannya.
 * Validasi auth dilakukan bertahap:
 * 1. CorsHandler → set CORS headers
 * 2. AuthenticationHandler → verifikasi identitas user
 * 3. OtpVerificationHandler → cek apakah OTP sudah diverifikasi
 * 4. RoleAuthorizationHandler → cek apakah role punya akses
 *
 * Manfaat maintenance:
 * - Tambah validasi baru → tambah handler, sisipkan di chain
 * - Hapus validasi → cabut handler dari chain
 * - Setiap handler independen, bisa ditest sendiri
 * - Urutan bisa diatur tanpa ubah logika handler
 * ══════════════════════════════════════════════════════════════
 */

// ── Abstract Handler ──────────────────────────────────────────

abstract class AuthHandler
{
    private ?AuthHandler $nextHandler = null;

    /**
     * Set handler berikutnya dalam chain
     * Return next handler untuk method chaining
     */
    public function setNext(AuthHandler $handler): AuthHandler
    {
        $this->nextHandler = $handler;
        return $handler;
    }

    /**
     * Proses request, atau teruskan ke handler berikutnya
     */
    public function handle(Request $request, array $context = []): AuthResult
    {
        // Proses di handler ini
        $result = $this->process($request, $context);

        // Jika gagal, berhenti di sini
        if (!$result->passed) {
            return $result;
        }

        // Teruskan ke handler berikutnya (jika ada)
        if ($this->nextHandler !== null) {
            return $this->nextHandler->handle($request, $result->context);
        }

        // Tidak ada handler lagi → semua passed
        return $result;
    }

    /**
     * Logika spesifik per handler
     */
    abstract protected function process(Request $request, array $context): AuthResult;
}

// ── Result Object ─────────────────────────────────────────────

class AuthResult
{
    public function __construct(
        public readonly bool $passed,
        public readonly array $context = [],
        public readonly ?string $error = null,
        public readonly int $httpCode = 200
    ) {}

    public static function pass(array $context = []): self
    {
        return new self(true, $context);
    }

    public static function fail(string $error, int $code = 401): self
    {
        return new self(false, [], $error, $code);
    }
}

// ── Concrete Handler: Authentication ──────────────────────────

class AuthenticationHandler extends AuthHandler
{
    protected function process(Request $request, array $context): AuthResult
    {
        // Cek session
        $userId = session('user_id');
        if ($userId) {
            $user = User::with('role')->where('id', $userId)->where('is_active', true)->first();
            if ($user) {
                return AuthResult::pass(array_merge($context, ['user' => $user, 'auth_method' => 'session']));
            }
        }

        // Cek bearer token
        $bearer = $request->bearerToken();
        if ($bearer) {
            $user = $this->resolveToken($bearer);
            if ($user) {
                return AuthResult::pass(array_merge($context, ['user' => $user, 'auth_method' => 'bearer']));
            }
        }

        // Cek cookie
        $cookie = $request->cookie('fitnez_token');
        if ($cookie) {
            $user = $this->resolveToken($cookie);
            if ($user) {
                return AuthResult::pass(array_merge($context, ['user' => $user, 'auth_method' => 'cookie']));
            }
        }

        return AuthResult::fail('Unauthorized', 401);
    }

    private function resolveToken(string $plain): ?User
    {
        $hash = hash('sha256', $plain);
        $pat = \App\Models\PersonalAccessToken::where('token', $hash)->where('tokenable_type', 'user')->first();
        if (!$pat) return null;
        $pat->update(['last_used_at' => now()]);
        return User::with('role')->where('id', $pat->tokenable_id)->where('is_active', true)->first();
    }
}

// ── Concrete Handler: OTP Verification ────────────────────────

class OtpVerificationHandler extends AuthHandler
{
    protected function process(Request $request, array $context): AuthResult
    {
        $otpVerified = session('otp_verified', false);

        if (!$otpVerified) {
            // Cek dari token abilities
            $bearer = $request->bearerToken() ?? $request->cookie('fitnez_token');
            if ($bearer) {
                $hash = hash('sha256', $bearer);
                $pat = \App\Models\PersonalAccessToken::where('token', $hash)->first();
                if ($pat && in_array('otp_verified', $pat->abilities ?? [])) {
                    $otpVerified = true;
                }
            }
        }

        if (!$otpVerified) {
            return AuthResult::fail('OTP verification required', 403);
        }

        return AuthResult::pass(array_merge($context, ['otp_verified' => true]));
    }
}

// ── Concrete Handler: Role Authorization ──────────────────────

class RoleAuthorizationHandler extends AuthHandler
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles = [])
    {
        $this->allowedRoles = $allowedRoles;
    }

    protected function process(Request $request, array $context): AuthResult
    {
        if (empty($this->allowedRoles)) {
            return AuthResult::pass($context); // Tidak ada pembatasan role
        }

        $user = $context['user'] ?? null;
        if (!$user || !in_array($user->role->name, $this->allowedRoles)) {
            return AuthResult::fail('Akses ditolak.', 403);
        }

        return AuthResult::pass($context);
    }
}

// ── Factory method untuk membangun chain ──────────────────────

class AuthChainBuilder
{
    /**
     * Bangun chain standar: Auth → OTP → (opsional Role)
     */
    public static function buildStandardChain(array $requiredRoles = []): AuthHandler
    {
        $authHandler = new AuthenticationHandler();
        $otpHandler  = new OtpVerificationHandler();

        $authHandler->setNext($otpHandler);

        if (!empty($requiredRoles)) {
            $roleHandler = new RoleAuthorizationHandler($requiredRoles);
            $otpHandler->setNext($roleHandler);
        }

        return $authHandler;
    }

    /**
     * Bangun chain tanpa OTP (untuk endpoint tertentu)
     */
    public static function buildAuthOnlyChain(): AuthHandler
    {
        return new AuthenticationHandler();
    }
}
