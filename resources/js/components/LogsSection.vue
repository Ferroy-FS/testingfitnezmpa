<template>
  <div>
    <header class="section-header">
      <div><h2 id="logs-heading">Auth Logs</h2><span class="polling-badge">● Polling aktif</span></div>
      <button type="button" class="btn btn--ghost" @click="clearLogs">Hapus Logs</button>
    </header>
    <div class="card table-card">
      <table>
        <thead><tr><th>#</th><th>User</th><th>Action</th><th>Role</th><th>Timestamp</th></tr></thead>
        <tbody>
          <tr v-if="!logs.length"><td colspan="5" class="empty">Belum ada log.</td></tr>
          <tr v-for="(l, i) in logs" :key="l.id || i">
            <td>{{ i + 1 }}</td>
            <td>{{ l.who }}</td>
            <td><strong>{{ l.action }}</strong></td>
            <td><span class="role-chip" :class="l.role">{{ l.role }}</span></td>
            <td>{{ l.ts }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import Http from '../utils/http.js';

export default {
  name: 'LogsSection',
  emits: ['toast'],
  setup() {
    const logs = ref([]);
    async function loadLogs() {
      try { const data = await Http.get('/logs'); logs.value = data.logs || []; }
      catch (e) { /* silent */ }
    }
    async function clearLogs() {
      if (!confirm('Hapus semua auth logs?')) return;
      try { await Http.delete('/logs'); loadLogs(); }
      catch (e) { alert('Gagal: ' + e.message); }
    }
    onMounted(loadLogs);
    return { logs, clearLogs };
  },
};
</script>

<style scoped>
.section-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 30px; }
.section-header h2 { font-family: 'DM Serif Display', serif; font-size: 28px; }
.polling-badge { font-size: 12px; color: #22c55e; }
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.table-card { padding: 0; overflow: hidden; }
table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
thead th { padding: 13px 18px; background: #f9fafb; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; font-weight: 700; border-bottom: 1px solid #e5e7eb; }
tbody td { padding: 13px 18px; border-bottom: 1px solid #e5e7eb; color: #374151; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: #f9fafb; }
.empty { text-align: center; padding: 28px; color: #9ca3af; }
.role-chip { border-radius: 5px; padding: 3px 9px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
.role-chip.admin { background: #fef3c7; color: #92400e; }
.role-chip.trainer { background: #ede9fe; color: #5b21b6; }
.role-chip.member { background: #dcfce7; color: #16a34a; }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn--ghost { background: transparent; border: 1.5px solid #e5e7eb; color: #374151; }
.btn--ghost:hover { background: #f3f4f6; }
</style>
