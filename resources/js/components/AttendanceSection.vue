<template>
  <div>
    <header class="section-header"><h2 id="attendance-heading">Attendance</h2></header>

    <div class="card" style="margin-bottom:20px; padding:26px">
      <h4 style="font-size:15px;font-weight:700;margin-bottom:14px">Check In ke Kelas</h4>
      <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="field-group" style="flex:1;min-width:200px;margin-bottom:0">
          <label for="checkin-class">Pilih Kelas</label>
          <select id="checkin-class" v-model="selectedClass">
            <option value="">— Pilih Kelas —</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }} — {{ c.day }} {{ c.time }} ({{ c.trainer }})</option>
          </select>
        </div>
        <button type="button" class="btn btn--primary" style="height:42px" @click="checkIn">Check In</button>
      </div>
      <p v-if="checkinMsg" class="msg msg--success" style="margin-top:12px">{{ checkinMsg }}</p>
      <p v-if="checkinErr" class="msg msg--error" style="margin-top:12px">{{ checkinErr }}</p>
    </div>

    <Transition name="fade">
      <p v-if="liveMsg" class="msg msg--success" style="margin-bottom:16px">🔴 Live — {{ liveMsg }}</p>
    </Transition>

    <div class="card table-card">
      <table>
        <thead><tr><th>#</th><th>Member</th><th>Kelas</th><th>Tanggal</th><th>Status</th></tr></thead>
        <tbody>
          <tr v-if="!list.length"><td colspan="5" class="empty">Belum ada data.</td></tr>
          <tr v-for="(a, i) in list" :key="a.id || i">
            <td>{{ i + 1 }}</td>
            <td>{{ a.member }}</td>
            <td>{{ a.cls }}</td>
            <td>{{ a.date }}</td>
            <td><span class="role-chip member">{{ a.status }}</span></td>
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
  name: 'AttendanceSection',
  props: { currentUser: Object },
  emits: ['toast'],
  setup() {
    const list = ref([]);
    const classes = ref([]);
    const selectedClass = ref('');
    const checkinMsg = ref('');
    const checkinErr = ref('');
    const liveMsg = ref('');

    async function loadAttendance() {
      try { const data = await Http.get('/attendance'); list.value = data.attendance || []; }
      catch (e) { /* silent */ }
    }

    async function loadClasses() {
      try { const data = await Http.get('/schedule'); classes.value = data.schedule || []; }
      catch (e) { /* silent */ }
    }

    async function checkIn() {
      checkinMsg.value = ''; checkinErr.value = '';
      if (!selectedClass.value) { checkinErr.value = 'Silakan pilih kelas terlebih dahulu.'; return; }
      try {
        const data = await Http.post('/attendance', { schedule_id: parseInt(selectedClass.value) });
        checkinMsg.value = data.message;
        loadAttendance();
      } catch (e) { checkinErr.value = e.message; }
    }

    onMounted(() => { loadAttendance(); loadClasses(); });
    return { list, classes, selectedClass, checkinMsg, checkinErr, liveMsg, checkIn };
  },
};
</script>

<style scoped>
.section-header { margin-bottom: 30px; }
.section-header h2 { font-family: 'DM Serif Display', serif; font-size: 28px; }
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.table-card { padding: 0; overflow: hidden; }
.field-group label { display: block; font-size: 11.5px; font-weight: 600; color: #374151; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
select { width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 15px; outline: none; transition: 0.2s; font-family: 'DM Sans', sans-serif; }
select:focus { border-color: #22c55e; box-shadow: 0 0 0 3px #22c55e22; }
table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
thead th { padding: 13px 18px; background: #f9fafb; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; font-weight: 700; border-bottom: 1px solid #e5e7eb; }
tbody td { padding: 13px 18px; border-bottom: 1px solid #e5e7eb; color: #374151; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: #f9fafb; }
.empty { text-align: center; padding: 28px; color: #9ca3af; }
.role-chip { border-radius: 5px; padding: 3px 9px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
.role-chip.member { background: #dcfce7; color: #16a34a; }
.msg--success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 10px 14px; border-radius: 8px; font-size: 13px; }
.msg--error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 8px; font-size: 13px; }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn--primary { background: #22c55e; color: #fff; }
.btn--primary:hover { background: #16a34a; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
