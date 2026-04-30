<template>
  <div class="faq-widget">
    <button type="button" class="faq-toggle" @click="toggleFaq">
      <span>{{ faqOpen ? '✕' : '💬' }}</span>
      <span class="faq-label">FAQ</span>
    </button>

    <Transition name="faq-anim">
      <div v-if="faqOpen" class="faq-box">
        <header class="faq-header">
          <span>FitNez Support</span>
          <button type="button" class="faq-close" @click="toggleFaq">✕</button>
        </header>

        <div class="faq-messages" ref="messagesRef">
          <div v-for="(msg, i) in messages" :key="i" class="faq-bubble" :class="msg.type">
            <span v-html="msg.text"></span>
          </div>
        </div>

        <div v-if="showOptions" class="faq-options">
          <button v-for="(f, i) in faqData" :key="i" type="button" class="faq-opt-btn" @click="selectFaq(i)">{{ f.question }}</button>
          <button type="button" class="faq-opt-btn" style="color:#6b7280;border-color:#e5e7eb" @click="enableInput">✏️ Ketik pertanyaan sendiri</button>
        </div>

        <footer class="faq-footer">
          <div v-if="!started">
            <button type="button" class="btn btn--primary btn--full" @click="startFaq">Mulai Chat</button>
          </div>
          <div v-if="inputMode" class="faq-input-area">
            <input v-model="inputText" type="text" placeholder="Ketik pertanyaan..." @keydown.enter="sendMessage" />
            <button type="button" class="btn btn--primary" @click="sendMessage">Kirim</button>
          </div>
        </footer>
      </div>
    </Transition>
  </div>
</template>

<script>
import { ref, nextTick, onMounted } from 'vue';
import Http from '../utils/http.js';

export default {
  name: 'FaqWidget',
  setup() {
    const faqOpen = ref(false);
    const started = ref(false);
    const showOptions = ref(false);
    const inputMode = ref(false);
    const inputText = ref('');
    const messages = ref([]);
    const messagesRef = ref(null);
    const faqData = ref([]);

    function toggleFaq() { faqOpen.value = !faqOpen.value; }

    function addBubble(text, type) {
      messages.value.push({ text, type });
      nextTick(() => { if (messagesRef.value) messagesRef.value.scrollTop = messagesRef.value.scrollHeight; });
    }

    function startFaq() {
      started.value = true;
      showOptions.value = true;
      addBubble('Hi! 👋 Selamat datang di FitNez Support. Pilih pertanyaan di bawah.', 'bot');
    }

    function selectFaq(i) {
      const f = faqData.value[i];
      addBubble(f.question, 'user');
      showOptions.value = false;
      setTimeout(() => {
        addBubble(f.answer, 'bot');
        setTimeout(() => { addBubble('Ada yang lain?', 'bot'); showOptions.value = true; }, 600);
      }, 300);
    }

    function enableInput() {
      inputMode.value = true;
      showOptions.value = false;
    }

    function sendMessage() {
      const text = inputText.value.trim();
      if (!text) return;
      addBubble(text, 'user');
      inputText.value = '';
      const lc = text.toLowerCase();
      setTimeout(() => {
        const match = faqData.value.find(f => f.category && lc.includes(f.category));
        addBubble(match ? match.answer : 'Terima kasih! 😊 Silakan email supportfitnez@gmail.com.', 'bot');
        inputMode.value = false;
        setTimeout(() => { addBubble('Butuh bantuan lain?', 'bot'); showOptions.value = true; }, 700);
      }, 400);
    }

    onMounted(async () => {
      try {
        const data = await Http.get('/faq');
        faqData.value = data.faq || [];
      } catch (e) {
        faqData.value = [
          { question: '📅 Jadwal Kelas', answer: 'Yoga (Senin), HIIT (Selasa), Pilates (Rabu), Spin (Kamis), Zumba (Jumat), Combat (Sabtu)', category: 'schedule' },
          { question: '💰 Harga Trainer', answer: 'Sesi: Rp400k, 5 sesi: Rp1.8jt, 10 sesi: Rp3.4jt', category: 'pricing' },
          { question: '📢 Keluhan', answer: 'Email: supportfitnez@gmail.com', category: 'feedback' },
          { question: 'ℹ️ Tentang FitNez', answer: 'Platform manajemen gym modern.', category: 'general' },
        ];
      }
    });

    return { faqOpen, started, showOptions, inputMode, inputText, messages, messagesRef, faqData, toggleFaq, startFaq, selectFaq, enableInput, sendMessage };
  },
};
</script>

<style scoped>
.faq-widget { position: fixed; bottom: 28px; right: 28px; z-index: 1000; display: flex; flex-direction: column; align-items: flex-end; gap: 12px; }
.faq-toggle { background: #22c55e; color: #fff; border: none; border-radius: 50px; padding: 13px 22px; font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 20px #22c55e55; transition: 0.2s; }
.faq-toggle:hover { background: #16a34a; transform: translateY(-2px); }
.faq-label { font-size: 13px; }
.faq-box { width: 350px; background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; box-shadow: 0 8px 40px rgba(0,0,0,.12); display: flex; flex-direction: column; max-height: 490px; overflow: hidden; }
.faq-header { padding: 15px 20px; background: #111827; color: #fff; border-radius: 18px 18px 0 0; display: flex; justify-content: space-between; align-items: center; font-weight: 600; font-size: 14px; }
.faq-close { background: none; border: none; color: #9ca3af; font-size: 18px; cursor: pointer; padding: 2px 6px; border-radius: 4px; }
.faq-close:hover { color: #fff; }
.faq-messages { flex: 1; padding: 16px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; min-height: 160px; }
.faq-bubble { max-width: 86%; padding: 10px 14px; border-radius: 14px; font-size: 13px; line-height: 1.55; }
.faq-bubble.bot { background: #f3f4f6; color: #111827; align-self: flex-start; border-radius: 4px 14px 14px 14px; }
.faq-bubble.user { background: #22c55e; color: #fff; align-self: flex-end; border-radius: 14px 4px 14px 14px; }
.faq-options { padding: 0 14px 10px; display: flex; flex-direction: column; gap: 6px; }
.faq-opt-btn { background: #dcfce7; border: 1px solid #86efac; color: #16a34a; border-radius: 8px; padding: 9px 13px; font-size: 12.5px; font-weight: 500; cursor: pointer; text-align: left; transition: 0.2s; }
.faq-opt-btn:hover { background: #bbf7d0; border-color: #22c55e; }
.faq-footer { padding: 13px; border-top: 1px solid #e5e7eb; }
.faq-input-area { display: flex; gap: 8px; }
.faq-input-area input { flex: 1; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 13px; outline: none; }
.faq-input-area input:focus { border-color: #22c55e; }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn--primary { background: #22c55e; color: #fff; }
.btn--primary:hover { background: #16a34a; }
.btn--full { width: 100%; }
.faq-anim-enter-active { animation: slideUp 0.25s ease; }
.faq-anim-leave-active { animation: slideUp 0.25s ease reverse; }
@keyframes slideUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
@media (max-width: 480px) { .faq-box { width: calc(100vw - 32px); } }
</style>
