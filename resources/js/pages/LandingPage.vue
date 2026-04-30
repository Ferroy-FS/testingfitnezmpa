<template>
  <!-- ═══════════════════════════════════════════════════════
       LANDING PAGE — Halaman Publik (poin e.1)
       Calon pengguna bisa memperoleh informasi:
       - Profil perusahaan (FitNez)
       - Produk/layanan
       - Fitur yang bisa digunakan
       Tracking web aktif setiap kali halaman ini dibuka
       ═══════════════════════════════════════════════════════ -->
  <div class="landing">

    <!-- Navigation -->
    <nav class="landing-nav">
      <div class="nav-inner">
        <div class="nav-brand">
          <span class="brand-bolt">⚡</span>
          <span class="brand-name">FitNez</span>
        </div>
        <div class="nav-links">
          <a href="#features">Fitur</a>
          <a href="#pricing">Harga</a>
          <a href="#about">Tentang</a>
          <router-link to="/login" class="nav-cta">Masuk</router-link>
        </div>
      </div>
    </nav>

    <!-- Hero Section — Profil Perusahaan -->
    <section class="hero">
      <div class="hero-bg"></div>
      <div class="hero-content">
        <h1>Train Smarter.<br/>Live Better.</h1>
        <p class="hero-sub">
          Platform fitness modern untuk mengelola gym, jadwal kelas, kehadiran, dan personal training.
          Dibangun untuk member, trainer, dan admin.
        </p>
        <div class="hero-actions">
          <router-link to="/register" class="hero-btn hero-btn--primary">
            Mulai Gratis
          </router-link>
          <a href="#features" class="hero-btn hero-btn--outline">
            Lihat Fitur
          </a>
        </div>
        <div class="hero-stats">
          <div class="stat"><span class="stat-num">500+</span><span class="stat-label">Member Aktif</span></div>
          <div class="stat"><span class="stat-num">50+</span><span class="stat-label">Kelas/Minggu</span></div>
          <div class="stat"><span class="stat-num">20+</span><span class="stat-label">Trainer Pro</span></div>
        </div>
      </div>
    </section>

    <!-- Features — Fitur Aplikasi -->
    <section id="features" class="section features">
      <h2 class="section-title">Fitur Unggulan</h2>
      <p class="section-sub">Semua yang Anda butuhkan untuk mengelola gym modern</p>
      <div class="feature-grid">
        <article v-for="feat in features" :key="feat.icon" class="feature-card">
          <span class="feature-icon">{{ feat.icon }}</span>
          <h3>{{ feat.title }}</h3>
          <p>{{ feat.desc }}</p>
        </article>
      </div>
    </section>

    <!-- Pricing — Produk/Layanan -->
    <section id="pricing" class="section pricing">
      <h2 class="section-title">Harga Trainer</h2>
      <p class="section-sub">Pilih paket personal training yang sesuai</p>
      <div class="price-grid">
        <article v-for="plan in plans" :key="plan.name" class="price-card" :class="{ featured: plan.featured }">
          <h3>{{ plan.name }}</h3>
          <div class="price">{{ plan.price }}</div>
          <p class="price-desc">{{ plan.desc }}</p>
          <ul>
            <li v-for="item in plan.items" :key="item">{{ item }}</li>
          </ul>
          <router-link to="/register" class="price-btn" :class="{ 'price-btn--primary': plan.featured }">
            Pilih Paket
          </router-link>
        </article>
      </div>
    </section>

    <!-- About — Tentang FitNez -->
    <section id="about" class="section about">
      <h2 class="section-title">Tentang FitNez</h2>
      <div class="about-content">
        <p>
          FitNez adalah platform manajemen gym yang dibangun dengan teknologi modern.
          Kami menyediakan solusi lengkap untuk mengelola member, jadwal kelas, kehadiran,
          dan personal training dalam satu platform yang terintegrasi.
        </p>
        <p>
          Dengan fitur autentikasi berlapis (Two-Factor Authentication), data Anda terjamin keamanannya.
          Platform kami mendukung role-based access untuk admin, trainer, dan member.
        </p>
      </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
      <div class="footer-inner">
        <div class="footer-brand">
          <span class="brand-bolt">⚡</span>
          <span class="brand-name">FitNez</span>
          <p>Platform fitness modern.</p>
        </div>
        <div class="footer-links">
          <a href="#features">Fitur</a>
          <a href="#pricing">Harga</a>
          <a href="#about">Tentang</a>
          <a href="mailto:supportfitnez@gmail.com">Kontak</a>
        </div>
        <p class="footer-copy">&copy; 2025 FitNez. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script>
import { onMounted } from 'vue';
import Tracker from '../utils/tracker.js';

export default {
  name: 'LandingPage',
  setup() {
    const features = [
      { icon: '📅', title: 'Jadwal Kelas', desc: 'Lihat dan kelola jadwal kelas gym secara real-time. Yoga, HIIT, Pilates, dan lainnya.' },
      { icon: '✅', title: 'Check-in Digital', desc: 'Absensi kehadiran langsung dari aplikasi. Data terupdate secara real-time via Long Polling.' },
      { icon: '🏋️', title: 'Personal Training', desc: 'Buat jadwal latihan pribadi dan kelola sesi personal training Anda sendiri.' },
      { icon: '👥', title: 'Manajemen Member', desc: 'Admin dan trainer bisa mengelola data member, tambah, dan hapus user.' },
      { icon: '🔐', title: 'Keamanan Berlapis', desc: 'Two-Factor Authentication (Password + OTP), session database, dan token SHA-256.' },
      { icon: '💬', title: 'FAQ Chatbot', desc: 'Widget FAQ interaktif untuk menjawab pertanyaan umum tentang gym dan layanan.' },
    ];

    const plans = [
      {
        name: 'Sesi Tunggal',
        price: 'Rp 400k',
        desc: 'Per sesi',
        items: ['1x sesi personal training', 'Durasi 60 menit', 'Program disesuaikan'],
        featured: false,
      },
      {
        name: '5 Sesi',
        price: 'Rp 1.8jt',
        desc: 'Hemat 10%',
        items: ['5x sesi personal training', 'Durasi 60 menit/sesi', 'Progress tracking', 'Konsultasi nutrisi'],
        featured: true,
      },
      {
        name: '10 Sesi',
        price: 'Rp 3.4jt',
        desc: 'Hemat 15%',
        items: ['10x sesi personal training', 'Durasi 60 menit/sesi', 'Progress tracking', 'Konsultasi nutrisi', 'Program meal plan'],
        featured: false,
      },
    ];

    // ── Tracking: catat setiap kali landing page dibuka ──
    onMounted(() => {
      if (Tracker.hasConsent()) {
        Tracker.trackPageVisit();
      }
    });

    return { features, plans };
  },
};
</script>

<style scoped>
/* ── Landing Page Styles ── */

.landing {
  font-family: 'DM Sans', sans-serif;
  color: #111827;
  background: #f9fafb;
}

/* Nav */
.landing-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid #e5e7eb;
}

.nav-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 14px 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 8px;
}
.brand-bolt { font-size: 22px; }
.brand-name {
  font-family: 'DM Serif Display', serif;
  font-size: 20px;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 28px;
}
.nav-links a {
  color: #6b7280;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  transition: color 0.2s;
}
.nav-links a:hover { color: #111827; }

.nav-cta {
  background: #22c55e !important;
  color: #fff !important;
  padding: 8px 20px;
  border-radius: 8px;
  font-weight: 600 !important;
  transition: background 0.2s !important;
}
.nav-cta:hover { background: #16a34a !important; }

/* Hero */
.hero {
  position: relative;
  padding: 140px 28px 80px;
  text-align: center;
  overflow: hidden;
}
.hero-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #111827 0%, #1f2937 50%, #111827 100%);
  z-index: 0;
}
.hero-bg::after {
  content: '';
  position: absolute;
  bottom: -100px;
  right: -100px;
  width: 400px;
  height: 400px;
  background: #22c55e;
  border-radius: 50%;
  opacity: 0.06;
}
.hero-content {
  position: relative;
  z-index: 1;
  max-width: 700px;
  margin: 0 auto;
}
.hero h1 {
  font-family: 'DM Serif Display', serif;
  font-size: 52px;
  line-height: 1.1;
  color: #fff;
  margin-bottom: 20px;
}
.hero-sub {
  color: #9ca3af;
  font-size: 16px;
  line-height: 1.7;
  margin-bottom: 32px;
}
.hero-actions {
  display: flex;
  gap: 14px;
  justify-content: center;
  margin-bottom: 48px;
}
.hero-btn {
  padding: 14px 32px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
}
.hero-btn--primary {
  background: #22c55e;
  color: #fff;
}
.hero-btn--primary:hover { background: #16a34a; transform: translateY(-2px); }
.hero-btn--outline {
  border: 1.5px solid #4b5563;
  color: #d1d5db;
}
.hero-btn--outline:hover { border-color: #9ca3af; color: #fff; }

.hero-stats {
  display: flex;
  gap: 48px;
  justify-content: center;
}
.stat { text-align: center; }
.stat-num {
  display: block;
  font-size: 28px;
  font-weight: 700;
  color: #22c55e;
}
.stat-label {
  font-size: 13px;
  color: #9ca3af;
}

/* Sections */
.section {
  max-width: 1100px;
  margin: 0 auto;
  padding: 80px 28px;
}
.section-title {
  font-family: 'DM Serif Display', serif;
  font-size: 32px;
  text-align: center;
  margin-bottom: 8px;
}
.section-sub {
  text-align: center;
  color: #6b7280;
  font-size: 15px;
  margin-bottom: 48px;
}

/* Features */
.feature-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
.feature-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 28px;
  transition: transform 0.2s, box-shadow 0.2s;
}
.feature-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px rgba(0,0,0,0.08);
}
.feature-icon {
  font-size: 28px;
  display: block;
  margin-bottom: 14px;
}
.feature-card h3 {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 8px;
}
.feature-card p {
  font-size: 13px;
  color: #6b7280;
  line-height: 1.6;
}

/* Pricing */
.price-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
.price-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 32px 28px;
  text-align: center;
  transition: transform 0.2s;
}
.price-card.featured {
  border-color: #22c55e;
  box-shadow: 0 0 0 2px #22c55e22;
  transform: scale(1.04);
}
.price-card h3 {
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 12px;
}
.price {
  font-family: 'DM Serif Display', serif;
  font-size: 36px;
  color: #22c55e;
  margin-bottom: 6px;
}
.price-desc {
  color: #9ca3af;
  font-size: 13px;
  margin-bottom: 20px;
}
.price-card ul {
  list-style: none;
  padding: 0;
  margin-bottom: 24px;
  text-align: left;
}
.price-card li {
  font-size: 13px;
  color: #374151;
  padding: 6px 0;
  border-bottom: 1px solid #f3f4f6;
}
.price-card li::before {
  content: '✓ ';
  color: #22c55e;
  font-weight: 700;
}
.price-btn {
  display: block;
  padding: 10px 20px;
  border-radius: 8px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  border: 1.5px solid #e5e7eb;
  transition: all 0.2s;
}
.price-btn:hover { background: #f3f4f6; }
.price-btn--primary {
  background: #22c55e;
  color: #fff;
  border-color: #22c55e;
}
.price-btn--primary:hover { background: #16a34a; }

/* About */
.about-content {
  max-width: 700px;
  margin: 0 auto;
}
.about-content p {
  font-size: 15px;
  color: #374151;
  line-height: 1.8;
  margin-bottom: 16px;
}

/* Footer */
.landing-footer {
  background: #111827;
  color: #9ca3af;
  padding: 48px 28px 28px;
}
.footer-inner {
  max-width: 1100px;
  margin: 0 auto;
  text-align: center;
}
.footer-brand {
  margin-bottom: 20px;
}
.footer-brand .brand-name { color: #fff; }
.footer-brand p { font-size: 13px; margin-top: 6px; }
.footer-links {
  display: flex;
  gap: 28px;
  justify-content: center;
  margin-bottom: 28px;
}
.footer-links a {
  color: #9ca3af;
  font-size: 13px;
  text-decoration: none;
}
.footer-links a:hover { color: #fff; }
.footer-copy {
  font-size: 12px;
  color: #6b7280;
  border-top: 1px solid #1f2937;
  padding-top: 20px;
}

@media (max-width: 768px) {
  .hero h1 { font-size: 32px; }
  .feature-grid, .price-grid { grid-template-columns: 1fr; }
  .hero-stats { gap: 24px; }
  .nav-links { gap: 16px; }
}
</style>
