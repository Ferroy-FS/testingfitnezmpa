<?php

namespace App\Http\Controllers;

use App\Models\Authentication;
use App\Models\OtpCode;
use App\Models\PersonalAccessToken;
use App\Models\Role;
use App\Models\SystemLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ══════════════════════════════════════════════════════════════
 *  AUTH CONTROLLER — Implementasi Lengkap Otentikasi
 * ══════════════════════════════════════════════════════════════
 *
 * a. Faktor Identitas:
 *    1. Password (sha256 + salt) → "yang diketahui pengguna"
 *    2. OTP via HP (simulasi) → "yang dimiliki pengguna"
 *    3. (Placeholder) Biometric → "keunikan pengguna"
 *    4. Kombinasi: Password + OTP = Two-Factor Auth
 *    5. Berurutan: Login → Password dulu → OTP kemudian
 *
 * b. Transmisi Data:
 *    1. HTTPS (SSL/TLS) dikonfigurasi di Nginx
 *    2. Hashing SHA-256 + Salt untuk password
 *
 * c. Mekanisme Sesi:
 *    1. Session di database (non-sementara) via cookies
 *    2. Token SHA-256 disimpan client di localStorage/cookie
 *
 * d. Keamanan Client:
 *    1. Cookie HttpOnly & Secure → anti XSS
 *    2. Token di localStorage (untuk SPA)
 *
 * e. Halaman Publik & Private:
 *    1. Landing page = publik (profil, fitur, produk)
 *    2. Consent cookies sebelum tracking
 *    3. Register → Login → akses aplikasi
 *    4. Kontrol data publik vs privasi
 * ══════════════════════════════════════════════════════════════
 */
class AuthController
{
    /**
     * POST /api/auth/register
     * Selalu register sebagai member
     */
    public function register(Request $request): JsonResponse
    {
        $name     = trim($request->input('name', ''));
        $email    = trim($request->input('email', ''));
        $password = $request->input('password', '');

        if (!$name || !$email || !$password) {
            return response()->json(['error' => 'Semua field wajib diisi.'], 400);
        }
        if (strlen($password) < 6) {
            return response()->json(['error' => 'Password minimal 6 karakter.'], 400);
        }

        // Cek email duplikat
        if (User::where('email', $email)->exists()) {
            return response()->json(['error' => 'Email sudah terdaftar.'], 409);
        }

        $role = Role::where('name', 'member')->first();
        if (!$role) {
            return response()->json(['error' => 'Role member tidak ditemukan.'], 500);
        }

        // ── Hashing + Salting (Faktor Identitas #1) ──
        $salt = bin2hex(random_bytes(16));
        $hash = hash('sha256', $salt . $password);

        // Insert user
        $user = User::create([
            'email'         => $email,
            'password_hash' => $hash,
            'full_name'     => $name,
            'role_id'       => $role->id,
            'bio'           => '',
            'is_active'     => true,
        ]);

        // Insert authentication record (salt disimpan terpisah)
        Authentication::create([
            'user_id'       => $user->id,
            'email'         => $email,
            'password_hash' => $hash,
            'salt'          => $salt,
            'provider'      => 'local',
            'is_active'     => true,
        ]);

        return response()->json(['message' => 'Registrasi berhasil.'], 201);
    }

    /**
     * POST /api/auth/login
     * Step 1: Verifikasi password → kirim OTP
     * Faktor identitas berurutan: password dulu, OTP kemudian
     */
    public function login(Request $request): JsonResponse
    {
        $email    = trim($request->input('email', ''));
        $password = $request->input('password', '');

        if (!$email || !$password) {
            return response()->json(['error' => 'Email dan password wajib diisi.'], 400);
        }

        // ── Cari user + authentication data ──
        $user = User::with(['role', 'authentication'])->where('email', $email)->first();

        if (!$user || !$user->authentication) {
            return response()->json(['error' => 'Email atau password salah.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['error' => 'Akun tidak aktif.'], 403);
        }

        // ── Verifikasi Password (Faktor #1: yang diketahui) ──
        $auth = $user->authentication;
        $hash = hash('sha256', $auth->salt . $password);

        if (!hash_equals($auth->password_hash, $hash)) {
            // Increment failed attempts
            $auth->increment('failed_login_attempts');
            return response()->json(['error' => 'Email atau password salah.'], 401);
        }

        // Password benar → Generate OTP (Faktor #2: yang dimiliki)
        // ── OTP dikirim ke HP pengguna (simulasi) ──
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiryMinutes = (int) env('OTP_EXPIRY_MINUTES', 5);

        OtpCode::create([
            'user_id'    => $user->id,
            'code'       => $otpCode,
            'purpose'    => 'login',
            'is_used'    => false,
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
        ]);

        // ── Buat token sementara (belum OTP verified) ──
        $plainToken  = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        PersonalAccessToken::create([
            'tokenable_type' => 'user',
            'tokenable_id'   => $user->id,
            'name'           => 'fitnez-pre-otp',
            'token'          => $hashedToken,
            'abilities'      => [$user->role->name], // belum ada otp_verified
            'expires_at'     => Carbon::now()->addMinutes($expiryMinutes + 2),
        ]);

        // ── Set Session (sesi berbasis database) ──
        session(['user_id' => $user->id, 'user_role' => $user->role->name]);

        // ── Set Cookie HttpOnly (keamanan client, anti XSS) ──
        $cookie = cookie('fitnez_token', $plainToken, 120, '/', null, false, true, false, 'Lax');

        return response()->json([
            'message'     => 'Password benar. Silakan verifikasi OTP.',
            'otp_pending' => true,
            'token'       => $plainToken,
            'user'        => $user->toApiResponse(),
            // ── Simulasi: OTP ditampilkan (di produksi, kirim via SMS) ──
            'otp_code_demo' => $otpCode,
        ])->cookie($cookie);
    }

    /**
     * POST /api/auth/verify-otp
     * Step 2: Verifikasi OTP → akses penuh
     * Faktor identitas kedua: "barang yang dimiliki" (HP → OTP)
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $otpInput = trim($request->input('otp', ''));
        $token    = $request->bearerToken() ?? $request->cookie('fitnez_token');

        if (!$otpInput || !$token) {
            return response()->json(['error' => 'OTP dan token wajib diisi.'], 400);
        }

        // Cari user dari token
        $hashedToken = hash('sha256', $token);
        $pat = PersonalAccessToken::where('token', $hashedToken)
            ->where('tokenable_type', 'user')
            ->first();

        if (!$pat) {
            return response()->json(['error' => 'Token tidak valid.'], 401);
        }

        $user = User::with('role')->find($pat->tokenable_id);
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan.'], 401);
        }

        // ── Verifikasi OTP (Faktor #2) ──
        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $otpInput)
            ->where('purpose', 'login')
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('id', 'desc')
            ->first();

        if (!$otp) {
            return response()->json(['error' => 'OTP tidak valid atau sudah kadaluarsa.'], 401);
        }

        // Tandai OTP sebagai sudah dipakai
        $otp->update(['is_used' => true]);

        // ── Upgrade token: tambahkan otp_verified ke abilities ──
        $abilities = $pat->abilities ?? [];
        $abilities[] = 'otp_verified';
        $pat->update([
            'name'       => 'fitnez-session',
            'abilities'  => array_unique($abilities),
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        // ── Update session ──
        session(['otp_verified' => true]);

        // ── Log login ──
        $user->update(['last_login' => now()]);
        $user->authentication?->update(['last_login' => now(), 'failed_login_attempts' => 0]);

        SystemLog::create([
            'user_id'        => $user->id,
            'action_type'    => 'LOGIN',
            'table_affected' => 'users',
            'record_id'      => $user->id,
            'description'    => $user->full_name . ' logged in (2FA verified)',
        ]);

        return response()->json([
            'message' => 'Login berhasil. OTP terverifikasi.',
            'token'   => $token,
            'user'    => $user->toApiResponse(),
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->current_user;

        // Log logout
        SystemLog::create([
            'user_id'        => $user->id,
            'action_type'    => 'LOGOUT',
            'table_affected' => 'users',
            'record_id'      => $user->id,
            'description'    => $user->full_name . ' logged out',
        ]);

        // Hapus token
        $bearer = $request->bearerToken();
        if ($bearer) {
            $hash = hash('sha256', $bearer);
            PersonalAccessToken::where('token', $hash)->delete();
        }

        // Hapus session
        session()->flush();

        // Hapus cookie
        $cookie = cookie()->forget('fitnez_token');

        return response()->json(['message' => 'Logout berhasil.'])->cookie($cookie);
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->current_user;
        return response()->json(['user' => $user->toApiResponse()]);
    }
}
