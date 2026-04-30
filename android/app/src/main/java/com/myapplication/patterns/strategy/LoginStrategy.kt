package com.myapplication.patterns.strategy

import com.myapplication.api.ApiClient
import com.myapplication.api.ApiResponse
import com.myapplication.auth.BiometricHelper
import com.myapplication.auth.SessionManager
import org.json.JSONObject

/**
 * ══════════════════════════════════════════════════════════════
 *  STRATEGY — Behavioral Pattern #2 (Android)
 * ══════════════════════════════════════════════════════════════
 *
 * Strategi login berbeda yang bisa dipilih:
 * - PasswordLoginStrategy: email + password → API call
 * - BiometricLoginStrategy: token + biometric → quick login
 * - TokenRefreshStrategy: cek token masih valid → auto-login
 *
 * Activity memilih strategi berdasarkan kondisi user.
 * ══════════════════════════════════════════════════════════════
 */

// ── Strategy Interface ────────────────────────────────────────

interface LoginStrategy {
    fun getName(): String
    fun canAttempt(context: android.content.Context): Boolean
    fun execute(context: android.content.Context, params: Map<String, String>, callback: (LoginStrategyResult) -> Unit)
}

data class LoginStrategyResult(
    val success: Boolean,
    val token: String? = null,
    val userJson: JSONObject? = null,
    val otpPending: Boolean = false,
    val otpCodeDemo: String? = null,
    val error: String? = null,
    val needsBiometric: Boolean = false
)

// ── Concrete Strategy: Password Login ─────────────────────────

class PasswordLoginStrategy : LoginStrategy {
    override fun getName() = "password"

    override fun canAttempt(context: android.content.Context): Boolean = true

    override fun execute(context: android.content.Context, params: Map<String, String>, callback: (LoginStrategyResult) -> Unit) {
        val email = params["email"] ?: ""
        val password = params["password"] ?: ""

        if (email.isEmpty() || password.isEmpty()) {
            callback(LoginStrategyResult(false, error = "Email dan password wajib diisi"))
            return
        }

        val body = JSONObject().put("email", email).put("password", password)

        // Jalankan di background thread
        Thread {
            val res = ApiClient.post("/auth/login", body)
            val result = if (res.ok) {
                LoginStrategyResult(
                    success = true,
                    token = res.data.optString("token"),
                    userJson = res.data.optJSONObject("user"),
                    otpPending = res.data.optBoolean("otp_pending", false),
                    otpCodeDemo = res.data.optString("otp_code_demo")
                )
            } else {
                LoginStrategyResult(false, error = res.error())
            }
            android.os.Handler(android.os.Looper.getMainLooper()).post { callback(result) }
        }.start()
    }
}

// ── Concrete Strategy: Token Refresh (auto-login) ─────────────

class TokenRefreshStrategy : LoginStrategy {
    override fun getName() = "token_refresh"

    override fun canAttempt(context: android.content.Context): Boolean {
        return SessionManager.getToken(context) != null
    }

    override fun execute(context: android.content.Context, params: Map<String, String>, callback: (LoginStrategyResult) -> Unit) {
        Thread {
            val res = ApiClient.get("/auth/me")
            val result = if (res.ok) {
                LoginStrategyResult(
                    success = true,
                    userJson = res.data.optJSONObject("user"),
                    needsBiometric = SessionManager.isBiometricEnabled(context)
                )
            } else {
                SessionManager.clear(context)
                LoginStrategyResult(false, error = "Token expired")
            }
            android.os.Handler(android.os.Looper.getMainLooper()).post { callback(result) }
        }.start()
    }
}

// ── Context: LoginManager menggunakan strategies ──────────────

class LoginManager {
    private val strategies = mutableListOf<LoginStrategy>()

    fun addStrategy(strategy: LoginStrategy): LoginManager {
        strategies.add(strategy)
        return this
    }

    /**
     * Coba strategi pertama yang applicable
     */
    fun attemptLogin(
        context: android.content.Context,
        params: Map<String, String> = emptyMap(),
        callback: (LoginStrategyResult) -> Unit
    ) {
        val applicable = strategies.firstOrNull { it.canAttempt(context) }

        if (applicable == null) {
            callback(LoginStrategyResult(false, error = "Tidak ada strategi login yang tersedia"))
            return
        }

        applicable.execute(context, params, callback)
    }
}
