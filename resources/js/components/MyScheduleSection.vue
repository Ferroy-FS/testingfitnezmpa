<template>
  <div>
    <header class="section-header">
      <h2 id="my-schedule-heading">Jadwal Latihan Saya</h2>
      <button type="button" class="btn btn--primary" @click="showForm = !showForm">+ Tambah Jadwal</button>
    </header>

    <div v-if="showForm" class="card form-panel">
      <h4>Tambah Jadwal Latihan</h4>
      <form @submit.prevent="addSchedule" class="field-row">
        <div class="field-group"><label>Nama Latihan</label><input v-model="form.name" type="text" required placeholder="Contoh: Leg Day" /></div>
        <div class="field-group"><label>Hari</label>
          <select v-model="form.day">
            <option v-for="d in days" :key="d" :value="d">{{ d }}</option>
          </select>
        </div>
        <div class="field-group"><label>Waktu</label><input v-model="form.time" type="time" required /></div>
        <div class="field-group"><label>Level</label>
          <select v-model="form.level">
            <option v-for="l in levels" :key="l" :value="l">{{ l }}</option>
          </select>
        </div>
        <div class="field-group" style="min-width:100%"><label>Catatan</label><textarea v-model="form.notes" rows="2" placeholder="Catatan latihan..."></textarea></div>
        <p v-if="formError" class="msg msg--error">{{ formError }}</p>
        <div class="btn-row">
          <button type="submit" class="btn btn--primary">Tambah</button>
          <button type="button" class="btn btn--ghost" @click="showForm = false">Batal</button>
        </div>
      </form>
    </div>

    <div class="schedule-grid">
      <p v-if="!items.length" class="empty">Belum ada jadwal latihan pribadi.</p>
      <article v-for="s in items" :key="s.id" class="sched-card">
        <h4>{{ s.name }}</h4>
        <p>📅 {{ s.day }} · {{ s.time }}</p>
        <p>🎯 {{ s.level }}</p>
        <p v-if="s.notes">📝 {{ s.notes }}</p>
        <button type="button" class="btn btn--ghost btn--sm" style="margin-top:10px" @click="deleteSchedule(s.id)">🗑️ Hapus</button>
      </article>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import Http from '../utils/http.js';

export default {
  name: 'MyScheduleSection',
  props: { currentUser: Object },
  emits: ['toast'],
  setup(props, { emit }) {
    const items = ref([]);
    const showForm = ref(false);
    const formError = ref('');
    const days = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
    const levels = ['Beginner','Intermediate','Advanced','Personal'];
    const form = reactive({ name: '', day: 'Senin', time: '', level: 'Beginner', notes: '' });

    async function load() {
      try { const data = await Http.get('/schedule/my'); items.value = data.schedule || []; }
      catch (e) { /* silent */ }
    }

    async function addSchedule() {
      formError.value = '';
      if (!form.name || !form.time) { formError.value = 'Nama dan waktu wajib diisi.'; return; }
      try {
        await Http.post('/schedule/my', { ...form });
        showForm.value = false;
        Object.assign(form, { name: '', day: 'Senin', time: '', level: 'Beginner', notes: '' });
        load();
        emit('toast', 'Jadwal latihan berhasil ditambahkan!');
      } catch (e) { formError.value = e.message; }
    }

    async function deleteSchedule(id) {
      if (!confirm('Hapus jadwal latihan ini?')) return;
      try { await Http.delete('/schedule/my/' + id); load(); }
      catch (e) { alert('Gagal: ' + e.message); }
    }

    onMounted(load);
    return { items, showForm, formError, days, levels, form, addSchedule, deleteSchedule };
  },
};
</script>

<style scoped>
.section-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 30px; }
.section-header h2 { font-family: 'DM Serif Display', serif; font-size: 28px; }
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.form-panel { padding: 26px; margin-bottom: 20px; }
.form-panel h4 { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
.field-row { display: flex; gap: 14px; flex-wrap: wrap; }
.field-group { flex: 1; min-width: 150px; margin-bottom: 12px; }
.field-group label { display: block; font-size: 11.5px; font-weight: 600; color: #374151; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
input, select, textarea { width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 15px; outline: none; transition: 0.2s; font-family: 'DM Sans', sans-serif; resize: vertical; }
input:focus, select:focus, textarea:focus { border-color: #22c55e; box-shadow: 0 0 0 3px #22c55e22; }
.btn-row { display: flex; gap: 8px; width: 100%; }
.schedule-grid { display: flex; flex-wrap: wrap; gap: 16px; }
.empty { color: #9ca3af; padding: 20px; }
.sched-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; border-top: 4px solid #22c55e; box-shadow: 0 1px 3px rgba(0,0,0,.08); min-width: 210px; transition: transform 0.2s; }
.sched-card:hover { transform: translateY(-3px); box-shadow: 0 4px 16px rgba(0,0,0,.10); }
.sched-card h4 { font-size: 15px; font-weight: 700; margin-bottom: 10px; }
.sched-card p { font-size: 12.5px; color: #6b7280; margin-bottom: 5px; }
.msg--error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; width: 100%; }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn--primary { background: #22c55e; color: #fff; }
.btn--primary:hover { background: #16a34a; }
.btn--ghost { background: transparent; border: 1.5px solid #e5e7eb; color: #374151; }
.btn--ghost:hover { background: #f3f4f6; }
.btn--sm { padding: 6px 12px; font-size: 12px; }
</style>
