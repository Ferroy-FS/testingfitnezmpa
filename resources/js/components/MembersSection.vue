<template>
  <div>
    <header class="section-header">
      <div><h2 id="members-heading">Members</h2><p class="section-sub">Kelola data member gym</p></div>
      <button v-if="currentUser?.role === 'admin'" type="button" class="btn btn--primary" @click="showForm = !showForm">+ Tambah Member</button>
    </header>

    <div v-if="showForm" class="card form-panel">
      <h4>Tambah Member Baru</h4>
      <form @submit.prevent="addMember" class="field-row">
        <div class="field-group"><label>Nama</label><input v-model="newName" type="text" required placeholder="Jane Smith" /></div>
        <div class="field-group"><label>Email</label><input v-model="newEmail" type="email" required placeholder="jane@contoh.com" /></div>
        <div class="field-group"><label>Role</label>
          <select v-model="newRole"><option value="member">Member</option><option value="trainer">Trainer</option></select>
        </div>
        <p v-if="formError" class="msg msg--error">{{ formError }}</p>
        <div class="btn-row">
          <button type="submit" class="btn btn--primary">Tambah</button>
          <button type="button" class="btn btn--ghost" @click="showForm = false">Batal</button>
        </div>
      </form>
    </div>

    <div class="card table-card">
      <table>
        <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>Role</th><th>Joined</th><th>Aksi</th></tr></thead>
        <tbody>
          <tr v-if="!users.length"><td colspan="6" class="empty">{{ loadingMsg }}</td></tr>
          <tr v-for="(u, i) in users" :key="u.id">
            <td>{{ i + 1 }}</td>
            <td><strong>{{ u.name }}</strong></td>
            <td>{{ u.email }}</td>
            <td><span class="role-chip" :class="u.role">{{ u.role }}</span></td>
            <td>{{ u.joined }}</td>
            <td>
              <button v-if="currentUser?.role === 'admin' && u.id !== currentUser?.id" type="button" class="btn btn--ghost btn--sm" @click="deleteUser(u.id)">Delete</button>
              <span v-else>—</span>
            </td>
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
  name: 'MembersSection',
  props: { currentUser: Object },
  emits: ['toast'],
  setup(props, { emit }) {
    const users = ref([]);
    const loadingMsg = ref('Memuat...');
    const showForm = ref(false);
    const newName = ref(''); const newEmail = ref(''); const newRole = ref('member');
    const formError = ref('');

    async function loadMembers() {
      try {
        const data = await Http.get('/users');
        users.value = data.users || [];
        if (!users.value.length) loadingMsg.value = 'Belum ada member.';
      } catch (e) { loadingMsg.value = 'Gagal memuat data.'; }
    }

    async function addMember() {
      formError.value = '';
      if (!newName.value || !newEmail.value) { formError.value = 'Nama dan email wajib.'; return; }
      try {
        await Http.post('/users', { name: newName.value, email: newEmail.value, role: newRole.value });
        showForm.value = false; newName.value = ''; newEmail.value = ''; newRole.value = 'member';
        loadMembers();
      } catch (e) { formError.value = e.message; }
    }

    async function deleteUser(id) {
      if (!confirm('Yakin ingin menghapus user ini?')) return;
      try { await Http.delete('/users/' + id); loadMembers(); }
      catch (e) { alert('Gagal: ' + e.message); }
    }

    onMounted(loadMembers);

    return { users, loadingMsg, showForm, newName, newEmail, newRole, formError, addMember, deleteUser };
  },
};
</script>

<style scoped>
.section-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 30px; gap: 16px; }
.section-header h2 { font-family: 'DM Serif Display', serif; font-size: 28px; margin-bottom: 3px; }
.section-sub { color: #6b7280; font-size: 13px; }
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.form-panel { padding: 26px; margin-bottom: 22px; }
.form-panel h4 { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
.field-row { display: flex; gap: 14px; flex-wrap: wrap; }
.field-group { flex: 1; min-width: 150px; margin-bottom: 12px; }
.field-group label { display: block; font-size: 11.5px; font-weight: 600; color: #374151; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
input, select { width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 15px; outline: none; transition: 0.2s; font-family: 'DM Sans', sans-serif; }
input:focus, select:focus { border-color: #22c55e; box-shadow: 0 0 0 3px #22c55e22; }
.btn-row { display: flex; gap: 8px; width: 100%; }
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
.msg--error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; width: 100%; }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn--primary { background: #22c55e; color: #fff; }
.btn--primary:hover { background: #16a34a; }
.btn--ghost { background: transparent; border: 1.5px solid #e5e7eb; color: #374151; }
.btn--ghost:hover { background: #f3f4f6; }
.btn--sm { padding: 4px 10px; font-size: 12px; }
</style>
