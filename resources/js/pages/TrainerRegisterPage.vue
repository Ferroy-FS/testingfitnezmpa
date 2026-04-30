<template>
  <div class="auth-wrapper">
    <aside class="auth-hero">
      <div class="hero-content">
        <div class="brand"><span class="brand-bolt">⚡</span><span class="brand-name">FitNez</span></div>
        <h2 class="hero-headline">Become A<br/>Trainer.</h2>
        <p class="hero-sub">Upload sertifikat Anda untuk menjadi trainer profesional di FitNez.</p>
      </div>
    </aside>
    <main class="auth-panel">
      <div class="auth-card">
        <header class="form-header">
          <h3>🏋️ Daftar Sebagai Trainer</h3>
          <p>Upload sertifikat untuk verifikasi</p>
        </header>
        <form @submit.prevent="handleUpload" novalidate>
          <fieldset>
            <div class="field-group">
              <label>Sertifikat (PDF)</label>
              <div class="upload-area" :class="{ dragover: isDragging }" @click="$refs.fileInput.click()" @dragover.prevent="isDragging = true" @dragleave="isDragging = false" @drop.prevent="handleDrop">
                <input ref="fileInput" type="file" accept=".pdf" style="display:none" @change="handleFileChange" />
                <div class="upload-icon">📄</div>
                <p>Klik atau drag file PDF sertifikat di sini</p>
                <p v-if="fileName" class="file-name">✅ {{ fileName }}</p>
              </div>
            </div>
          </fieldset>
          <p v-if="errorMsg" class="msg msg--error">{{ errorMsg }}</p>
          <button type="submit" class="btn btn--primary btn--full" :disabled="loading || !selectedFile" style="margin-top:12px">
            {{ loading ? 'Mengupload...' : 'Upload & Verifikasi' }}
          </button>
          <router-link to="/dashboard" class="btn btn--ghost btn--full" style="margin-top:8px;text-decoration:none;text-align:center">
            Kembali ke Dashboard
          </router-link>
        </form>
      </div>
    </main>
    <!-- Loading overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
      <h3>Memverifikasi Sertifikat...</h3>
      <p>Mohon tunggu sebentar</p>
      <div class="loading-steps">
        <div v-for="(step, i) in steps" :key="i" class="step" :class="{ done: step.done, active: step.active }">
          {{ step.done ? '✅' : step.active ? '⏳' : '⏳' }} {{ step.text }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Http from '../utils/http.js';

export default {
  name: 'TrainerRegisterPage',
  setup() {
    const router = useRouter();
    const selectedFile = ref(null);
    const fileName = ref('');
    const errorMsg = ref('');
    const loading = ref(false);
    const isDragging = ref(false);
    const steps = ref([
      { text: 'Mengupload sertifikat...', done: false, active: false },
      { text: 'Memvalidasi dokumen...', done: false, active: false },
      { text: 'Mengupdate role Anda...', done: false, active: false },
    ]);

    function handleFileChange(e) {
      if (e.target.files.length) processFile(e.target.files[0]);
    }

    function handleDrop(e) {
      isDragging.value = false;
      if (e.dataTransfer.files.length) processFile(e.dataTransfer.files[0]);
    }

    function processFile(file) {
      if (file.type !== 'application/pdf') {
        errorMsg.value = 'Hanya file PDF yang diterima.';
        return;
      }
      selectedFile.value = file;
      fileName.value = file.name;
      errorMsg.value = '';
    }

    async function handleUpload() {
      if (!selectedFile.value) { errorMsg.value = 'Silakan upload sertifikat.'; return; }
      loading.value = true;
      errorMsg.value = '';

      for (let i = 0; i < steps.value.length; i++) {
        steps.value[i].active = true;
        await new Promise(r => setTimeout(r, 1200));
        steps.value[i].active = false;
        steps.value[i].done = true;
      }

      try {
        const formData = new FormData();
        formData.append('certificate', selectedFile.value);
        const data = await Http.upload('/users/upgrade-trainer', formData);
        if (data.user) localStorage.setItem('fitnez_user', JSON.stringify(data.user));
        await new Promise(r => setTimeout(r, 800));
        router.push({ name: 'dashboard' });
      } catch (err) {
        loading.value = false;
        errorMsg.value = err.message;
      }
    }

    return { selectedFile, fileName, errorMsg, loading, isDragging, steps, handleFileChange, handleDrop, handleUpload };
  },
};
</script>

<style scoped>
.auth-wrapper { display: flex; min-height: 100vh; }
.auth-hero { width: 400px; background: #111827; color: #fff; padding: 60px 44px; display: flex; align-items: center; flex-shrink: 0; position: relative; overflow: hidden; }
.auth-hero::before { content: ''; position: absolute; bottom: -80px; right: -80px; width: 300px; height: 300px; background: #22c55e; border-radius: 50%; opacity: .06; }
.hero-content { position: relative; z-index: 1; }
.brand { display: flex; align-items: center; gap: 10px; margin-bottom: 48px; }
.brand-bolt { font-size: 28px; }
.brand-name { font-family: 'DM Serif Display', serif; font-size: 24px; }
.hero-headline { font-family: 'DM Serif Display', serif; font-size: 38px; line-height: 1.15; margin-bottom: 16px; color: #fff; }
.hero-sub { color: #9ca3af; font-size: 14px; line-height: 1.7; }
.auth-panel { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 40px; background: #f9fafb; }
.auth-card { width: 100%; max-width: 600px; background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 40px; box-shadow: 0 4px 16px rgba(0,0,0,.10); }
.form-header { margin-bottom: 22px; }
.form-header h3 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
.form-header p { color: #6b7280; font-size: 13px; }
fieldset { border: none; }
.field-group { margin-bottom: 16px; }
.field-group label { display: block; font-size: 11.5px; font-weight: 600; color: #374151; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
.upload-area { border: 2px dashed #e5e7eb; border-radius: 12px; padding: 32px; text-align: center; cursor: pointer; transition: 0.2s; background: #f9fafb; }
.upload-area:hover, .upload-area.dragover { border-color: #22c55e; background: #dcfce7; }
.upload-icon { font-size: 36px; margin-bottom: 10px; }
.upload-area p { color: #6b7280; font-size: 13px; }
.file-name { color: #16a34a; font-weight: 600; font-size: 14px; margin-top: 8px; }
.msg--error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn--primary { background: #22c55e; color: #fff; }
.btn--primary:hover { background: #16a34a; }
.btn--ghost { background: transparent; border: 1.5px solid #e5e7eb; color: #374151; }
.btn--ghost:hover { background: #f3f4f6; }
.btn--full { width: 100%; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.loading-overlay { position: fixed; inset: 0; background: rgba(255,255,255,0.96); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; }
.spinner { width: 56px; height: 56px; border: 4px solid #e5e7eb; border-top-color: #22c55e; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 20px; }
@keyframes spin { to { transform: rotate(360deg); } }
.loading-overlay h3 { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
.loading-overlay p { color: #6b7280; font-size: 13px; }
.loading-steps { margin-top: 20px; text-align: left; }
.step { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6b7280; padding: 6px 0; }
.step.done { color: #16a34a; font-weight: 600; }
.step.active { color: #111827; }
@media (max-width: 900px) { .auth-hero { display: none; } }
</style>
