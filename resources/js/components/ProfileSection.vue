<template>
  <div>
    <div class="profile-grid">
      <article class="card identity-card">
        <div class="avatar">{{ initials }}</div>
        <h3>{{ user?.name || '—' }}</h3>
        <span class="badge">{{ user?.role || '—' }}</span>
        <p class="bio">{{ user?.bio || 'Belum ada bio' }}</p>
        <dl class="detail-list">
          <div class="detail-row"><dt>Email</dt><dd>{{ user?.email || '—' }}</dd></div>
          <div class="detail-row"><dt>Role</dt><dd>{{ user?.role || '—' }}</dd></div>
          <div class="detail-row"><dt>Joined</dt><dd>{{ user?.joined || '—' }}</dd></div>
          <div class="detail-row"><dt>Status</dt><dd class="status-active">● Active</dd></div>
        </dl>
        <div class="trainer-action">
          <template v-if="user?.role === 'trainer'">
            <button type="button" class="btn btn--ghost btn--full" @click="switchRole" :disabled="switching">🔄 Ubah menjadi Member</button>
          </template>
          <template v-else-if="user?.role === 'member' && user?.has_trainer_cert">
            <button type="button" class="btn btn--primary btn--full" @click="switchRole" :disabled="switching">🔄 Ubah menjadi Trainer</button>
          </template>
          <template v-else-if="user?.role === 'member'">
            <router-link to="/trainer-register" class="btn btn--primary btn--full" style="text-decoration:none;text-align:center">🏋️ Daftar Trainer Sekarang!</router-link>
          </template>
        </div>
      </article>

      <article class="card edit-card">
        <h4>Update Profile</h4>
        <form @submit.prevent="updateProfile">
          <div class="field-group"><label>Nama</label><input v-model="editName" type="text" /></div>
          <div class="field-group"><label>Telepon</label><input v-model="editPhone" type="tel" placeholder="+62 812 000 0000" /></div>
          <div class="field-group"><label>Bio</label><textarea v-model="editBio" rows="3" placeholder="Ceritakan tentang diri Anda..."></textarea></div>
          <p v-if="profileMsg" class="msg msg--success">{{ profileMsg }}</p>
          <button type="submit" class="btn btn--primary">Simpan</button>
        </form>
      </article>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';
import Http from '../utils/http.js';

export default {
  name: 'ProfileSection',
  props: { user: Object },
  emits: ['updated', 'toast'],
  setup(props, { emit }) {
    const editName = ref('');
    const editPhone = ref('');
    const editBio = ref('');
    const profileMsg = ref('');
    const switching = ref(false);

    const initials = computed(() => {
      if (!props.user?.name) return '?';
      return props.user.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
    });

    watch(() => props.user, (u) => {
      if (u) { editName.value = u.name || ''; editPhone.value = u.phone || ''; editBio.value = u.bio || ''; }
    }, { immediate: true });

    async function updateProfile() {
      try {
        const data = await Http.put('/users/profile', {
          name: editName.value.trim(),
          phone: editPhone.value.trim(),
          bio: editBio.value.trim(),
        });
        emit('updated', data.user);
        profileMsg.value = 'Profil berhasil diperbarui!';
        setTimeout(() => { profileMsg.value = ''; }, 3000);
      } catch (e) { alert('Gagal: ' + e.message); }
    }

    async function switchRole() {
      switching.value = true;
      try {
        const data = await Http.post('/users/switch-role', {});
        emit('updated', data.user);
        emit('toast', data.message);
      } catch (e) { alert('Gagal: ' + e.message); }
      finally { switching.value = false; }
    }

    return { editName, editPhone, editBio, profileMsg, initials, switching, updateProfile, switchRole };
  },
};
</script>

<style scoped>
.profile-grid { display: grid; grid-template-columns: 290px 1fr; gap: 22px; align-items: start; }
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 26px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.identity-card { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 14px; }
.avatar { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; font-size: 26px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.badge { background: #dcfce7; color: #16a34a; border: 1px solid #86efac; border-radius: 20px; padding: 4px 14px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
.bio { color: #6b7280; font-size: 13px; font-style: italic; }
.detail-list { width: 100%; text-align: left; }
.detail-row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
.detail-row:last-child { border-bottom: none; }
.detail-row dt { color: #6b7280; font-weight: 500; }
.detail-row dd { font-weight: 600; text-align: right; word-break: break-all; }
.status-active { color: #22c55e; font-weight: 600; }
.trainer-action { width: 100%; margin-top: 12px; }
.edit-card h4 { font-size: 16px; font-weight: 700; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
.field-group { margin-bottom: 16px; }
.field-group label { display: block; font-size: 11.5px; font-weight: 600; color: #374151; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
input, textarea { width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 15px; color: #111827; outline: none; transition: 0.2s; resize: vertical; font-family: 'DM Sans', sans-serif; }
input:focus, textarea:focus { border-color: #22c55e; box-shadow: 0 0 0 3px #22c55e22; }
.msg--success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn--primary { background: #22c55e; color: #fff; }
.btn--primary:hover { background: #16a34a; }
.btn--ghost { background: transparent; border: 1.5px solid #e5e7eb; color: #374151; }
.btn--ghost:hover { background: #f3f4f6; }
.btn--full { width: 100%; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
@media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } }
</style>
