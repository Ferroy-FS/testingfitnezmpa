/**
 * ══════════════════════════════════════════════════════════════
 *  WEB TRACKING — Enhanced dengan Tab Count & Incognito Detection
 * ══════════════════════════════════════════════════════════════
 *
 * Q: Apakah website bisa membaca ada berapa tab di tiap browser?
 * A: YA, TERBATAS. Menggunakan BroadcastChannel API, website bisa
 *    menghitung berapa tab/window yang membuka WEBSITE YANG SAMA.
 *    Website TIDAK bisa membaca tab dari website lain (sandboxed).
 *
 * Q: Apakah website bisa membaca tracking dalam mode incognito?
 * A: TERBATAS. Di mode incognito:
 *    - localStorage/cookies DIHAPUS saat window ditutup
 *    - Website bisa MENDETEKSI bahwa user dalam mode incognito
 *      (via storage quota check atau navigator API)
 *    - Tracking MASIH BISA BERJALAN selama session berlangsung,
 *      tapi visitor_id hilang saat tab ditutup
 *    - Server-side tracking (IP, user-agent) tetap bekerja
 *
 * Implementasi di bawah mencakup kedua fitur tersebut.
 * ══════════════════════════════════════════════════════════════
 */

const CONSENT_KEY = 'fitnez_tracking_consent';
const VISITOR_KEY = 'fitnez_visitor_id';
const TAB_CHANNEL = 'fitnez_tab_sync';

export const Tracker = {
    _tabCount: 1,
    _isIncognito: false,
    _broadcastChannel: null,
    _tabId: null,

    // ════════════════════════════════════════════════
    //  TAB COUNTING — BroadcastChannel API
    // ════════════════════════════════════════════════

    /**
     * Inisialisasi tab counting.
     * Menggunakan BroadcastChannel untuk komunikasi antar tab.
     * Setiap tab yang buka website ini akan terhitung.
     */
    initTabCounting() {
        if (typeof BroadcastChannel === 'undefined') {
            // Browser tidak support (Safari < 15.4)
            this._tabCount = 1;
            return;
        }

        this._tabId = crypto.randomUUID?.() || Math.random().toString(36).slice(2);
        this._broadcastChannel = new BroadcastChannel(TAB_CHANNEL);

        // Set untuk track semua tab yang aktif
        const activeTabs = new Set();
        activeTabs.add(this._tabId);

        this._broadcastChannel.onmessage = (event) => {
            const { type, tabId } = event.data;

            switch (type) {
                case 'tab_opened':
                    activeTabs.add(tabId);
                    // Balas agar tab baru tahu kita ada
                    this._broadcastChannel.postMessage({
                        type: 'tab_present', tabId: this._tabId
                    });
                    break;

                case 'tab_present':
                    activeTabs.add(tabId);
                    break;

                case 'tab_closed':
                    activeTabs.delete(tabId);
                    break;
            }

            this._tabCount = activeTabs.size;
        };

        // Broadcast bahwa tab ini baru dibuka
        this._broadcastChannel.postMessage({
            type: 'tab_opened', tabId: this._tabId
        });

        // Broadcast saat tab ditutup
        window.addEventListener('beforeunload', () => {
            this._broadcastChannel?.postMessage({
                type: 'tab_closed', tabId: this._tabId
            });
        });
    },

    /**
     * Dapatkan jumlah tab yang membuka website FitNez
     * @returns {number} Jumlah tab aktif
     */
    getTabCount() {
        return this._tabCount;
    },

    // ════════════════════════════════════════════════
    //  INCOGNITO DETECTION
    // ════════════════════════════════════════════════

    /**
     * Deteksi apakah browser dalam mode incognito/private.
     *
     * Metode deteksi (bervariasi per browser):
     * 1. Chrome: StorageManager.estimate() — quota lebih kecil di incognito
     * 2. Firefox: IndexedDB test — error di private mode (lama)
     * 3. Safari: localStorage quota test
     * 4. Generic: navigator.storage.persisted() behavior
     *
     * CATATAN: Browser semakin mempersulit deteksi incognito
     * demi privasi user. Hasil tidak 100% akurat.
     *
     * @returns {Promise<boolean>} true jika kemungkinan incognito
     */
    async detectIncognito() {
        try {
            // Metode 1: Chrome/Edge — storage quota check
            if (navigator.storage && navigator.storage.estimate) {
                const { quota } = await navigator.storage.estimate();
                // Chrome incognito biasanya punya quota < 120MB
                // Normal mode biasanya > 1GB
                if (quota && quota < 150 * 1024 * 1024) {
                    this._isIncognito = true;
                    return true;
                }
            }

            // Metode 2: FileSystem API (Chrome specific)
            if (window.webkitRequestFileSystem) {
                return new Promise((resolve) => {
                    window.webkitRequestFileSystem(
                        window.TEMPORARY, 100,
                        () => { this._isIncognito = false; resolve(false); },
                        () => { this._isIncognito = true; resolve(true); }
                    );
                });
            }

            // Metode 3: Safari — localStorage test
            try {
                const testKey = '__incognito_test__';
                localStorage.setItem(testKey, '1');
                localStorage.removeItem(testKey);
            } catch (e) {
                // Safari private mode throws QuotaExceededError
                this._isIncognito = true;
                return true;
            }

            this._isIncognito = false;
            return false;

        } catch (e) {
            // Tidak bisa mendeteksi — asumsikan normal
            this._isIncognito = false;
            return false;
        }
    },

    /**
     * Apakah browser dalam mode incognito?
     */
    isIncognito() {
        return this._isIncognito;
    },

    // ════════════════════════════════════════════════
    //  CONSENT & TRACKING (sama seperti sebelumnya)
    // ════════════════════════════════════════════════

    hasConsent() {
        return localStorage.getItem(CONSENT_KEY) === 'granted';
    },

    setConsent(granted) {
        localStorage.setItem(CONSENT_KEY, granted ? 'granted' : 'denied');
        if (granted) this.trackPageVisit();
    },

    isConsentPending() {
        return localStorage.getItem(CONSENT_KEY) === null;
    },

    getVisitorId() {
        let id = localStorage.getItem(VISITOR_KEY);
        if (!id) {
            const arr = new Uint8Array(16);
            crypto.getRandomValues(arr);
            id = Array.from(arr, b => b.toString(16).padStart(2, '0')).join('');
            localStorage.setItem(VISITOR_KEY, id);
        }
        return id;
    },

    getDeviceInfo() {
        const ua = navigator.userAgent;
        let deviceType = 'desktop';
        if (/Mobi|Android/i.test(ua)) deviceType = 'mobile';
        else if (/Tablet|iPad/i.test(ua)) deviceType = 'tablet';

        let browser = 'Unknown';
        if (ua.includes('Firefox')) browser = 'Firefox';
        else if (ua.includes('Edg')) browser = 'Edge';
        else if (ua.includes('Chrome')) browser = 'Chrome';
        else if (ua.includes('Safari')) browser = 'Safari';
        else if (ua.includes('Opera') || ua.includes('OPR')) browser = 'Opera';

        let os = 'Unknown';
        if (ua.includes('Windows')) os = 'Windows';
        else if (ua.includes('Mac OS')) os = 'macOS';
        else if (ua.includes('Linux')) os = 'Linux';
        else if (ua.includes('Android')) os = 'Android';
        else if (ua.includes('iOS') || ua.includes('iPhone')) os = 'iOS';

        return { device_type: deviceType, browser, os,
            screen_resolution: `${screen.width}x${screen.height}`,
            language: navigator.language || 'unknown',
        };
    },

    /**
     * Track page visit — sekarang menyertakan tab count dan incognito status
     */
    async trackPageVisit() {
        if (!this.hasConsent()) return;

        // Detect incognito sebelum tracking
        await this.detectIncognito();

        const deviceInfo = this.getDeviceInfo();
        const payload = {
            consent_given: true,
            visitor_id: this.getVisitorId(),
            session_id: sessionStorage.getItem('fitnez_session') || this._generateSessionId(),
            page_url: window.location.pathname,
            referrer: document.referrer || null,
            ...deviceInfo,
            // ── Data tambahan: tab count & incognito status ──
            extra_data: JSON.stringify({
                tab_count: this.getTabCount(),
                is_incognito: this.isIncognito(),
                viewport_width: window.innerWidth,
                viewport_height: window.innerHeight,
                color_depth: screen.colorDepth,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                connection_type: navigator.connection?.effectiveType || 'unknown',
            }),
        };

        try {
            const res = await fetch('/api/tracking/visit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (data.visitor_id) localStorage.setItem(VISITOR_KEY, data.visitor_id);
        } catch (err) {
            console.warn('Tracking failed:', err);
        }
    },

    startTimeTracking() {
        if (!this.hasConsent()) return;
        const startTime = Date.now();
        window.addEventListener('beforeunload', () => {
            const timeOnPage = Math.round((Date.now() - startTime) / 1000);
            navigator.sendBeacon('/api/tracking/time', JSON.stringify({
                visitor_id: this.getVisitorId(),
                time_on_page: timeOnPage,
            }));
        });
    },

    clearTrackingData() {
        localStorage.removeItem(CONSENT_KEY);
        localStorage.removeItem(VISITOR_KEY);
        sessionStorage.removeItem('fitnez_session');
    },

    /**
     * Inisialisasi lengkap: tab counting + incognito detection + tracking
     */
    async init() {
        this.initTabCounting();
        await this.detectIncognito();

        if (this.hasConsent()) {
            await this.trackPageVisit();
            this.startTimeTracking();
        }
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
