package com.myapplication.patterns.adapter

import org.json.JSONArray
import org.json.JSONObject

/**
 * ══════════════════════════════════════════════════════════════
 *  ADAPTER — Structural Pattern (Android)
 * ══════════════════════════════════════════════════════════════
 *
 * Mengadaptasi response JSON dari Laravel API
 * ke data class Kotlin yang mudah dipakai di UI.
 *
 * Manfaat:
 * - Jika format JSON dari server berubah, ubah adapter saja
 * - Activity/UI code tidak perlu parsing JSON langsung
 * - Bisa ditest independen dari UI
 * ══════════════════════════════════════════════════════════════
 */

// ── Domain Models (target) ────────────────────────────────────

data class UserDto(
    val id: Int,
    val name: String,
    val email: String,
    val role: String,
    val phone: String?,
    val bio: String,
    val hasTrainerCert: Boolean,
    val joined: String
)

data class ScheduleDto(
    val id: Int,
    val name: String,
    val day: String,
    val time: String,
    val trainer: String,
    val level: String,
    val slots: Int
)

data class AttendanceDto(
    val id: Int,
    val member: String,
    val className: String,
    val date: String,
    val status: String
)

data class LogDto(
    val id: Int,
    val who: String,
    val action: String,
    val role: String,
    val timestamp: String
)

data class LoginResult(
    val success: Boolean,
    val token: String?,
    val user: UserDto?,
    val otpPending: Boolean,
    val otpCodeDemo: String?,
    val error: String?
)

// ── Adapter: JSON → Domain Models ─────────────────────────────

class ApiResponseAdapter {

    fun adaptUser(json: JSONObject): UserDto {
        return UserDto(
            id = json.optInt("id", 0),
            name = json.optString("name", ""),
            email = json.optString("email", ""),
            role = json.optString("role", ""),
            phone = json.optString("phone", null),
            bio = json.optString("bio", ""),
            hasTrainerCert = json.optBoolean("has_trainer_cert", false),
            joined = json.optString("joined", "")
        )
    }

    fun adaptLoginResponse(json: JSONObject): LoginResult {
        val success = json.has("token") || json.optBoolean("otp_pending", false)
        return LoginResult(
            success = success,
            token = json.optString("token", null),
            user = if (json.has("user")) adaptUser(json.getJSONObject("user")) else null,
            otpPending = json.optBoolean("otp_pending", false),
            otpCodeDemo = json.optString("otp_code_demo", null),
            error = json.optString("error", null)
        )
    }

    fun adaptScheduleList(jsonArray: JSONArray): List<ScheduleDto> {
        return (0 until jsonArray.length()).map { i ->
            val s = jsonArray.getJSONObject(i)
            ScheduleDto(
                id = s.optInt("id", 0),
                name = s.optString("name", ""),
                day = s.optString("day", ""),
                time = s.optString("time", ""),
                trainer = s.optString("trainer", ""),
                level = s.optString("level", ""),
                slots = s.optInt("slots", 0)
            )
        }
    }

    fun adaptAttendanceList(jsonArray: JSONArray): List<AttendanceDto> {
        return (0 until jsonArray.length()).map { i ->
            val a = jsonArray.getJSONObject(i)
            AttendanceDto(
                id = a.optInt("id", 0),
                member = a.optString("member", ""),
                className = a.optString("cls", ""),
                date = a.optString("date", ""),
                status = a.optString("status", "")
            )
        }
    }

    fun adaptLogList(jsonArray: JSONArray): List<LogDto> {
        return (0 until jsonArray.length()).map { i ->
            val l = jsonArray.getJSONObject(i)
            LogDto(
                id = l.optInt("id", 0),
                who = l.optString("who", ""),
                action = l.optString("action", ""),
                role = l.optString("role", ""),
                timestamp = l.optString("ts", "")
            )
        }
    }

    fun adaptUserList(jsonArray: JSONArray): List<UserDto> {
        return (0 until jsonArray.length()).map { i -> adaptUser(jsonArray.getJSONObject(i)) }
    }
}
