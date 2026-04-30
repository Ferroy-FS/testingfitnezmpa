<template>
  <!-- ═══════════════════════════════════════════════════════
       WEB TRACKING STATS — Admin Dashboard
       Menampilkan data tracking pengunjung landing page
       ═══════════════════════════════════════════════════════ -->
  <div>
    <header class="section-header">
      <div><h2 id="tracking-heading">📊 Web Tracking</h2><p class="section-sub">Statistik pengunjung landing page (tanpa Google Analytics)</p></div>
      <button type="button" class="btn btn--ghost" @click="loadStats">🔄 Refresh</button>
    </header>

    <div class="stats-grid">
      <article class="stat-card">
        <span class="stat-icon">👁️</span>
        <div class="stat-num">{{ stats.total_visits ?? 0 }}</div>
        <div class="stat-label">Total Kunjungan</div>
      </article>
      <article class="stat-card">
        <span class="stat-icon">👤</span>
        <div class="stat-num">{{ stats.unique_visitors ?? 0 }}</div>
        <div class="stat-label">Pengunjung Unik</div>
      </article>
      <article class="stat-card">
        <span class="stat-icon">📅</span>
        <div class="stat-num">{{ stats.today_visits ?? 0 }}</div>
        <div class="stat-label">Hari Ini</div>
      </article>
    </div>

    <div class="cards-row">
      <div class="card" style="flex:1">
        <h4>Device Breakdown</h4>
        <div v-if="stats.device_breakdown?.length">
          <div v-for="d in stats.device_breakdown" :key="d.device_type" class="breakdown-row">
            <span>{{ d.device_type || 'Unknown' }}</span>
            <strong>{{ d.total }}</strong>
          </div>
        </div>
        <p v-else class="empty">Belum ada data.</p>
      </div>

      <div class="card" style="flex:1">
        <h4>Browser Breakdown</h4>
        <div v-if="stats.browser_breakdown?.length">
          <div v-for="b in stats.browser_breakdown" :key="b.browser" class="breakdown-row">
            <span>{{ b.browser || 'Unknown' }}</span>
            <strong>{{ b.total }}</strong>
          </div>
        </div>
        <p v-else class="empty">Belum ada data.</p>
      </div>
    </div>

    <div class="card" style="margin-top:20px">
      <h4>Kunjungan Terakhir</h4>
      <div class="table-card">
        <table>
          <thead><tr><th>Visitor ID</th><th>Halaman</th><th>Device</th><th>Browser</th><th>Waktu</th></tr></thead>
          <tbody>
            <tr v-if="!stats.recent_visits?.length"><td colspan="5" class="empty">Belum ada data.</td></tr>
            <tr v-for="v in stats.recent_visits" :key="v.visitor_id + v.created_at">
              <td><code>{{ v.visitor_id?.slice(0, 12) }}...</code></td>
              <td>{{ v.page_url }}</td>
              <td>{{ v.device_type || '-' }}</td>
              <td>{{ v.browser || '-' }}</td>
              <td>{{ v.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import Http from '../utils/http.js';

export default {
  name: 'TrackingSection',
  emits: ['toast'],
  setup() {
    const stats = ref({});

    async function loadStats() {
      try { stats.value = await Http.get('/tracking/stats'); }
      catch (e) { /* silent */ }
    }

    onMounted(loadStats);
    return { stats, loadStats };
  },
};
</script>

<style scoped>
.section-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 30px; }
.section-header h2 { font-family: 'DM Serif Display', serif; font-size: 28px; }
.section-sub { color: #6b7280; font-size: 13px; }

.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
.stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.stat-icon { font-size: 24px; display: block; margin-bottom: 8px; }
.stat-num { font-size: 32px; font-weight: 700; color: #22c55e; }
.stat-label { font-size: 13px; color: #6b7280; margin-top: 4px; }

.cards-row { display: flex; gap: 20px; }
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.card h4 { font-size: 15px; font-weight: 700; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb; }

.breakdown-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
.breakdown-row:last-child { border-bottom: none; }

.table-card { overflow: hidden; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
thead th { padding: 10px 14px; background: #f9fafb; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; font-weight: 700; border-bottom: 1px solid #e5e7eb; }
tbody td { padding: 10px 14px; border-bottom: 1px solid #e5e7eb; color: #374151; }
tbody tr:last-child td { border-bottom: none; }
code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
.empty { text-align: center; padding: 20px; color: #9ca3af; font-size: 13px; }

.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn--ghost { background: transparent; border: 1.5px solid #e5e7eb; color: #374151; }
.btn--ghost:hover { background: #f3f4f6; }

@media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } .cards-row { flex-direction: column; } }
</style>
