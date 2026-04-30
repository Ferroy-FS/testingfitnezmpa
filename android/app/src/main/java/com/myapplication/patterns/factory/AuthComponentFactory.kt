package com.myapplication.patterns.factory

import java.security.MessageDigest
import java.security.SecureRandom

/**
 * ══════════════════════════════════════════════════════════════
 *  ABSTRACT FACTORY — Creational Pattern (Android)
 * ══════════════════════════════════════════════════════════════
 *
 * Sama seperti di web: memproduksi keluarga objek auth
 * tanpa menentukan kelas konkret.
 *
 * Di Android TIDAK menggunakan Singleton —
 * factory diinstansiasi per Activity yang membutuhkan.
 * ══════════════════════════════════════════════════════════════
 */

// ── Product Interfaces ────────────────────────────────────────

interface PasswordHasher {
    fun generateSalt(): String
    fun hash(password: String, salt: String): String
    fun verify(password: String, salt: String, storedHash: String): Boolean
}

interface TokenManager {
    fun getToken(): String?
    fun setToken(token: String?)
    fun clearToken()
}

interface OtpHandler {
    fun isEnabled(): Boolean
    fun getExpiryMinutes(): Int
}

// ── Abstract Factory Interface ────────────────────────────────

interface AuthComponentFactory {
    fun createPasswordHasher(): PasswordHasher
    fun createTokenManager(context: android.content.Context): TokenManager
    fun createOtpHandler(): OtpHandler
}

// ── Concrete Factory: Local Auth ──────────────────────────────

class LocalAuthComponentFactory : AuthComponentFactory {
    override fun createPasswordHasher(): PasswordHasher = Sha256SaltHasher()
    override fun createTokenManager(context: android.content.Context): TokenManager =
        EncryptedTokenManager(context)
    override fun createOtpHandler(): OtpHandler = NumericOtpHandler()
}

// ── Concrete Products ─────────────────────────────────────────

class Sha256SaltHasher : PasswordHasher {
    override fun generateSalt(): String {
        val bytes = ByteArray(16)
        SecureRandom().nextBytes(bytes)
        return bytes.joinToString("") { "%02x".format(it) }
    }

    override fun hash(password: String, salt: String): String {
        val digest = MessageDigest.getInstance("SHA-256")
        return digest.digest((salt + password).toByteArray())
            .joinToString("") { "%02x".format(it) }
    }

    override fun verify(password: String, salt: String, storedHash: String): Boolean {
        return hash(password, salt) == storedHash
    }
}

class EncryptedTokenManager(private val context: android.content.Context) : TokenManager {
    // Delegate ke SessionManager (yang sudah pakai EncryptedSharedPreferences)
    override fun getToken(): String? = com.myapplication.auth.SessionManager.getToken(context)
    override fun setToken(token: String?) {
        if (token != null) com.myapplication.auth.SessionManager.saveToken(context, token)
        else com.myapplication.auth.SessionManager.clear(context)
    }
    override fun clearToken() = com.myapplication.auth.SessionManager.clear(context)
}

class NumericOtpHandler : OtpHandler {
    override fun isEnabled(): Boolean = true
    override fun getExpiryMinutes(): Int = 5
}
