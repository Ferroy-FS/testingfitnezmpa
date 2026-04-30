<?php

namespace App\Services\Auth;

use App\Factories\AuthFactoryInterface;
use App\Factories\LocalAuthFactory;
use App\Models\Authentication;
use App\Models\OtpCode;
use App\Models\PersonalAccessToken;
use App\Models\SystemLog;
use App\Models\User;
use Carbon\Carbon;

/**
 * ══════════════════════════════════════════════════════════════
 *  FACADE — Structural Pattern (khusus Auth)
 * ══════════════════════════════════════════════════════════════
 *
 * Menyederhanakan subsistem autentikasi yang kompleks:
 * - Password hashing/verification (via Abstract Factory)
 * - Token generation & management
 * - OTP generation & verification
 * - Session management
 * - System logging
 *
 * Controller cukup memanggil satu method sederhana,
 * Facade mengkoordinasikan semua subsistem di belakang layar.
 *
 * Sebelum Facade:
 *   $salt = bin2hex(random_bytes(16));
 *   $hash = hash('sha256', $salt . $password);
 *   $token = bin2hex(random_bytes(32));
 *   $hashedToken = hash('sha256', $token);
 *   // ... 30 baris kode lagi
 *
 * Setelah Facade:
 *   $result = $authFacade->login($email, $password);
 * ══════════════════════════════════════════════════════════════
 */
class AuthFacade
{
    private AuthFactoryInterface $factory;

    public function __construct(?AuthFactoryInterface $factory = null)
    {
        // Default ke local auth factory
        $this->factory = $factory ?? new LocalAuthFactory();
    }

    /**
     * Register user baru
     * Facade mengkoordinasikan: hashing, user creation, auth record
     */
    public function register(string $name, string $email, string $password, string $roleName = 'member'): array
    {
        // Validasi
        if (!$name || !$email || !$password) {
            return ['success' => false, 'error' => 'Semua field wajib diisi.', 'code' => 400];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password minimal 6 karakter.', 'code' => 400];
        }
        if (User::where('email', $email)->exists()) {
            return ['success' => false, 'error' => 'Email sudah terdaftar.', 'code' => 409];
        }

        $role = \App\Models\Role::where('name', $roleName)->first();
        if (!$role) {
            return ['success' => false, 'error' => 'Role tidak valid.', 'code' => 400];
        }

        // Gunakan factory untuk membuat hasher
        $hasher = $this->factory->createPasswordHasher();
        $salt = $hasher->generateSalt();
        $hash = $hasher->hash($password, $salt);

        $user = User::create([
            'email' => $email, 'password_hash' => $hash,
            'full_name' => $name, 'role_id' => $role->id,
            'bio' => '', 'is_active' => true,
        ]);

        Authentication::create([
            'user_id' => $user->id, 'email' => $email,
            'password_hash' => $hash, 'salt' => $salt,
            'provider' => 'local', 'is_active' => true,
        ]);

        return ['success' => true, 'message' => 'Registrasi berhasil.'];
    }

    /**
     * Login step 1: verifikasi password → generate OTP + token
     */
    public function login(string $email, string $password): array
    {
        if (!$email || !$password) {
            return ['success' => false, 'error' => 'Email dan password wajib diisi.', 'code' => 400];
        }

        $user = User::with(['role', 'authentication'])->where('email', $email)->first();
        if (!$user || !$user->authentication) {
            return ['success' => false, 'error' => 'Email atau password salah.', 'code' => 401];
        }
        if (!$user->is_active) {
            return ['success' => false, 'error' => 'Akun tidak aktif.', 'code' => 403];
        }

        // Verifikasi password via factory hasher
        $hasher = $this->factory->createPasswordHasher();
        $auth = $user->authentication;
        if (!$hasher->verify($password, $auth->salt, $auth->password_hash)) {
            $auth->increment('failed_login_attempts');
            return ['success' => false, 'error' => 'Email atau password salah.', 'code' => 401];
        }

        // Generate OTP via factory
        $otpGen = $this->factory->createOtpGenerator();
        $otpCode = '';
        if ($otpGen->isEnabled()) {
            $otpCode = $otpGen->generate();
            OtpCode::create([
                'user_id' => $user->id, 'code' => $otpCode,
                'purpose' => 'login', 'is_used' => false,
                'expires_at' => Carbon::now()->addMinutes($otpGen->getExpiryMinutes()),
            ]);
        }

        // Generate token via factory
        $tokenGen = $this->factory->createTokenGenerator();
        $plainToken = $tokenGen->generate();
        $hashedToken = $tokenGen->hashForStorage($plainToken);

        PersonalAccessToken::create([
            'tokenable_type' => 'user', 'tokenable_id' => $user->id,
            'name' => 'fitnez-pre-otp', 'token' => $hashedToken,
            'abilities' => [$user->role->name],
            'expires_at' => Carbon::now()->addMinutes($otpGen->getExpiryMinutes() + 2),
        ]);

        session(['user_id' => $user->id, 'user_role' => $user->role->name]);

        return [
            'success' => true,
            'message' => 'Password benar. Silakan verifikasi OTP.',
            'otp_pending' => true,
            'token' => $plainToken,
            'user' => $user->toApiResponse(),
            'otp_code_demo' => $otpCode,
        ];
    }

    /**
     * Login step 2: verifikasi OTP → upgrade token
     */
    public function verifyOtp(string $otpInput, string $token): array
    {
        if (!$otpInput || !$token) {
            return ['success' => false, 'error' => 'OTP dan token wajib diisi.', 'code' => 400];
        }

        $tokenGen = $this->factory->createTokenGenerator();
        $hashedToken = $tokenGen->hashForStorage($token);
        $pat = PersonalAccessToken::where('token', $hashedToken)->where('tokenable_type', 'user')->first();

        if (!$pat) {
            return ['success' => false, 'error' => 'Token tidak valid.', 'code' => 401];
        }

        $user = User::with('role')->find($pat->tokenable_id);
        if (!$user) {
            return ['success' => false, 'error' => 'User tidak ditemukan.', 'code' => 401];
        }

        // Verifikasi OTP
        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $otpInput)->where('purpose', 'login')
            ->where('is_used', false)->where('expires_at', '>', Carbon::now())
            ->orderBy('id', 'desc')->first();

        if (!$otp) {
            return ['success' => false, 'error' => 'OTP tidak valid atau sudah kadaluarsa.', 'code' => 401];
        }

        $otp->update(['is_used' => true]);

        // Upgrade token
        $abilities = $pat->abilities ?? [];
        $abilities[] = 'otp_verified';
        $pat->update([
            'name' => 'fitnez-session',
            'abilities' => array_unique($abilities),
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        session(['otp_verified' => true]);
        $user->update(['last_login' => now()]);
        $user->authentication?->update(['last_login' => now(), 'failed_login_attempts' => 0]);

        // Log (Observer pattern akan menangani ini juga)
        SystemLog::create([
            'user_id' => $user->id, 'action_type' => 'LOGIN',
            'table_affected' => 'users', 'record_id' => $user->id,
            'description' => $user->full_name . ' logged in (2FA verified)',
        ]);

        return [
            'success' => true,
            'message' => 'Login berhasil. OTP terverifikasi.',
            'token' => $token,
            'user' => $user->toApiResponse(),
        ];
    }

    /**
     * Logout — invalidate token + session + log
     */
    public function logout(User $user, ?string $bearerToken = null): array
    {
        SystemLog::create([
            'user_id' => $user->id, 'action_type' => 'LOGOUT',
            'table_affected' => 'users', 'record_id' => $user->id,
            'description' => $user->full_name . ' logged out',
        ]);

        if ($bearerToken) {
            $tokenGen = $this->factory->createTokenGenerator();
            $hash = $tokenGen->hashForStorage($bearerToken);
            PersonalAccessToken::where('token', $hash)->delete();
        }

        session()->flush();

        return ['success' => true, 'message' => 'Logout berhasil.'];
    }

    /**
     * Resolve user dari token (untuk middleware)
     */
    public function resolveUserFromToken(string $plainToken): ?User
    {
        $tokenGen = $this->factory->createTokenGenerator();
        $hash = $tokenGen->hashForStorage($plainToken);

        $pat = PersonalAccessToken::where('token', $hash)->where('tokenable_type', 'user')->first();
        if (!$pat) return null;

        $pat->update(['last_used_at' => now()]);

        return User::with('role')->where('id', $pat->tokenable_id)->where('is_active', true)->first();
    }
}
