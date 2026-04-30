package com.myapplication.patterns.command

import com.myapplication.api.ApiClient
import com.myapplication.api.ApiResponse
import com.myapplication.auth.SessionManager
import org.json.JSONObject

/**
 * ══════════════════════════════════════════════════════════════
 *  COMMAND — Behavioral Pattern #3 (Android)
 * ══════════════════════════════════════════════════════════════
 *
 * Mengenkapsulasi operasi sebagai objek command.
 * Setiap aksi (checkin, update profile, delete user, dll)
 * adalah command yang bisa dieksekusi, di-undo, atau di-queue.
 *
 * Manfaat:
 * - Operasi bisa di-retry jika gagal (network error)
 * - History operasi bisa disimpan
 * - Bisa dieksekusi secara batch
 * ══════════════════════════════════════════════════════════════
 */

// ── Command Interface ─────────────────────────────────────────

interface ApiCommand {
    fun getName(): String
    fun execute(callback: (ApiResponse) -> Unit)
    fun canUndo(): Boolean = false
    fun undo(callback: (ApiResponse) -> Unit) {}
}

// ── Concrete Command: Check In ────────────────────────────────

class CheckInCommand(private val scheduleId: Int) : ApiCommand {
    private var lastAttendanceId: Int? = null

    override fun getName() = "check_in"

    override fun execute(callback: (ApiResponse) -> Unit) {
        val body = JSONObject().put("schedule_id", scheduleId)
        Thread {
            val res = ApiClient.post("/attendance", body)
            android.os.Handler(android.os.Looper.getMainLooper()).post { callback(res) }
        }.start()
    }
}

// ── Concrete Command: Update Profile ──────────────────────────

class UpdateProfileCommand(
    private val name: String,
    private val phone: String,
    private val bio: String
) : ApiCommand {
    override fun getName() = "update_profile"

    override fun execute(callback: (ApiResponse) -> Unit) {
        val body = JSONObject().put("name", name).put("phone", phone).put("bio", bio)
        Thread {
            val res = ApiClient.put("/users/profile", body)
            android.os.Handler(android.os.Looper.getMainLooper()).post { callback(res) }
        }.start()
    }
}

// ── Concrete Command: Add Personal Schedule ───────────────────

class AddScheduleCommand(
    private val name: String,
    private val day: String,
    private val time: String,
    private val level: String = "Personal"
) : ApiCommand {
    override fun getName() = "add_schedule"

    override fun execute(callback: (ApiResponse) -> Unit) {
        val body = JSONObject().put("name", name).put("day", day)
            .put("time", time).put("level", level)
        Thread {
            val res = ApiClient.post("/schedule/my", body)
            android.os.Handler(android.os.Looper.getMainLooper()).post { callback(res) }
        }.start()
    }
}

// ── Concrete Command: Delete Schedule ─────────────────────────

class DeleteScheduleCommand(private val scheduleId: Int) : ApiCommand {
    override fun getName() = "delete_schedule"

    override fun execute(callback: (ApiResponse) -> Unit) {
        Thread {
            val res = ApiClient.delete("/schedule/my/$scheduleId")
            android.os.Handler(android.os.Looper.getMainLooper()).post { callback(res) }
        }.start()
    }
}

// ── Concrete Command: Logout ──────────────────────────────────

class LogoutCommand(private val context: android.content.Context) : ApiCommand {
    override fun getName() = "logout"

    override fun execute(callback: (ApiResponse) -> Unit) {
        Thread {
            val res = ApiClient.post("/auth/logout")
            SessionManager.clear(context)
            android.os.Handler(android.os.Looper.getMainLooper()).post { callback(res) }
        }.start()
    }
}

// ── Invoker: CommandExecutor ──────────────────────────────────

class CommandExecutor {
    private val history = mutableListOf<ApiCommand>()

    fun execute(command: ApiCommand, callback: (ApiResponse) -> Unit) {
        history.add(command)
        command.execute(callback)
    }

    fun getHistory(): List<ApiCommand> = history.toList()

    /**
     * Retry last failed command
     */
    fun retryLast(callback: (ApiResponse) -> Unit) {
        history.lastOrNull()?.execute(callback)
    }
}
