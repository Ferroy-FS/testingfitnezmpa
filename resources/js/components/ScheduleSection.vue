<template>
  <div>
    <header class="section-header"><h2 id="schedule-heading">Jadwal Kelas Gym</h2></header>
    <div class="schedule-grid">
      <p v-if="!classes.length" class="empty">Memuat jadwal...</p>
      <article v-for="c in classes" :key="c.id" class="sched-card">
        <h4>{{ c.name }}</h4>
        <p>📅 {{ c.day }} · {{ c.time }}</p>
        <p>👤 {{ c.trainer }}</p>
        <p>🎯 {{ c.level }}</p>
        <p>🪑 {{ c.slots }} spots available</p>
      </article>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import Http from '../utils/http.js';

export default {
  name: 'ScheduleSection',
  emits: ['toast'],
  setup() {
    const classes = ref([]);
    onMounted(async () => {
      try { const data = await Http.get('/schedule'); classes.value = data.schedule || []; }
      catch (e) { /* silent */ }
    });
    return { classes };
  },
};
</script>

<style scoped>
.section-header { margin-bottom: 30px; }
.section-header h2 { font-family: 'DM Serif Display', serif; font-size: 28px; }
.schedule-grid { display: flex; flex-wrap: wrap; gap: 16px; }
.empty { color: #9ca3af; padding: 20px; }
.sched-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; border-top: 4px solid #22c55e; box-shadow: 0 1px 3px rgba(0,0,0,.08); transition: transform 0.2s, box-shadow 0.2s; min-width: 210px; }
.sched-card:hover { transform: translateY(-3px); box-shadow: 0 4px 16px rgba(0,0,0,.10); }
.sched-card h4 { font-size: 15px; font-weight: 700; margin-bottom: 10px; }
.sched-card p { font-size: 12.5px; color: #6b7280; margin-bottom: 5px; }
</style>
