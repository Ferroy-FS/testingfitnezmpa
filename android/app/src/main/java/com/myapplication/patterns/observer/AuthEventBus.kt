package com.myapplication.patterns.observer

import android.util.Log

/**
 * ══════════════════════════════════════════════════════════════
 *  OBSERVER — Behavioral Pattern #1 (Android)
 * ══════════════════════════════════════════════════════════════
 *
 * TIDAK menggunakan Singleton — EventBus diinstansiasi
 * dan di-pass ke komponen yang butuh (dependency injection manual).
 *
 * Observers mendapatkan notifikasi saat event auth terjadi.
 * ══════════════════════════════════════════════════════════════
 */

// ── Event Types ───────────────────────────────────────────────

sealed class AuthEvent {
    data class LoginSuccess(val userName: String, val role: String) : AuthEvent()
    data class LoginFailed(val email: String, val reason: String) : AuthEvent()
    data class OtpVerified(val userName: String) : AuthEvent()
    data class BiometricSuccess(val userName: String) : AuthEvent()
    data class BiometricFailed(val reason: String) : AuthEvent()
    data class Logout(val userName: String) : AuthEvent()
}

// ── Observer Interface ────────────────────────────────────────

interface AuthEventObserver {
    fun onAuthEvent(event: AuthEvent)
}

// ── Subject (Event Bus — NOT Singleton) ───────────────────────

class AuthEventBus {
    private val observers = mutableListOf<AuthEventObserver>()

    fun subscribe(observer: AuthEventObserver) {
        if (observer !in observers) observers.add(observer)
    }

    fun unsubscribe(observer: AuthEventObserver) {
        observers.remove(observer)
    }

    fun emit(event: AuthEvent) {
        observers.forEach { it.onAuthEvent(event) }
    }

    fun clear() {
        observers.clear()
    }
}

// ── Concrete Observer: Logger ─────────────────────────────────

class AuthLoggerObserver : AuthEventObserver {
    override fun onAuthEvent(event: AuthEvent) {
        val tag = "AuthLogger"
        when (event) {
            is AuthEvent.LoginSuccess    -> Log.i(tag, "✅ Login: ${event.userName} (${event.role})")
            is AuthEvent.LoginFailed     -> Log.w(tag, "❌ Login failed: ${event.email} — ${event.reason}")
            is AuthEvent.OtpVerified     -> Log.i(tag, "🔑 OTP verified: ${event.userName}")
            is AuthEvent.BiometricSuccess -> Log.i(tag, "🔒 Biometric OK: ${event.userName}")
            is AuthEvent.BiometricFailed -> Log.w(tag, "🔒 Biometric failed: ${event.reason}")
            is AuthEvent.Logout          -> Log.i(tag, "↩ Logout: ${event.userName}")
        }
    }
}

// ── Concrete Observer: UI Toast ───────────────────────────────

class ToastNotificationObserver(
    private val showToast: (String) -> Unit  // Lambda untuk tampilkan toast
) : AuthEventObserver {
    override fun onAuthEvent(event: AuthEvent) {
        val msg = when (event) {
            is AuthEvent.LoginSuccess    -> "Selamat datang, ${event.userName}!"
            is AuthEvent.OtpVerified     -> "OTP terverifikasi ✓"
            is AuthEvent.BiometricSuccess -> "Biometric terverifikasi ✓"
            is AuthEvent.BiometricFailed -> "Biometric gagal: ${event.reason}"
            is AuthEvent.Logout          -> "Logout berhasil"
            else -> null
        }
        msg?.let { showToast(it) }
    }
}
