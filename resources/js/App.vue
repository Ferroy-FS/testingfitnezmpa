<template>
  <div id="fitnez-app">
    <!-- Cookie Consent Banner (poin e.2) -->
    <ConsentBanner v-if="showConsent" @accept="handleConsent(true)" @decline="handleConsent(false)" />

    <!-- Router View -->
    <router-view />

    <!-- Toast Notification -->
    <Transition name="toast">
      <div v-if="toast.visible" class="toast-notification" :class="toast.type">
        {{ toast.message }}
      </div>
    </Transition>
  </div>
</template>

<script>
import { ref, onMounted, provide } from 'vue';
import ConsentBanner from './components/ConsentBanner.vue';
import Tracker from './utils/tracker.js';

export default {
  name: 'App',
  components: { ConsentBanner },
  setup() {
    const showConsent = ref(false);
    const toast = ref({ visible: false, message: '', type: 'success' });

    // ── Toast System ──
    function showToast(message, type = 'success') {
      toast.value = { visible: true, message, type };
      setTimeout(() => { toast.value.visible = false; }, 4000);
    }

    provide('showToast', showToast);

    // ── Consent Check (poin e.2) ──
    onMounted(() => {
      if (Tracker.isConsentPending()) {
        showConsent.value = true;
      } else if (Tracker.hasConsent()) {
        Tracker.trackPageVisit();
        Tracker.startTimeTracking();
      }
    });

    function handleConsent(granted) {
      Tracker.setConsent(granted);
      showConsent.value = false;

      if (granted) {
        Tracker.startTimeTracking();
      }
    }

    return { showConsent, toast, handleConsent };
  },
};
</script>

<style>
/* Toast animation */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  z-index: 9999;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.toast-notification.success {
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
}
.toast-notification.error {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}
.toast-enter-active { transition: all 0.3s ease; }
.toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from { opacity: 0; transform: translateY(-10px); }
.toast-leave-to { opacity: 0; transform: translateX(20px); }
</style>
