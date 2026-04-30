/**
 * ══════════════════════════════════════════════════════════════
 *  HTTP Client — FitNez Vue.js
 * ══════════════════════════════════════════════════════════════
 *
 * Mekanisme Sesi (poin c):
 * 1. Session: server menyimpan sesi di database via cookies
 * 2. Token: SHA-256 hashed token disimpan di localStorage
 *
 * Keamanan Client (poin d):
 * 1. Cookie HttpOnly & Secure (diset oleh server)
 * 2. Token di localStorage (untuk SPA, dikirim via Bearer header)
 *
 * Kontrol Penyimpanan (poin e.4):
 * - Halaman publik: hanya visitor_id di localStorage (jika consent)
 * - Halaman privat: token + user data di localStorage
 * ══════════════════════════════════════════════════════════════
 */

const API_BASE = '/api';

const Http = {
    // ── Token Management (disimpan di localStorage) ──
    getToken() {
        return localStorage.getItem('fitnez_token');
    },

    setToken(token) {
        localStorage.setItem('fitnez_token', token);
    },

    clearToken() {
        localStorage.removeItem('fitnez_token');
    },

    // ── OTP Status ──
    isOtpVerified() {
        return localStorage.getItem('fitnez_otp_verified') === 'true';
    },

    setOtpVerified(val) {
        localStorage.setItem('fitnez_otp_verified', val ? 'true' : 'false');
    },

    /**
     * Request utama — semua komunikasi client-server
     * Bearer token dikirim di header Authorization
     * Cookie dikirim otomatis (credentials: include)
     */
    async request(method, url, body, isFormData = false) {
        const opts = {
            method,
            headers: { 'Accept': 'application/json' },
            credentials: 'include', // Kirim cookie otomatis
        };

        // Token di header Authorization
        const token = this.getToken();
        if (token) {
            opts.headers['Authorization'] = 'Bearer ' + token;
        }

        // OTP verified header
        if (this.isOtpVerified()) {
            opts.headers['X-OTP-Verified'] = 'true';
        }

        // Body
        if (body && method !== 'GET') {
            if (isFormData) {
                opts.body = body; // FormData — browser sets Content-Type
            } else {
                opts.headers['Content-Type'] = 'application/json';
                opts.body = JSON.stringify(body);
            }
        }

        const res = await fetch(API_BASE + url, opts);
        const data = await res.json();

        if (!res.ok) {
            // OTP pending — redirect ke OTP verification
            if (data.otp_pending) {
                throw { message: data.error, otpPending: true };
            }

            // Unauthorized — clear token, redirect ke login
            if (res.status === 401 && !url.includes('/auth/login')) {
                this.clearToken();
                this.setOtpVerified(false);
                localStorage.removeItem('fitnez_user');
                window.location.href = '/login';
            }

            throw new Error(data.error || 'Request failed');
        }

        return data;
    },

    get(url) { return this.request('GET', url); },
    post(url, body) { return this.request('POST', url, body); },
    put(url, body) { return this.request('PUT', url, body); },
    delete(url) { return this.request('DELETE', url); },
    upload(url, formData) { return this.request('POST', url, formData, true); },
};

export default Http;
