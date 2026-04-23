/**
 * FitNez — Vanilla JS Application
 * Communication Layer: fetch API (GET/POST/PUT/DELETE)
 * Auth: Session + Cookie + Bearer Token
 */

const API_BASE = '/api';

//1. HTTP CLIENT — request & response via fetch
const Http = {
    getToken() { return localStorage.getItem('fitnez_token'); },
    setToken(t) { localStorage.setItem('fitnez_token', t); },
    clearToken() { localStorage.removeItem('fitnez_token'); },

    async request(method, url, body) {
        const opts = {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'include',
        };
        const token = this.getToken();
        if (token) opts.headers['Authorization'] = 'Bearer ' + token;
        if (body && method !== 'GET') opts.body = JSON.stringify(body);

        const res = await fetch(API_BASE + url, opts);
        const data = await res.json();
        if (!res.ok) {
            if (res.status === 401 && !url.includes('/auth/login')) {
                this.clearToken();
                window.location.href = '/pages/login.html';
            }
            throw new Error(data.error || 'Request failed');
        }
        return data;
    },

    get(url) { return this.request('GET', url); },
    post(url, body) { return this.request('POST', url, body); },
    put(url, body) { return this.request('PUT', url, body); },
    delete(url) { return this.request('DELETE', url); },
};

//2. LONG POLLING — Real-time communication
let pollingActive = false;

function startPolling(callback) {
    pollingActive = true;
    let lastAttId = 0, lastLogId = 0;

    async function poll() {
        if (!pollingActive) return;
        try {
            const data = await Http.get(`/notifications/poll?last_att_id=${lastAttId}&last_log_id=${lastLogId}`);
            if (data.attendance?.length) lastAttId = Math.max(...data.attendance.map(a => a.id));
            if (data.logs?.length) lastLogId = Math.max(...data.logs.map(l => l.id));
            if (callback) callback(data);
        } catch (_) {}
        if (pollingActive) setTimeout(poll, 1000);
    }
    poll();
}

function stopPolling() { pollingActive = false; }
