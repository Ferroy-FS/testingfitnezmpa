// FitNez script.js — REST via localStorage + token/session + FAQ chatbot
const DB = {
  get: k => JSON.parse(localStorage.getItem(k) || 'null'),
  post: (k, d) => { localStorage.setItem(k, JSON.stringify(d)); return d; },
  put: (k, d) => { localStorage.setItem(k, JSON.stringify(d)); return d; },
  delete: k => { localStorage.removeItem(k); return true; }
};
const uid = () => Math.random().toString(36).slice(2, 10);
const ts  = () => new Date().toLocaleString();
const $   = id => document.getElementById(id);
const show = el => el && el.classList.remove('hidden');
const hide = el => el && el.classList.add('hidden');
const setMsg = (id, txt, type) => { const e=$(id); e.textContent=txt; e.className=`msg msg--${type}`; show(e); };

// ── SEED DATA ──
function seedUsers() {
  if (DB.get('users')) return;
  const users = [
    { id:uid(), name:'Alexandra Reed',  email:'admin@fitnez.com',   password:'123456', role:'admin',   joined:'2024-01-01', bio:'', phone:'' },
    { id:uid(), name:'Marcus Thompson', email:'trainer@fitnez.com', password:'123456', role:'trainer', joined:'2024-02-15', bio:'Certified trainer, 8 yrs exp.', phone:'+1 555 010 2020' },
    { id:uid(), name:'Sophie Williams', email:'member@fitnez.com',  password:'123456', role:'member',  joined:'2024-03-10', bio:'Fitness enthusiast.', phone:'+1 555 030 4040' }
  ];
  DB.post('users', users);
  DB.post('attendance', [
    { id:uid(), userId:users[2].id, member:'Sophie Williams', cls:'Yoga Flow',  date:'2025-02-20', status:'Present' },
    { id:uid(), userId:users[2].id, member:'Sophie Williams', cls:'HIIT Blast', date:'2025-02-22', status:'Present' }
  ]);
}

// ── AUTH ──
function handleLogin(e) {
  e.preventDefault();
  const email = $('login-email').value.trim(), pass = $('login-password').value;
  const user  = (DB.get('users') || []).find(u => u.email===email && u.password===pass);
  if (!user) { setMsg('login-error','Invalid email or password.','error'); return; }
  hide($('login-error'));
  const token = 'tkn_' + uid();
  DB.post('session', { token, userId:user.id, role:user.role, loginTime:ts() });
  DB.post('token', token);
  logAuth(user.email, 'LOGIN', user.role);
  bootDashboard(user);
}

function handleRegister(e) {
  e.preventDefault();
  const name = $('reg-name').value.trim(), email = $('reg-email').value.trim();
  const pass = $('reg-password').value, role = $('reg-role').value;
  const users = DB.get('users') || [];
  if (users.find(u => u.email===email)) { setMsg('register-error','Email already registered.','error'); return; }
  users.push({ id:uid(), name, email, password:pass, role, joined:new Date().toISOString().slice(0,10), bio:'', phone:'' });
  DB.put('users', users);
  hide($('register-error'));
  setMsg('register-success','✓ Account created! You can now log in.','success');
  logAuth(email, 'REGISTER', role);
  $('form-register').reset();
}

function handleLogout() {
  const s = DB.get('session');
  if (s) logAuth(s.userId, 'LOGOUT', s.role);
  DB.delete('session'); DB.delete('token');
  $('page-dashboard').style.display = 'none';
  $('page-auth').className = 'page active';
}

function checkSession() {
  const sess = DB.get('session'), tok = DB.get('token');
  if (!sess || !tok || sess.token!==tok) return;
  const user = (DB.get('users') || []).find(u => u.id===sess.userId);
  if (user) bootDashboard(user);
}

function logAuth(who, action, role) {
  const logs = DB.get('auth_logs') || [];
  logs.unshift({ id:uid(), who, action, role, ts:ts() });
  DB.put('auth_logs', logs.slice(0, 50));
}

// ── DASHBOARD BOOT ──
function bootDashboard(user) {
  $('page-auth').className = 'page hidden';
  $('page-dashboard').className = 'page';
  $('page-dashboard').style.display = 'block';
  $('sb-role').textContent = user.role;
  $('nav-members').style.display = (user.role==='admin'||user.role==='trainer') ? 'flex' : 'none';
  $('nav-authlog').style.display  = user.role==='admin' ? 'flex' : 'none';
  renderProfile(user);
  showSection('profile', document.querySelector('.nav-item[data-section="profile"]'));
}

// ── SECTION SWITCHER ──
function showSection(name, link) {
  document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(a => a.classList.remove('active'));
  const sec = $('section-' + name);
  if (sec) sec.classList.add('active');
  if (link) link.classList.add('active');
  if (name==='members')    renderMembers();
  if (name==='schedule')   renderSchedule();
  if (name==='attendance') renderAttendance();
  if (name==='authlog')    renderAuthLog();
  return false;
}

// ── PROFILE ──
function renderProfile(user) {
  const ini = user.name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
  $('avatar').textContent   = ini;
  $('p-name').textContent   = user.name;
  $('p-email').textContent  = user.email;
  $('p-role').textContent   = user.role;
  $('p-joined').textContent = user.joined;
  $('p-badge').textContent  = user.role;
  $('edit-name').value  = user.name;
  $('edit-phone').value = user.phone || '';
  $('edit-bio').value   = user.bio   || '';
}

function updateProfile(e) {
  e.preventDefault();
  const sess = DB.get('session'), users = DB.get('users') || [];
  const idx  = users.findIndex(u => u.id===sess.userId);
  if (idx<0) return;
  users[idx].name  = $('edit-name').value.trim()  || users[idx].name;
  users[idx].phone = $('edit-phone').value.trim();
  users[idx].bio   = $('edit-bio').value.trim();
  DB.put('users', users);
  renderProfile(users[idx]);
  setMsg('profile-msg','✓ Profile updated!','success');
  setTimeout(() => hide($('profile-msg')), 3000);
}

// ── MEMBERS ──
function renderMembers() {
  const users = DB.get('users') || [];
  $('members-body').innerHTML = users.map((u,i) => `<tr>
    <td>${i+1}</td><td>${u.name}</td><td>${u.email}</td>
    <td><span class="role-chip ${u.role}">${u.role}</span></td>
    <td>${u.joined}</td>
    <td><button class="btn btn--ghost" style="padding:4px 10px;font-size:12px"
        onclick="deleteUser('${u.id}')">Delete</button></td>
  </tr>`).join('');
}

function openAddMember()  { show($('add-member-form')); }
function closeAddMember() { hide($('add-member-form')); }

function submitAddMember(e) {
  e.preventDefault();
  const name = $('am-name').value.trim(), email = $('am-email').value.trim(), role = $('am-role').value;
  const users = DB.get('users') || [];
  if (users.find(u => u.email===email)) { setMsg('am-error','Email already exists.','error'); return; }
  users.push({ id:uid(), name, email, password:'changeme', role, joined:new Date().toISOString().slice(0,10), bio:'', phone:'' });
  DB.put('users', users); closeAddMember(); renderMembers();
}

function deleteUser(id) {
  if (!confirm('Delete this user?')) return;
  DB.put('users', (DB.get('users') || []).filter(u => u.id!==id));
  renderMembers();
}

// ── SCHEDULE ──
function renderSchedule() {
  const cls = [
    { name:'Yoga Flow',    day:'Monday',    time:'07:00–08:00', spots:15 },
    { name:'HIIT Blast',   day:'Tuesday',   time:'18:00–19:00', spots:12 },
    { name:'Pilates Core', day:'Wednesday', time:'09:00–10:00', spots:10 },
    { name:'Spin Cycle',   day:'Thursday',  time:'07:30–08:30', spots:20 },
    { name:'Zumba Dance',  day:'Friday',    time:'17:00–18:00', spots:25 },
    { name:'Body Combat',  day:'Saturday',  time:'10:00–11:00', spots:18 }
  ];
  $('schedule-grid').innerHTML = cls.map(c => `<div class="sched-card">
    <h4>${c.name}</h4><p>📅 ${c.day} · ${c.time}</p>
    <p>👤 Marcus Thompson</p><p>🪑 ${c.spots} spots</p>
  </div>`).join('');
}

// ── ATTENDANCE ──
function renderAttendance() {
  const att  = DB.get('attendance') || [];
  const sess = DB.get('session');
  const me   = (DB.get('users') || []).find(u => u.id===sess?.userId);
  const list = me?.role==='admin' ? att : att.filter(a => a.userId===sess?.userId);
  $('att-body').innerHTML = list.length ? list.map((a,i) => `<tr>
    <td>${i+1}</td><td>${a.member}</td><td>${a.cls}</td><td>${a.date}</td>
    <td><span class="role-chip member">${a.status}</span></td></tr>`).join('') :
    '<tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:24px">No records found.</td></tr>';
}

function checkIn() {
  const sess = DB.get('session');
  const me   = (DB.get('users') || []).find(u => u.id===sess?.userId);
  if (!me) return;
  const options = ['Yoga Flow','HIIT Blast','Pilates Core','Spin Cycle','Zumba Dance'];
  const cls = options[Math.floor(Math.random()*options.length)];
  const att = DB.get('attendance') || [];
  att.unshift({ id:uid(), userId:me.id, member:me.name, cls, date:new Date().toISOString().slice(0,10), status:'Present' });
  DB.put('attendance', att); renderAttendance();
}

// ── AUTH LOG ──
function renderAuthLog() {
  const logs = DB.get('auth_logs') || [];
  $('log-body').innerHTML = logs.length ? logs.map((l,i) => `<tr>
    <td>${i+1}</td><td>${l.who}</td><td>${l.action}</td>
    <td><span class="role-chip ${l.role}">${l.role}</span></td><td>${l.ts}</td></tr>`).join('') :
    '<tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:24px">No logs yet.</td></tr>';
}
function clearLogs() { if(confirm('Clear all auth logs?')) { DB.delete('auth_logs'); renderAuthLog(); } }

// ── AUTH TABS ──
function switchTab(tab) {
  hide($('form-login')); hide($('form-register'));
  [$('tab-login'),$('tab-register')].forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected','false'); });
  const isLogin = tab==='login';
  show(isLogin ? $('form-login') : $('form-register'));
  const btn = isLogin ? $('tab-login') : $('tab-register');
  btn.classList.add('active'); btn.setAttribute('aria-selected','true');
}

// ── FAQ CHATBOT ──
const FAQ_MENU = [
  { label:'📅 Class Schedule',       reply:'6 weekly classes: Yoga (Mon 7AM), HIIT (Tue 6PM), Pilates (Wed 9AM), Spin (Thu 7:30AM), Zumba (Fri 5PM), Body Combat (Sat 10AM).' },
  { label:'💰 Trainer Pricing',      reply:'Personal sessions from $40/hr. Packages: 5×$180, 10×$340. Group classes included in your membership.' },
  { label:'📢 Complaint / Feedback', reply:'Email support@fitnez.com — our team responds within 24 hours. You can also describe your issue here.' },
  { label:'ℹ️ About FitNez',         reply:'FitNez is a modern gym management platform — scheduling, attendance, trainer booking & live support. 🚀' },
  { label:'🔁 Back to Menu',          reply:'Sure! Here is the main menu again:' }
];
let faqOpen = false;

function toggleFAQ() {
  faqOpen = !faqOpen;
  faqOpen ? show($('faq-box')) : hide($('faq-box'));
  $('faq-toggle').setAttribute('aria-expanded', faqOpen);
  $('faq-icon').textContent = faqOpen ? '✕' : '💬';
}

function startFAQ() {
  hide($('faq-start-area')); show($('faq-options'));
  addBubble('Hi! 👋 Welcome to FitNez Support. What can I help you with today?', 'bot');
  setTimeout(showFAQMenu, 400);
}

function showFAQMenu() {
  $('faq-options').innerHTML = FAQ_MENU.map((item,i) =>
    `<button class="faq-opt-btn" onclick="selectFAQ(${i})">${item.label}</button>`).join('');
}

function selectFAQ(idx) {
  addBubble(FAQ_MENU[idx].label, 'user');
  setTimeout(() => { addBubble(FAQ_MENU[idx].reply, 'bot'); setTimeout(showFAQMenu, 400); }, 300);
}

function sendFAQMessage() {
  const input = $('faq-input'), text = input.value.trim();
  if (!text) return;
  addBubble(text, 'user'); input.value = '';
  const lc = text.toLowerCase();
  setTimeout(() => {
    const r = lc.includes('schedule')||lc.includes('class') ? FAQ_MENU[0].reply
      : lc.includes('price')||lc.includes('trainer')        ? FAQ_MENU[1].reply
      : lc.includes('complaint')||lc.includes('issue')      ? FAQ_MENU[2].reply
      : lc.includes('about')||lc.includes('fitnez')         ? FAQ_MENU[3].reply
      : 'Thanks for your message! For detailed help, email support@fitnez.com 📧';
    addBubble(r, 'bot');
  }, 400);
}

function addBubble(text, type) {
  const msgs = $('faq-messages');
  const d = document.createElement('div');
  d.className = `faq-bubble ${type}`; d.textContent = text;
  msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight;
}

// ── INIT ──
document.addEventListener('DOMContentLoaded', () => { seedUsers(); checkSession(); });
