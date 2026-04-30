<template>
  <!-- ═══════════════════════════════════════════════════════
       OTP VERIFICATION PAGE — Faktor Identitas #2
       "Barang yang dimiliki pengguna" (HP → OTP)
       Autentikasi berurutan: setelah password berhasil
       ═══════════════════════════════════════════════════════ -->
  <div class="auth-wrapper">
    <aside class="auth-hero">
      <div class="hero-content">
        <div class="brand">
          <span class="brand-bolt">⚡</span>
          <span class="brand-name">FitNez</span>
        </div>
        <h2 class="hero-headline">Verifikasi<br/>OTP</h2>
        <p class="hero-sub">
          Masukkan kode OTP yang dikirim ke perangkat Anda.
          Ini adalah langkah keamanan kedua (Two-Factor Authentication).
        </p>
        <div class="auth-factors">
          <div class="factor done">
            <span class="factor-icon">✅</span>
            <span>Password (SHA-256 + Salt)</span>
          </div>
          <div class="factor current">
            <span class="factor-icon">🔑</span>
            <span>OTP dari HP (simulasi)</span>
          </div>
          <div class="factor pending">
            <span class="factor-icon">🔒</span>
            <span>Akses Aplikasi</span>
          </div>
        </div>
      </div>
    </aside>

    <main class="auth-panel">
      <div class="auth-card">
        <form @submit.prevent="handleVerifyOtp" novalidate>
          <header class="form-header">
            <h3>🔑 Verifikasi OTP</h3>
            <p>Masukkan 6-digit kode OTP (Step 2: Faktor Kepemilikan)</p>
          </header>

          <div class="otp-inputs">
            <input
              v-for="(_, i) in 6"
              :key="i"
              :ref="el => { if (el) otpRefs[i] = el }"
              type="text"
              maxlength="1"
              class="otp-digit"
              @input="handleOtpInput(i, $event)"
              @keydown="handleOtpKeydown(i, $event)"
              @paste="handlePaste"
            />
          </div>

          <p v-if="errorMsg" class="msg msg--error">{{ errorMsg }}</p>
          <p v-if="successMsg" class="msg msg--success">{{ successMsg }}</p>

          <button type="submit" class="btn btn--primary btn--full" :disabled="loading">
            {{ loading ? 'Memverifikasi...' : 'Verifikasi OTP' }}
          </button>

          <!-- Demo: tampilkan OTP (di produksi ini tidak ada) -->
          <div v-if="demoOtp" class="otp-demo">
            <p>📱 <strong>Demo OTP</strong> (di produksi, kode dikirim via SMS):</p>
            <span class="otp-demo-code">{{ demoOtp }}</span>
          </div>

          <button type="button" class="btn btn--ghost btn--full" style="margin-top:10px" @click="goBack">
            ← Kembali ke Login
          </button>
        </form>
      </div>
    </main>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Http from '../utils/http.js';

export default {
  name: 'OtpPage',
  setup() {
    const router = useRouter();
    const otpRefs = ref([]);
    const errorMsg = ref('');
    const successMsg = ref('');
    const loading = ref(false);
    const demoOtp = ref('');

    onMounted(() => {
      // Cek apakah ada token (harus sudah login step 1)
      if (!Http.getToken()) {
        router.push({ name: 'login' });
        return;
      }

      // Ambil demo OTP dari localStorage
      demoOtp.value = localStorage.getItem('fitnez_otp_demo') || '';

      // Focus input pertama
      if (otpRefs.value[0]) otpRefs.value[0].focus();
    });

    function handleOtpInput(index, event) {
      const val = event.target.value;
      if (val && index < 5 && otpRefs.value[index + 1]) {
        otpRefs.value[index + 1].focus();
      }
    }

    function handleOtpKeydown(index, event) {
      if (event.key === 'Backspace' && !event.target.value && index > 0) {
        otpRefs.value[index - 1].focus();
      }
    }

    function handlePaste(event) {
      const pasted = event.clipboardData.getData('text').trim();
      if (/^\d{6}$/.test(pasted)) {
        for (let i = 0; i < 6; i++) {
          if (otpRefs.value[i]) otpRefs.value[i].value = pasted[i];
        }
        event.preventDefault();
      }
    }

    function getOtpValue() {
      return otpRefs.value.map(el => el?.value || '').join('');
    }

    async function handleVerifyOtp() {
      errorMsg.value = '';
      successMsg.value = '';
      loading.value = true;

      const otp = getOtpValue();
      if (otp.length !== 6) {
        errorMsg.value = 'Masukkan 6 digit OTP.';
        loading.value = false;
        return;
      }

      try {
        const data = await Http.post('/auth/verify-otp', { otp });

        // OTP berhasil → simpan status
        Http.setOtpVerified(true);
        Http.setToken(data.token);
        localStorage.setItem('fitnez_user', JSON.stringify(data.user));
        localStorage.removeItem('fitnez_otp_demo');

        successMsg.value = 'OTP terverifikasi! Mengalihkan...';

        setTimeout(() => {
          router.push({ name: 'dashboard' });
        }, 1000);

      } catch (err) {
        errorMsg.value = err.message || 'OTP tidak valid.';
      } finally {
        loading.value = false;
      }
    }

    function goBack() {
      Http.clearToken();
      Http.setOtpVerified(false);
      localStorage.removeItem('fitnez_otp_demo');
      router.push({ name: 'login' });
    }

    return {
      otpRefs, errorMsg, successMsg, loading, demoOtp,
      handleOtpInput, handleOtpKeydown, handlePaste, handleVerifyOtp, goBack,
    };
  },
};
</script>

<style scoped>
.auth-wrapper { display: flex; min-height: 100vh; }

.auth-hero {
  width: 400px; background: #111827; color: #fff;
  padding: 60px 44px; display: flex; align-items: center;
  flex-shrink: 0; position: relative; overflow: hidden;
}
.hero-content { position: relative; z-index: 1; }
.brand { display: flex; align-items: center; gap: 10px; margin-bottom: 48px; }
.brand-bolt { font-size: 28px; }
.brand-name { font-family: 'DM Serif Display', serif; font-size: 24px; }
.hero-headline { font-family: 'DM Serif Display', serif; font-size: 38px; line-height: 1.15; margin-bottom: 16px; color: #fff; }
.hero-sub { color: #9ca3af; font-size: 14px; line-height: 1.7; margin-bottom: 28px; }

.auth-factors { display: flex; flex-direction: column; gap: 12px; }
.factor { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #6b7280; padding: 8px 12px; border-radius: 8px; }
.factor.done { color: #22c55e; background: rgba(34,197,94,0.1); }
.factor.current { color: #fbbf24; background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.3); }
.factor.pending { color: #6b7280; }
.factor-icon { font-size: 16px; }

.auth-panel { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 40px; background: #f9fafb; }
.auth-card { width: 100%; max-width: 500px; background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 40px; box-shadow: 0 4px 16px rgba(0,0,0,.10); }
.form-header { margin-bottom: 28px; }
.form-header h3 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
.form-header p { color: #6b7280; font-size: 13px; }

.otp-inputs { display: flex; gap: 10px; justify-content: center; margin-bottom: 24px; }
.otp-digit {
  width: 52px; height: 60px; text-align: center; font-size: 24px; font-weight: 700;
  border: 2px solid #e5e7eb; border-radius: 12px; outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.otp-digit:focus { border-color: #22c55e; box-shadow: 0 0 0 3px #22c55e22; }

.msg--error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
.msg--success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }

.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn--primary { background: #22c55e; color: #fff; }
.btn--primary:hover { background: #16a34a; }
.btn--ghost { background: transparent; border: 1.5px solid #e5e7eb; color: #374151; }
.btn--ghost:hover { background: #f3f4f6; }
.btn--full { width: 100%; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }

.otp-demo {
  margin-top: 20px; padding: 16px; background: #fffbeb; border: 1px solid #fde68a;
  border-radius: 10px; text-align: center; font-size: 13px;
}
.otp-demo-code {
  display: inline-block; margin-top: 8px; font-size: 28px; font-weight: 700;
  letter-spacing: 8px; color: #d97706; background: #fef3c7;
  padding: 8px 20px; border-radius: 8px;
}

@media (max-width: 900px) { .auth-hero { display: none; } }
</style>
