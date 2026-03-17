/* ── API Base ───────────────────────────────────────────────── */
const API_BASE = 'http://localhost/newsletter_php/backend/api';

/* ── Auth Helpers ──────────────────────────────────────────── */
const Auth = {
  getToken: () => localStorage.getItem('nms_token'),
  getUser:  () => { try { return JSON.parse(localStorage.getItem('nms_user')); } catch { return null; } },
  setSession: (token, user) => {
    localStorage.setItem('nms_token', token);
    localStorage.setItem('nms_user', JSON.stringify(user));
  },
  clear: () => { localStorage.removeItem('nms_token'); localStorage.removeItem('nms_user'); },
  isLoggedIn: () => !!localStorage.getItem('nms_token'),
  isAdmin:    () => { const u = Auth.getUser(); return u && u.role === 'admin'; },
  isFaculty:  () => { const u = Auth.getUser(); return u && u.role === 'faculty'; },
  requireAuth: (redirect = '../public/login.html') => {
    if (!Auth.isLoggedIn()) { window.location.href = redirect; return false; }
    return true;
  },
  requireAdmin: () => {
    if (!Auth.isAdmin()) { window.location.href = '../public/login.html'; return false; }
    return true;
  }
};

/* ── Route Table (method + pattern → PHP URL) ──────────────── */
const ROUTES = [
  // Auth
  ['POST',   /^\/auth\/login$/,                               () => 'auth.php?action=login'],
  ['POST',   /^\/auth\/register$/,                            () => 'auth.php?action=register'],
  ['GET',    /^\/auth\/me$/,                                  () => 'auth.php?action=me'],
  ['POST',   /^\/auth\/change-password$/,                     () => 'auth.php?action=change-password'],

  // Newsletters
  ['GET',    /^\/newsletters\/?$/,                            () => 'newsletters.php?action=list'],
  ['POST',   /^\/newsletters\/?$/,                            () => 'newsletters.php?action=create'],
  ['GET',    /^\/newsletters\/([^/]+)\/pdf$/,                 (m) => `newsletters.php?action=pdf&id=${m[1]}`],
  ['POST',   /^\/newsletters\/([^/]+)\/submit$/,              (m) => `newsletters.php?action=submit&id=${m[1]}`],
  ['GET',    /^\/newsletters\/([^/]+)$/,                      (m) => `newsletters.php?action=get&id=${m[1]}`],
  ['PUT',    /^\/newsletters\/([^/]+)$/,                      (m) => `newsletters.php?action=update&id=${m[1]}`],
  ['DELETE', /^\/newsletters\/([^/]+)$/,                      (m) => `newsletters.php?action=delete&id=${m[1]}`],

  // Articles
  ['POST',   /^\/articles\/upload-image$/,                    () => 'articles.php?action=upload-image'],
  ['GET',    /^\/articles\/my$/,                              () => 'articles.php?action=my'],
  ['GET',    /^\/articles\/newsletter\/([^/]+)$/,             (m) => `articles.php?action=by-newsletter&id=${m[1]}`],
  ['POST',   /^\/articles\/?$/,                               () => 'articles.php?action=create'],
  ['POST',   /^\/articles\/([^/]+)\/submit$/,                 (m) => `articles.php?action=submit&id=${m[1]}`],
  ['PUT',    /^\/articles\/([^/]+)$/,                         (m) => `articles.php?action=update&id=${m[1]}`],
  ['DELETE', /^\/articles\/([^/]+)$/,                         (m) => `articles.php?action=delete&id=${m[1]}`],

  // Admin
  ['GET',    /^\/admin\/stats$/,                              () => 'admin.php?action=stats'],
  ['GET',    /^\/admin\/articles\/pending$/,                  () => 'admin.php?action=pending-articles'],
  ['POST',   /^\/admin\/articles\/([^/]+)\/approve$/,         (m) => `admin.php?action=approve-article&id=${m[1]}`],
  ['POST',   /^\/admin\/articles\/([^/]+)\/reject$/,          (m) => `admin.php?action=reject-article&id=${m[1]}`],
  ['POST',   /^\/admin\/newsletters\/([^/]+)\/publish$/,      (m) => `admin.php?action=publish-newsletter&id=${m[1]}`],
  ['GET',    /^\/admin\/users$/,                              () => 'admin.php?action=users'],
  ['POST',   /^\/admin\/users\/?$/,                           () => 'admin.php?action=create-user'],
  ['PUT',    /^\/admin\/users\/([^/]+)$/,                     (m) => `admin.php?action=update-user&id=${m[1]}`],
  ['DELETE', /^\/admin\/users\/([^/]+)$/,                     (m) => `admin.php?action=delete-user&id=${m[1]}`],

  // Public
  ['GET', /^\/public\/newsletters\/?(\?.*)?$/, () => 'public.php?action=newsletters'],
  ['GET',    /^\/public\/newsletters\/([^/]+)$/,              (m) => `public.php?action=newsletter&id=${m[1]}`],
  ['GET',    /^\/public\/departments$\/?(\?.*)?$/,                     () => 'public.php?action=departments'],
  ['POST',   /^\/public\/subscribe$/,                         () => 'public.php?action=subscribe'],
];

function buildURL(endpoint, method) {
  const upper = method.toUpperCase();
  for (const [rm, pattern, builder] of ROUTES) {
    if (rm !== upper) continue;
    const m = endpoint.match(pattern);
    if (m) return `${API_BASE}/${builder(m)}`;
  }
  console.warn('No PHP route for:', method, endpoint);
  return `${API_BASE}${endpoint}`;
}

/* ── Fetch Wrapper ─────────────────────────────────────────── */
async function apiFetch(endpoint, options = {}) {
  const method  = (options.method || 'GET').toUpperCase();
  const token   = Auth.getToken();
  const headers = { 'Content-Type': 'application/json', ...options.headers };
  if (token) headers['Authorization'] = `Bearer ${token}`;
  const url = buildURL(endpoint, method);
  try {
    const res  = await fetch(url, { ...options, method, headers });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
    return data;
  } catch (err) {
    throw err;
  }
}

/* ── Upload Image ──────────────────────────────────────────── */
async function uploadImage(file) {
  const token = Auth.getToken();
  const form  = new FormData();
  form.append('image', file);
  const res  = await fetch(`${API_BASE}/articles.php?action=upload-image`, {
    method:  'POST',
    headers: { 'Authorization': `Bearer ${token}` },
    body:    form
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'Upload failed');
  return data.url;
}

/* ── Toast ─────────────────────────────────────────────────── */
function getToastContainer() {
  let el = document.getElementById('toast-container');
  if (!el) {
    el = document.createElement('div');
    el.id = 'toast-container';
    el.className = 'toast-container';
    document.body.appendChild(el);
  }
  return el;
}
function toast(message, type = 'info', duration = 4000) {
  const icons = { success:'✅', error:'❌', info:'ℹ️', warn:'⚠️' };
  const el    = document.createElement('div');
  el.className = `toast toast-${type}`;
  el.innerHTML = `<span>${icons[type]||'ℹ️'}</span><div style="flex:1"><div style="font-weight:600;font-size:14px">${message}</div></div><button onclick="this.parentElement.remove()" style="background:none;font-size:16px;opacity:.6;padding:0 4px">×</button>`;
  getToastContainer().appendChild(el);
  setTimeout(() => el.remove(), duration);
}

/* ── Loading ───────────────────────────────────────────────── */
function showLoading() {
  let el = document.getElementById('loading-overlay');
  if (!el) { el = document.createElement('div'); el.id='loading-overlay'; el.className='loading-overlay'; el.innerHTML='<div class="spinner"></div>'; document.body.appendChild(el); }
  el.style.display = 'grid';
}
function hideLoading() {
  const el = document.getElementById('loading-overlay');
  if (el) el.style.display = 'none';
}

/* ── Modal ─────────────────────────────────────────────────── */
function showModal({ title, body, onConfirm, confirmText='Confirm', confirmClass='btn-primary', showCancel=true }) {
  const backdrop = document.createElement('div');
  backdrop.className = 'modal-backdrop';
  backdrop.innerHTML = `<div class="modal">
    <div class="modal-header"><h4 style="font-family:var(--font-display)">${title}</h4><button class="btn btn-ghost btn-icon btn-close-modal" style="font-size:18px">×</button></div>
    <div class="modal-body">${body}</div>
    <div class="modal-footer">${showCancel?'<button class="btn btn-ghost btn-close-modal">Cancel</button>':''}<button class="btn ${confirmClass} btn-confirm">${confirmText}</button></div>
  </div>`;
  document.body.appendChild(backdrop);
  backdrop.querySelectorAll('.btn-close-modal').forEach(b => b.onclick = () => backdrop.remove());
  backdrop.querySelector('.btn-confirm').onclick = () => { onConfirm && onConfirm(backdrop); };
  return backdrop;
}

/* ── Helpers ───────────────────────────────────────────────── */
function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('en-IN', { year:'numeric', month:'short', day:'numeric' });
}
function statusBadge(status) {
  const labels = { draft:'Draft', submitted:'Pending Review', approved:'Approved', rejected:'Rejected', published:'Published' };
  return `<span class="badge badge-${status}">${labels[status]||status}</span>`;
}
function truncate(str, len=100) { return (!str)?'':str.length>len?str.slice(0,len)+'…':str; }
function logout() { Auth.clear(); window.location.href = '../public/login.html'; }

function renderNavUser() {
  const el = document.getElementById('nav-user');
  if (!el) return;
  const user = Auth.getUser();
  if (user) {
    el.innerHTML = `<span style="font-size:13px;color:rgba(255,255,255,.7)">👤 ${user.name}</span>
      <button class="btn btn-outline btn-sm" style="border-color:rgba(255,255,255,.3);color:#fff" onclick="logout()">Logout</button>`;
  } else {
    el.innerHTML = `<a href="../public/login.html" class="btn btn-accent btn-sm">Login</a>`;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  renderNavUser();
  const toggle  = document.getElementById('sidebar-toggle');
  const sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) toggle.onclick = () => sidebar.classList.toggle('open');
});
