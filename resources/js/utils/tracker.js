/**
 * ══════════════════════════════════════════════════════════════
 *  Web Tracking — Tanpa Google Analytics (poin e.2)
 * ══════════════════════════════════════════════════════════════
 *
 * Alur:
 * 1. User buka landing page
 * 2. Banner consent muncul → minta izin cookies/localStorage
 * 3. Jika consent diberikan → data tracking dikirim ke server
 * 4. Data: visitor_id, browser, OS, device, screen, dll.
 *
 * Kontrol Penyimpanan Client (poin d & e.4):
 * - Consent status disimpan di localStorage
 * - Visitor ID disimpan di localStorage (jika consent)
 * - Data publik (tracking) terpisah dari data privat (auth)
 * ══════════════════════════════════════════════════════════════
 */

const CONSENT_KEY = 'fitnez_tracking_consent';
const VISITOR_KEY = 'fitnez_visitor_id';

export const Tracker = {
    /**
     * Cek apakah user sudah memberikan consent
     */
    hasConsent() {
        return localStorage.getItem(CONSENT_KEY) === 'granted';
    },

    /**
     * Simpan consent status
     */
    setConsent(granted) {
        localStorage.setItem(CONSENT_KEY, granted ? 'granted' : 'denied');
        if (granted) {
            this.trackPageVisit();
        }
    },

    /**
     * Cek apakah consent belum pernah ditanya
     */
    isConsentPending() {
        return localStorage.getItem(CONSENT_KEY) === null;
    },

    /**
     * Generate atau ambil visitor ID
     */
    getVisitorId() {
        let id = localStorage.getItem(VISITOR_KEY);
        if (!id) {
            // Generate random visitor ID
            const arr = new Uint8Array(16);
            crypto.getRandomValues(arr);
            id = Array.from(arr, b => b.toString(16).padStart(2, '0')).join('');
            localStorage.setItem(VISITOR_KEY, id);
        }
        return id;
    },

    /**
     * Deteksi info browser/device
     */
    getDeviceInfo() {
        const ua = navigator.userAgent;

        // Device type
        let deviceType = 'desktop';
        if (/Mobi|Android/i.test(ua)) deviceType = 'mobile';
        else if (/Tablet|iPad/i.test(ua)) deviceType = 'tablet';

        // Browser
        let browser = 'Unknown';
        if (ua.includes('Firefox')) browser = 'Firefox';
        else if (ua.includes('Edg')) browser = 'Edge';
        else if (ua.includes('Chrome')) browser = 'Chrome';
        else if (ua.includes('Safari')) browser = 'Safari';
        else if (ua.includes('Opera') || ua.includes('OPR')) browser = 'Opera';

        // OS
        let os = 'Unknown';
        if (ua.includes('Windows')) os = 'Windows';
        else if (ua.includes('Mac OS')) os = 'macOS';
        else if (ua.includes('Linux')) os = 'Linux';
        else if (ua.includes('Android')) os = 'Android';
        else if (ua.includes('iOS') || ua.includes('iPhone')) os = 'iOS';

        return {
            device_type: deviceType,
            browser,
            os,
            screen_resolution: `${screen.width}x${screen.height}`,
            language: navigator.language || navigator.userLanguage || 'unknown',
        };
    },

    /**
     * Kirim data tracking ke server
     * Hanya jika consent diberikan
     */
    async trackPageVisit() {
        if (!this.hasConsent()) return;

        const deviceInfo = this.getDeviceInfo();
        const payload = {
            consent_given: true,
            visitor_id: this.getVisitorId(),
            session_id: sessionStorage.getItem('fitnez_session') || this._generateSessionId(),
            page_url: window.location.pathname,
            referrer: document.referrer || null,
            ...deviceInfo,
        };

        try {
            const res = await fetch('/api/tracking/visit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (data.visitor_id) {
                localStorage.setItem(VISITOR_KEY, data.visitor_id);
            }
        } catch (err) {
            console.warn('Tracking failed:', err);
        }
    },

    /**
     * Track waktu di halaman (dipanggil saat beforeunload)
     */
    startTimeTracking() {
        if (!this.hasConsent()) return;

        const startTime = Date.now();

        window.addEventListener('beforeunload', () => {
            const timeOnPage = Math.round((Date.now() - startTime) / 1000);
            const payload = JSON.stringify({
                visitor_id: this.getVisitorId(),
                time_on_page: timeOnPage,
            });

            // Gunakan sendBeacon agar data terkirim walau tab ditutup
            navigator.sendBeacon('/api/tracking/time', payload);
        });
    },

    /**
     * Bersihkan data tracking dari client
     * Dipanggil jika user cabut consent
     */
    clearTrackingData() {
        localStorage.removeItem(CONSENT_KEY);
        localStorage.removeItem(VISITOR_KEY);
        sessionStorage.removeItem('fitnez_session');
    },

    _generateSessionId() {
        const arr = new Uint8Array(16);
        crypto.getRandomValues(arr);
        const id = Array.from(arr, b => b.toString(16).padStart(2, '0')).join('');
        sessionStorage.setItem('fitnez_session', id);
        return id;
    },
};

export default Tracker;
