<template>
  <!-- ═══════════════════════════════════════════════════════
       CONSENT BANNER (poin e.2)
       Calon pengguna dimintakan izin untuk menyimpan data
       cookies/localStorage sebagai trafik pengunjung
       ═══════════════════════════════════════════════════════ -->
  <div class="consent-overlay">
    <div class="consent-banner">
      <div class="consent-icon">🍪</div>
      <div class="consent-content">
        <h3>Izin Penyimpanan Data</h3>
        <p>
          FitNez menggunakan cookies dan localStorage untuk melacak kunjungan halaman
          sebagai data trafik pengunjung. Data ini mencakup informasi perangkat, browser,
          dan durasi kunjungan. Data tidak dikirim ke pihak ketiga.
        </p>
        <p class="consent-detail">
          Dengan mengizinkan, Anda menyetujui penyimpanan data berikut di perangkat Anda:
          <strong>visitor ID</strong>, <strong>session ID</strong>, dan <strong>consent status</strong>.
        </p>
      </div>
      <div class="consent-actions">
        <button class="consent-btn consent-btn--accept" @click="$emit('accept')">
          Izinkan
        </button>
        <button class="consent-btn consent-btn--decline" @click="$emit('decline')">
          Tolak
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConsentBanner',
  emits: ['accept', 'decline'],
};
</script>

<style scoped>
.consent-overlay {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 10000;
  padding: 20px;
  background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
  animation: slideUp 0.4s ease;
}

@keyframes slideUp {
  from { transform: translateY(100%); opacity: 0; }
  to   { transform: translateY(0); opacity: 1; }
}

.consent-banner {
  max-width: 680px;
  margin: 0 auto;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 24px 28px;
  display: flex;
  gap: 16px;
  align-items: flex-start;
  box-shadow: 0 8px 40px rgba(0,0,0,0.15);
}

.consent-icon {
  font-size: 32px;
  flex-shrink: 0;
  margin-top: 2px;
}

.consent-content h3 {
  font-size: 16px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 6px;
}

.consent-content p {
  font-size: 13px;
  color: #6b7280;
  line-height: 1.6;
  margin-bottom: 4px;
}

.consent-detail {
  font-size: 12px !important;
  color: #9ca3af !important;
}

.consent-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex-shrink: 0;
}

.consent-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.consent-btn--accept {
  background: #22c55e;
  color: #fff;
}
.consent-btn--accept:hover {
  background: #16a34a;
}

.consent-btn--decline {
  background: transparent;
  color: #6b7280;
  border: 1px solid #e5e7eb;
}
.consent-btn--decline:hover {
  background: #f3f4f6;
}

@media (max-width: 600px) {
  .consent-banner {
    flex-direction: column;
    align-items: stretch;
  }
  .consent-actions {
    flex-direction: row;
  }
  .consent-btn {
    flex: 1;
  }
}
</style>
