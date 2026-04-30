/**
 * Long Polling — Real-time Communication
 */

import Http from './http.js';

let pollingActive = false;

export function startPolling(callback) {
    pollingActive = true;
    let lastAttId = 0;
    let lastLogId = 0;

    async function poll() {
        if (!pollingActive) return;

        try {
            const data = await Http.get(
                `/notifications/poll?last_att_id=${lastAttId}&last_log_id=${lastLogId}`
            );

            if (data.attendance?.length) {
                lastAttId = Math.max(...data.attendance.map(a => a.id));
            }
            if (data.logs?.length) {
                lastLogId = Math.max(...data.logs.map(l => l.id));
            }

            if (callback) callback(data);
        } catch (err) {
            // Silently retry
        }

        if (pollingActive) {
            setTimeout(poll, 1000);
        }
    }

    poll();
}

export function stopPolling() {
    pollingActive = false;
}
