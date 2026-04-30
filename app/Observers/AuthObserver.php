<?php

namespace App\Observers;

use App\Models\SystemLog;
use App\Models\User;

/**
 * ══════════════════════════════════════════════════════════════
 *  OBSERVER — Behavioral Pattern #1
 * ══════════════════════════════════════════════════════════════
 *
 * Ketika event auth terjadi (login, logout, register, dll),
 * semua observer yang terdaftar otomatis dinotifikasi.
 *
 * Manfaat maintenance:
 * - Tambah logging → tambah observer, tanpa ubah auth code
 * - Tambah notifikasi email → tambah observer lain
 * - Auth code (subject) tidak perlu tahu siapa yang mendengarkan
 *
 * Contoh penggunaan:
 *   $dispatcher->subscribe(new SystemLogObserver());
 *   $dispatcher->subscribe(new NotificationObserver());
 *   $dispatcher->notify('user.login', $user);
 * ══════════════════════════════════════════════════════════════
 */

// ── Observer Interface ────────────────────────────────────────

interface AuthObserverInterface
{
    public function onEvent(string $eventName, array $data): void;
    public function getSubscribedEvents(): array;
}

// ── Subject (Event Dispatcher) ────────────────────────────────

class AuthEventDispatcher
{
    /** @var AuthObserverInterface[] */
    private array $observers = [];

    public function subscribe(AuthObserverInterface $observer): void
    {
        $this->observers[] = $observer;
    }

    public function unsubscribe(AuthObserverInterface $observer): void
    {
        $this->observers = array_filter(
            $this->observers,
            fn ($o) => $o !== $observer
        );
    }

    /**
     * Notifikasi semua observer yang subscribe ke event ini
     */
    public function notify(string $eventName, array $data = []): void
    {
        foreach ($this->observers as $observer) {
            if (in_array($eventName, $observer->getSubscribedEvents()) ||
                in_array('*', $observer->getSubscribedEvents())) {
                $observer->onEvent($eventName, $data);
            }
        }
    }
}

// ── Concrete Observer: System Logger ──────────────────────────

class SystemLogObserver implements AuthObserverInterface
{
    public function getSubscribedEvents(): array
    {
        return ['user.login', 'user.logout', 'user.register', 'user.otp_verified'];
    }

    public function onEvent(string $eventName, array $data): void
    {
        $user = $data['user'] ?? null;
        if (!$user) return;

        $descriptions = [
            'user.login'        => ($user->full_name ?? 'User') . ' logged in',
            'user.logout'       => ($user->full_name ?? 'User') . ' logged out',
            'user.register'     => ($user->full_name ?? 'User') . ' registered',
            'user.otp_verified' => ($user->full_name ?? 'User') . ' verified OTP (2FA)',
        ];

        $actionTypes = [
            'user.login'        => 'LOGIN',
            'user.logout'       => 'LOGOUT',
            'user.register'     => 'REGISTER',
            'user.otp_verified' => 'OTP_VERIFY',
        ];

        SystemLog::create([
            'user_id'        => $user->id,
            'action_type'    => $actionTypes[$eventName] ?? strtoupper($eventName),
            'table_affected' => 'users',
            'record_id'      => $user->id,
            'description'    => $descriptions[$eventName] ?? $eventName,
        ]);
    }
}

// ── Concrete Observer: Real-time Notification ─────────────────

class RealtimeNotificationObserver implements AuthObserverInterface
{
    public function getSubscribedEvents(): array
    {
        return ['user.login', 'user.logout', 'attendance.checkin'];
    }

    public function onEvent(string $eventName, array $data): void
    {
        // Di implementasi sebenarnya, ini bisa push ke WebSocket/SSE/polling cache
        // Untuk sekarang, log ke file sebagai placeholder
        $msg = match ($eventName) {
            'user.login'          => ($data['user']->full_name ?? 'User') . ' baru saja login',
            'user.logout'         => ($data['user']->full_name ?? 'User') . ' baru saja logout',
            'attendance.checkin'  => ($data['member'] ?? 'Member') . ' check-in ke ' . ($data['class'] ?? 'kelas'),
            default               => "Event: $eventName",
        };

        // Cache untuk long polling (bisa diganti Redis di production)
        // Sementara diabaikan, hanya menunjukkan pattern
    }
}
