<template>
  <!-- ═══════════════════════════════════════════════════════
       DASHBOARD — Halaman Private (poin e.3, e.4)
       Navigasi menggunakan <nav>, <button> agar ringan
       Sidebar tetap di kiri
       ═══════════════════════════════════════════════════════ -->
  <div class="app-shell">

    <!-- ── Sidebar Navigation (menggunakan <nav> dan <button>) ── -->
    <aside class="sidebar">
      <div class="sidebar-brand">
        <span class="brand-bolt">⚡</span>
        <span class="brand-name">FitNez</span>
      </div>

      <nav class="sidebar-nav" aria-label="Menu utama">
        <button
          v-for="item in visibleNavItems"
          :key="item.section"
          type="button"
          class="nav-item"
          :class="{ active: activeSection === item.section }"
          @click="showSection(item.section)"
        >
          <span class="nav-icon" aria-hidden="true">{{ item.icon }}</span>
          <span class="nav-label">{{ item.label }}</span>
        </button>
      </nav>

      <footer class="sidebar-footer">
        <p class="sidebar-meta">Logged in as <strong>{{ currentUser?.role || '—' }}</strong></p>
        <button type="button" class="btn btn--logout" @click="handleLogout">Logout</button>
      </footer>
    </aside>

    <!-- ── Main Content ── -->
    <main class="main-content">

      <!-- Toast -->
      <Transition name="toast-anim">
        <div v-if="toastMsg" class="toast">{{ toastMsg }}</div>
      </Transition>

      <!-- Profile Section -->
      <section v-if="activeSection === 'profile'" class="content-section" aria-labelledby="profile-heading">
        <ProfileSection :user="currentUser" @updated="onProfileUpdated" @toast="showToast" />
      </section>

      <!-- Members Section -->
      <section v-if="activeSection === 'members'" class="content-section" aria-labelledby="members-heading">
        <MembersSection :currentUser="currentUser" @toast="showToast" />
      </section>

      <!-- Schedule Section -->
      <section v-if="activeSection === 'schedule'" class="content-section" aria-labelledby="schedule-heading">
        <ScheduleSection @toast="showToast" />
      </section>

      <!-- My Schedule Section -->
      <section v-if="activeSection === 'my-schedule'" class="content-section" aria-labelledby="my-schedule-heading">
        <MyScheduleSection :currentUser="currentUser" @toast="showToast" />
      </section>

      <!-- Attendance Section -->
      <section v-if="activeSection === 'attendance'" class="content-section" aria-labelledby="attendance-heading">
        <AttendanceSection :currentUser="currentUser" @toast="showToast" />
      </section>

      <!-- Auth Logs Section -->
      <section v-if="activeSection === 'logs'" class="content-section" aria-labelledby="logs-heading">
        <LogsSection @toast="showToast" />
      </section>

      <!-- Tracking Stats Section (admin) -->
      <section v-if="activeSection === 'tracking'" class="content-section" aria-labelledby="tracking-heading">
        <TrackingSection @toast="showToast" />
      </section>
    </main>

    <!-- FAQ Widget -->
    <FaqWidget />
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import Http from '../utils/http.js';
import { startPolling, stopPolling } from '../utils/polling.js';

import ProfileSection from '../components/ProfileSection.vue';
import MembersSection from '../components/MembersSection.vue';
import ScheduleSection from '../components/ScheduleSection.vue';
import MyScheduleSection from '../components/MyScheduleSection.vue';
import AttendanceSection from '../components/AttendanceSection.vue';
import LogsSection from '../components/LogsSection.vue';
import TrackingSection from '../components/TrackingSection.vue';
import FaqWidget from '../components/FaqWidget.vue';

export default {
  name: 'DashboardPage',
  components: {
    ProfileSection, MembersSection, ScheduleSection,
    MyScheduleSection, AttendanceSection, LogsSection,
    TrackingSection, FaqWidget,
  },
  setup() {
    const router = useRouter();
    const currentUser = ref(null);
    const activeSection = ref('profile');
    const toastMsg = ref('');

    // ── Navigation items (menggunakan <button>) ──
    const allNavItems = [
      { section: 'profile',     icon: '👤', label: 'Profile',        roles: ['admin', 'trainer', 'member'] },
      { section: 'members',     icon: '👥', label: 'Members',        roles: ['admin', 'trainer'] },
      { section: 'schedule',    icon: '📅', label: 'Schedule',       roles: ['admin', 'trainer', 'member'] },
      { section: 'my-schedule', icon: '🏋️', label: 'Latihan Saya',  roles: ['admin', 'trainer', 'member'] },
      { section: 'attendance',  icon: '✅', label: 'Attendance',     roles: ['admin', 'trainer', 'member'] },
      { section: 'logs',        icon: '🔐', label: 'Auth Logs',      roles: ['admin'] },
      { section: 'tracking',    icon: '📊', label: 'Web Tracking',   roles: ['admin'] },
    ];

    const visibleNavItems = computed(() => {
      const role = currentUser.value?.role;
      if (!role) return [];
      return allNavItems.filter(item => item.roles.includes(role));
    });

    function showSection(name) {
      activeSection.value = name;
    }

    function showToast(msg) {
      toastMsg.value = msg;
      setTimeout(() => { toastMsg.value = ''; }, 4000);
    }

    function onProfileUpdated(user) {
      currentUser.value = user;
      localStorage.setItem('fitnez_user', JSON.stringify(user));
    }

    async function handleLogout() {
      try { await Http.post('/auth/logout', {}); } catch (_) {}
      Http.clearToken();
      Http.setOtpVerified(false);
      localStorage.removeItem('fitnez_user');
      stopPolling();
      router.push({ name: 'login' });
    }

    onMounted(async () => {
      try {
        const data = await Http.get('/auth/me');
        currentUser.value = data.user;
        localStorage.setItem('fitnez_user', JSON.stringify(data.user));
      } catch (e) {
        Http.clearToken();
        Http.setOtpVerified(false);
        router.push({ name: 'login' });
        return;
      }

      // Real-time notifications
      startPolling((data) => {
        if (data.attendance?.length) {
          data.attendance.forEach(d => showToast(`${d.member} check-in ke ${d.cls}`));
        }
        if (data.logs?.length) {
          data.logs.forEach(d => showToast(`${d.who} ${d.action}`));
        }
      });
    });

    onUnmounted(() => { stopPolling(); });

    return {
      currentUser, activeSection, toastMsg, visibleNavItems,
      showSection, showToast, onProfileUpdated, handleLogout,
    };
  },
};
</script>

<style scoped>
.app-shell { display: flex; min-height: 100vh; }

/* ── Sidebar ── */
.sidebar {
  width: 240px; background: #fff; border-right: 1px solid #e5e7eb;
  display: flex; flex-direction: column; flex-shrink: 0;
  position: sticky; top: 0; height: 100vh;
}
.sidebar-brand { padding: 24px 22px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #e5e7eb; margin-bottom: 10px; }
.brand-bolt { font-size: 22px; }
.brand-name { font-family: 'DM Serif Display', serif; font-size: 20px; }

/* Nav menggunakan <button> */
.sidebar-nav { display: flex; flex-direction: column; gap: 2px; padding: 0 10px; flex: 1; }
.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 14px; border-radius: 8px; border: none; background: none;
  color: #6b7280; font-weight: 500; font-size: 14px;
  cursor: pointer; transition: all 0.2s; text-align: left;
  border-left: 3px solid transparent; width: 100%;
  font-family: 'DM Sans', sans-serif;
}
.nav-item:hover { background: #f9fafb; color: #111827; }
.nav-item.active {
  background: #dcfce7; color: #16a34a;
  border-left-color: #22c55e; font-weight: 600;
}
.nav-icon { font-size: 16px; flex-shrink: 0; }

.sidebar-footer { padding: 18px; border-top: 1px solid #e5e7eb; margin-top: auto; }
.sidebar-meta { font-size: 12px; color: #6b7280; margin-bottom: 10px; }
.sidebar-meta strong { color: #111827; }

.btn--logout {
  background: #fef2f2; color: #dc2626; width: 100%;
  border: 1px solid #fecaca; padding: 10px 20px;
  border-radius: 8px; font-weight: 600; font-size: 14px;
  cursor: pointer; transition: 0.2s;
}
.btn--logout:hover { background: #fecaca; }

/* ── Main ── */
.main-content { flex: 1; padding: 40px 44px; overflow-y: auto; background: #f9fafb; }
.content-section { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

/* Toast */
.toast {
  position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 8px;
  font-size: 13px; font-weight: 500; z-index: 9999;
  background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.toast-anim-enter-active { transition: all 0.3s ease; }
.toast-anim-leave-active { transition: all 0.3s ease; }
.toast-anim-enter-from { opacity: 0; transform: translateY(-10px); }
.toast-anim-leave-to { opacity: 0; transform: translateX(20px); }

@media (max-width: 768px) {
  .sidebar { width: 60px; }
  .nav-label, .sidebar-brand .brand-name, .sidebar-meta, .sidebar-footer .btn--logout { font-size: 0; }
  .sidebar-brand { justify-content: center; padding: 20px 0; }
  .nav-item { justify-content: center; padding: 12px; }
  .main-content { padding: 24px 20px; }
}
</style>
