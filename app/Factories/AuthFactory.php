<?php

namespace App\Factories;

use App\Contracts\AuthFactoryInterface;
use App\Contracts\PasswordHasherInterface;
use App\Contracts\TokenGeneratorInterface;
use App\Contracts\OtpGeneratorInterface;

/**
 * ══════════════════════════════════════════════════════════════
 *  ABSTRACT FACTORY — Creational Pattern
 * ══════════════════════════════════════════════════════════════
 */

// ── INTERFACE (Abstract Factory)

interface AuthFactoryInterface
{
    public function createPasswordHasher(): PasswordHasherInterface;
    public function createTokenGenerator(): TokenGeneratorInterface;
    public function createOtpGenerator(): OtpGeneratorInterface;
}

// ── CONCRETE FACTORY: Local Auth 

class LocalAuthFactory implements AuthFactoryInterface
{
    public function createPasswordHasher(): PasswordHasherInterface
    {
        return new Sha256SaltHasher();
    }

    public function createTokenGenerator(): TokenGeneratorInterface
    {
        return new SecureTokenGenerator();
    }

    public function createOtpGenerator(): OtpGeneratorInterface
    {
        return new NumericOtpGenerator(6, 5); // 6 digit, 5 menit
    }
}

// ── CONCRETE FACTORY: Google OAuth

class GoogleAuthFactory implements AuthFactoryInterface
{
    public function createPasswordHasher(): PasswordHasherInterface
    {
        return new NullHasher(); // OAuth tidak pakai password
    }

    public function createTokenGenerator(): TokenGeneratorInterface
    {
        return new SecureTokenGenerator();
    }

    public function createOtpGenerator(): OtpGeneratorInterface
    {
        return new NullOtpGenerator(); // OAuth tidak pakai OTP
    }
}

// ── PRODUCT INTERFACES

interface PasswordHasherInterface
{
    public function generateSalt(): string;
    public function hash(string $password, string $salt): string;
    public function verify(string $password, string $salt, string $storedHash): bool;
}

interface TokenGeneratorInterface
{
    public function generate(int $length = 64): string;
    public function hashForStorage(string $plainToken): string;
}

interface OtpGeneratorInterface
{
    public function generate(): string;
    public function getExpiryMinutes(): int;
    public function isEnabled(): bool;
}

// ── CONCRETE PRODUCTS 

class Sha256SaltHasher implements PasswordHasherInterface
{
    public function generateSalt(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function hash(string $password, string $salt): string
    {
        return hash('sha256', $salt . $password);
    }

    public function verify(string $password, string $salt, string $storedHash): bool
    {
        return hash_equals($storedHash, $this->hash($password, $salt));
    }
}

class SecureTokenGenerator implements TokenGeneratorInterface
{
    public function generate(int $length = 64): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    public function hashForStorage(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}

class NumericOtpGenerator implements OtpGeneratorInterface
{
    public function __construct(
        private int $digits = 6,
        private int $expiryMinutes = 5
    ) {}

    public function generate(): string
    {
        $max = pow(10, $this->digits) - 1;
        return str_pad((string) random_int(0, $max), $this->digits, '0', STR_PAD_LEFT);
    }

    public function getExpiryMinutes(): int
    {
        return $this->expiryMinutes;
    }

    public function isEnabled(): bool
    {
        return true;
    }
}

class NullHasher implements PasswordHasherInterface
{
    public function generateSalt(): string { return ''; }
    public function hash(string $password, string $salt): string { return ''; }
    public function verify(string $password, string $salt, string $storedHash): bool { return false; }
}

class NullOtpGenerator implements OtpGeneratorInterface
{
    public function generate(): string { return ''; }
    public function getExpiryMinutes(): int { return 0; }
    public function isEnabled(): bool { return false; }
}
