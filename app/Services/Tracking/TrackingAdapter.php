<?php

namespace App\Services\Tracking;

/**
 * ══════════════════════════════════════════════════════════════
 *  ADAPTER — Structural Pattern
 * ══════════════════════════════════════════════════════════════
 *
 * Mengadaptasi data tracking dari format client (browser/mobile)
 * ke format yang seragam untuk disimpan di database.
 *
 * Setiap platform (web browser, Android, iOS) mengirim data
 * dengan struktur berbeda. Adapter menerjemahkan ke format standar.
 *
 * Manfaat maintenance:
 * - Tambah platform baru → tambah adapter baru, tanpa ubah DB/controller
 * - Client format berubah → ubah adapter saja, logika bisnis tetap
 * ══════════════════════════════════════════════════════════════
 */

// ── Target Interface ──────────────────────────────────────────

interface TrackingDataInterface
{
    public function getVisitorId(): string;
    public function getSessionId(): ?string;
    public function getPageUrl(): string;
    public function getReferrer(): ?string;
    public function getUserAgent(): ?string;
    public function getIpAddress(): ?string;
    public function getDeviceType(): ?string;
    public function getBrowser(): ?string;
    public function getOs(): ?string;
    public function getScreenResolution(): ?string;
    public function getLanguage(): ?string;
    public function getTimeOnPage(): ?int;
    public function hasConsent(): bool;
    public function toArray(): array;
}

// ── Adapter: Web Browser ──────────────────────────────────────

class WebBrowserTrackingAdapter implements TrackingDataInterface
{
    private array $rawData;
    private ?string $ip;
    private ?string $userAgent;

    /**
     * @param array $rawData Data dari JavaScript tracker (browser)
     */
    public function __construct(array $rawData, ?string $ip = null, ?string $userAgent = null)
    {
        $this->rawData = $rawData;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
    }

    public function getVisitorId(): string
    {
        return $this->rawData['visitor_id'] ?? bin2hex(random_bytes(16));
    }

    public function getSessionId(): ?string
    {
        return $this->rawData['session_id'] ?? null;
    }

    public function getPageUrl(): string
    {
        return $this->rawData['page_url'] ?? '/';
    }

    public function getReferrer(): ?string
    {
        return $this->rawData['referrer'] ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent ?? $this->rawData['user_agent'] ?? null;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip;
    }

    public function getDeviceType(): ?string
    {
        return $this->rawData['device_type'] ?? null;
    }

    public function getBrowser(): ?string
    {
        return $this->rawData['browser'] ?? null;
    }

    public function getOs(): ?string
    {
        return $this->rawData['os'] ?? null;
    }

    public function getScreenResolution(): ?string
    {
        return $this->rawData['screen_resolution'] ?? null;
    }

    public function getLanguage(): ?string
    {
        return $this->rawData['language'] ?? null;
    }

    public function getTimeOnPage(): ?int
    {
        return isset($this->rawData['time_on_page']) ? (int) $this->rawData['time_on_page'] : null;
    }

    public function hasConsent(): bool
    {
        return (bool) ($this->rawData['consent_given'] ?? false);
    }

    public function toArray(): array
    {
        return [
            'visitor_id' => $this->getVisitorId(),
            'session_id' => $this->getSessionId(),
            'page_url' => $this->getPageUrl(),
            'referrer' => $this->getReferrer(),
            'user_agent' => $this->getUserAgent(),
            'ip_address' => $this->getIpAddress(),
            'device_type' => $this->getDeviceType(),
            'browser' => $this->getBrowser(),
            'os' => $this->getOs(),
            'screen_resolution' => $this->getScreenResolution(),
            'language' => $this->getLanguage(),
            'time_on_page' => $this->getTimeOnPage(),
            'consent_given' => $this->hasConsent(),
        ];
    }
}

// ── Adapter: Android Native App ───────────────────────────────

class AndroidTrackingAdapter implements TrackingDataInterface
{
    private array $rawData;

    /**
     * Android mengirim data dengan key yang berbeda dari web
     * Misal: 'device_id' bukan 'visitor_id', 'screen_size' bukan 'screen_resolution'
     */
    public function __construct(array $rawData)
    {
        $this->rawData = $rawData;
    }

    public function getVisitorId(): string
    {
        // Android pakai device_id atau android_id
        return $this->rawData['device_id'] ?? $this->rawData['visitor_id'] ?? bin2hex(random_bytes(16));
    }

    public function getSessionId(): ?string
    {
        return $this->rawData['app_session_id'] ?? $this->rawData['session_id'] ?? null;
    }

    public function getPageUrl(): string
    {
        // Android pakai screen_name bukan page_url
        return $this->rawData['screen_name'] ?? $this->rawData['page_url'] ?? '/android';
    }

    public function getReferrer(): ?string
    {
        return $this->rawData['install_referrer'] ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->rawData['user_agent'] ?? 'FitNez-Android/' . ($this->rawData['app_version'] ?? '1.0');
    }

    public function getIpAddress(): ?string
    {
        return $this->rawData['ip_address'] ?? null;
    }

    public function getDeviceType(): ?string
    {
        return 'mobile'; // Android selalu mobile
    }

    public function getBrowser(): ?string
    {
        return 'FitNez App'; // Native app, bukan browser
    }

    public function getOs(): ?string
    {
        return 'Android ' . ($this->rawData['os_version'] ?? '');
    }

    public function getScreenResolution(): ?string
    {
        return $this->rawData['screen_size'] ?? $this->rawData['screen_resolution'] ?? null;
    }

    public function getLanguage(): ?string
    {
        return $this->rawData['device_language'] ?? $this->rawData['language'] ?? null;
    }

    public function getTimeOnPage(): ?int
    {
        return isset($this->rawData['session_duration']) ? (int) $this->rawData['session_duration'] : null;
    }

    public function hasConsent(): bool
    {
        return (bool) ($this->rawData['consent_given'] ?? false);
    }

    public function toArray(): array
    {
        return [
            'visitor_id' => $this->getVisitorId(),
            'session_id' => $this->getSessionId(),
            'page_url' => $this->getPageUrl(),
            'referrer' => $this->getReferrer(),
            'user_agent' => $this->getUserAgent(),
            'ip_address' => $this->getIpAddress(),
            'device_type' => $this->getDeviceType(),
            'browser' => $this->getBrowser(),
            'os' => $this->getOs(),
            'screen_resolution' => $this->getScreenResolution(),
            'language' => $this->getLanguage(),
            'time_on_page' => $this->getTimeOnPage(),
            'consent_given' => $this->hasConsent(),
        ];
    }
}
