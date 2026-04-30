<template>
  <div class="auth-wrapper">
    <aside class="auth-hero">
      <div class="hero-content">
        <div class="brand">
          <span class="brand-bolt">⚡</span>
          <span class="brand-name">FitNez</span>
        </div>
        <h2 class="hero-headline">Train Smarter.<br/>Live Better.</h2>
        <p class="hero-sub">Platform fitness modern untuk member, trainer, dan admin.</p>
      </div>
    </aside>

    <main class="auth-panel">
      <div class="auth-card">
        <div class="auth-tabs">
          <router-link to="/login" class="tab active">Login</router-link>
          <router-link to="/register" class="tab">Register</router-link>
        </div>

        <form @submit.prevent="handleLogin" novalidate>
          <header class="form-header">
            <h3>Selamat Datang</h3>
            <p>Login ke akun Anda (Step 1: Password)</p>
          </header>

          <fieldset>
            <div class="field-group">
              <label for="email">Email</label>
              <input id="email" v-model="email" type="email" placeholder="anda@contoh.com" required />
            </div>
            <div class="field-group">
              <label for="password">Password</label>
              <input id="password" v-model="password" type="password" placeholder="Masukkan password" required />
            </div>
          </fieldset>

          <p v-if="errorMsg" class="msg msg--error">{{ errorMsg }}</p>

          <button type="submit" class="btn btn--primary btn--full" :disabled="loading">
            {{ loading ? 'Memproses...' : 'Sign In' }}
          </button>

          <p class="demo-hint">
            Demo — password: <code>123456</code><br/>
            <span class="demo-chip" @click="fill('admin@gmail.com')">admin@gmail.com</span>
            <span class="demo-chip" @click="fill('trainer@gmail.com')">trainer@gmail.com</span>
            <span class="demo-chip" @click="fill('member@gmail.com')">member@gmail.com</span>
          </p>

          <div class="auth-info">
            <h4>🔐 Faktor Identitas yang Diterapkan:</h4>
            <p><strong>Step 1:</strong> Password (SHA-256 + Salt) — yang diketahui pengguna</p>
            <p><strong>Step 2:</strong> OTP via HP (simulasi) — yang dimiliki pengguna</p>
            <p>Autentikasi berurutan: Password dulu → OTP kemudian</p>
          </div>
        </form>
      </div>
    </main>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Http from '../utils/http.js';

export default {
  name: 'LoginPage',
  setup() {
    const router = useRouter();
    const email = ref('');
    const password = ref('');
    const errorMsg = ref('');
    const loading = ref(false);

    function fill(e) {
      email.value = e;
      password.value = '123456';
    }

    async function handleLogin() {
      errorMsg.value = '';
      loading.value = true;

      if (!email.value || !password.value) {
        errorMsg.value = 'Harap isi semua field.';
        loading.value = false;
        return;
      }

      try {
        const data = await Http.post('/auth/login', {
          email: email.value,
          password: password.value,
        });

        // Simpan token (mekanisme sesi: token di localStorage)
        Http.setToken(data.token);
        localStorage.setItem('fitnez_user', JSON.stringify(data.user));

        // Simpan OTP code demo (untuk simulasi — di produksi dikirim via SMS)
        if (data.otp_code_demo) {
          localStorage.setItem('fitnez_otp_demo', data.otp_code_demo);
        }

        // Redirect ke halaman OTP verification (step 2)
        router.push({ name: 'verify-otp' });

      } catch (err) {
        errorMsg.value = err.message || 'Login gagal.';
      } finally {
        loading.value = false;
      }
    }

    return { email, password, errorMsg, loading, fill, handleLogin };
  },
};
</script>

<style scoped>
/* Auth styles reused from style.css */
.auth-wrapper { display: flex; min-height: 100vh; }

.auth-hero {
  width: 400px; background: #111827; color: #fff;
  padding: 60px 44px; display: flex; align-items: center;
  flex-shrink: 0; position: relative; overflow: hidden;
}
.auth-hero::before {
  content: ''; position: absolute; bottom: -80px; right: -80px;
  width: 300px; height: 300px; background: #22c55e; border-radius: 50%; opacity: .06;
}

.hero-content { position: relative; z-index: 1; }
.brand { display: flex; align-items: center; gap: 10px; margin-bottom: 48px; }
.brand-bolt { font-size: 28px; }
.brand-name { font-family: 'DM Serif Display', serif; font-size: 24px; }
.hero-headline { font-family: 'DM Serif Display', serif; font-size: 38px; line-height: 1.15; margin-bottom: 16px; color: #fff; }
.hero-sub { color: #9ca3af; font-size: 14px; line-height: 1.7; }

.auth-panel { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 40px; background: #f9fafb; }

.auth-card {
  width: 100%; max-width: 600px; background: #fff;
  border: 1px solid #e5e7eb; border-radius: 18px;
  padding: 40px; box-shadow: 0 4px 16px rgba(0,0,0,.10);
}

.auth-tabs { display: flex; gap: 4px; background: #f3f4f6; border-radius: 8px; padding: 4px; margin-bottom: 28px; }
.tab {
  flex: 1; text-align: center; padding: 9px 0; border: none; background: none;
  border-radius: 6px; font-weight: 600; font-size: 14px; color: #6b7280;
  text-decoration: none; transition: 0.2s ease;
}
.tab.active, .tab.router-link-active { background: #fff; color: #111827; box-shadow: 0 1px 3px rgba(0,0,0,.08); }

.form-header { margin-bottom: 22px; }
.form-header h3 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
.form-header p { color: #6b7280; font-size: 13px; }

fieldset { border: none; }
.field-group { margin-bottom: 16px; }
.field-group label { display: block; font-size: 11.5px; font-weight: 600; color: #374151; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }

input {
  width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 15px; color: #111827; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
}
input:focus { border-color: #22c55e; box-shadow: 0 0 0 3px #22c55e22; }

.msg--error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }

.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn--primary { background: #22c55e; color: #fff; }
.btn--primary:hover { background: #16a34a; }
.btn--full { width: 100%; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }

.demo-hint { margin-top: 16px; font-size: 12px; color: #6b7280; text-align: center; line-height: 2.2; }
.demo-chip {
  background: #f0fdf4; color: #16a34a; border-radius: 4px; padding: 2px 7px;
  margin: 0 2px; font-size: 11px; font-weight: 500; cursor: pointer;
}
.demo-chip:hover { background: #dcfce7; }

.auth-info {
  margin-top: 20px; padding: 16px; background: #f0fdf4; border: 1px solid #bbf7d0;
  border-radius: 10px; font-size: 12px; color: #374151;
}
.auth-info h4 { font-size: 13px; margin-bottom: 8px; }
.auth-info p { margin-bottom: 4px; line-height: 1.6; }

@media (max-width: 900px) { .auth-hero { display: none; } }
</style>
