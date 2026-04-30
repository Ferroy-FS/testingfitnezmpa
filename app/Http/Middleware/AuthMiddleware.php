<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware Autentikasi — Faktor Identitas Berurutan
 *
 * Urutan verifikasi:
 * 1. Session (server-side, disimpan di database)
 * 2. Bearer Token (SHA-256 hashed, disimpan di server)
 * 3. Cookie HttpOnly (fitnez_token)
 *
 * Kombinasi: Session + Token + Cookie = triple-layer auth
 */
class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = null;

        // ── Cek 1: Session (server menyimpan sesi di database) ──
        $userId = session('user_id');
        if ($userId) {
            $user = User::with('role')->where('id', $userId)->where('is_active', true)->first();
        }

        // ── Cek 2: Bearer Token (token terenkripsi SHA-256) ──
        if (!$user) {
            $bearer = $request->bearerToken();
            if ($bearer) {
                $user = $this->getUserByToken($bearer);
            }
        }

        // ── Cek 3: Cookie HttpOnly (mengurangi XSS) ──
        if (!$user) {
            $cookieToken = $request->cookie('fitnez_token');
            if ($cookieToken) {
                $user = $this->getUserByToken($cookieToken);
            }
        }

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // ── Cek apakah OTP sudah diverifikasi (2FA wajib) ──
        $otpVerified = session('otp_verified', false);
        $bearerOtp   = $request->header('X-OTP-Verified');

        if (!$otpVerified && !$bearerOtp) {
            // Cek dari token abilities apakah sudah otp_verified
            $bearer = $request->bearerToken() ?? $request->cookie('fitnez_token');
            if ($bearer) {
                $hash = hash('sha256', $bearer);
                $pat  = PersonalAccessToken::where('token', $hash)->first();
                if ($pat && in_array('otp_verified', $pat->abilities ?? [])) {
                    $otpVerified = true;
                }
            }
        }

        if (!$otpVerified) {
            return response()->json([
                'error'       => 'OTP verification required',
                'otp_pending' => true,
            ], 403);
        }

        // Simpan user di request untuk controller
        $request->merge(['current_user' => $user]);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    /**
     * Cari user berdasarkan plain token → hash SHA-256 → cocokkan di DB
     */
    private function getUserByToken(string $plainToken): ?User
    {
        $hash = hash('sha256', $plainToken);

        $pat = PersonalAccessToken::where('token', $hash)
            ->where('tokenable_type', 'user')
            ->first();

        if (!$pat) return null;

        // Update last_used_at
        $pat->update(['last_used_at' => now()]);

        return User::with('role')
            ->where('id', $pat->tokenable_id)
            ->where('is_active', true)
            ->first();
    }
}
