/* ==========================================================================
   Lyncott — app.js  (Vite entry)
   Centralized, modular, reusable front-end layer.

   Loads Bootstrap 5 + Font Awesome + theme.css, then exposes a small,
   documented API on window.Lx:

     Lx.http      -> get / post / put / del   (fetch + CSRF + JSON)
     Lx.toast     -> toast notifications
     Lx.loading   -> button + global loading states
     Lx.modal     -> show / hide Bootstrap modals by id
     Lx.form      -> serialize / fill forms
     Lx.money     -> MXN formatter
     Lx.theme     -> light/dark toggle (persisted)

   Vanilla JS only (Bootstrap is the single UI dependency).
   ========================================================================== */

import 'bootstrap/dist/css/bootstrap.min.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import * as bootstrap from 'bootstrap';
import '../css/theme.css';

import { Wizard } from './wizard.js';
import { initFlujo } from './steps/flujo.js';
import { initSolicitante } from './steps/solicitante.js';
import { initViaje } from './steps/viaje.js';
import { initGastos } from './steps/gastos.js';
import { initRevisar } from './steps/revisar.js';

window.bootstrap = bootstrap;

/* --------------------------------------------------------------------------
   CSRF token (from <meta name="csrf-token">)
   -------------------------------------------------------------------------- */
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/* --------------------------------------------------------------------------
   HTTP — thin fetch wrapper. Always JSON in/out, CSRF on writes, one error
   shape. Throws { status, message, errors } on non-2xx so callers can catch.
   -------------------------------------------------------------------------- */
async function request(method, url, body = null, { isForm = false } = {}) {
  const headers = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrf(),
  };
  const opts = { method, headers, credentials: 'same-origin' };

  if (body !== null) {
    if (isForm || body instanceof FormData) {
      opts.body = body instanceof FormData ? body : toFormData(body);
    } else {
      headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
  }

  let res;
  try {
    res = await fetch(url, opts);
  } catch {
    throw { status: 0, message: 'No se pudo conectar con el servidor.', errors: {} };
  }

  const text = await res.text();
  const data = text ? safeJson(text) : {};

  if (!res.ok) {
    throw {
      status: res.status,
      message: data.message || `Error ${res.status}`,
      errors: data.errors || {},
    };
  }
  return data;
}

const toFormData = (obj) => {
  const fd = new FormData();
  Object.entries(obj).forEach(([k, v]) => fd.append(k, v));
  return fd;
};
const safeJson = (t) => { try { return JSON.parse(t); } catch { return { message: t }; } };

const http = {
  get:  (url)              => request('GET', url),
  post: (url, body, o)     => request('POST', url, body, o),
  put:  (url, body, o)     => request('PUT', url, body, o),
  del:  (url)              => request('DELETE', url),
  /** multipart POST for file uploads (XML/PDF). */
  upload: (url, formData)  => request('POST', url, formData, { isForm: true }),
};

/* --------------------------------------------------------------------------
   Toasts
   -------------------------------------------------------------------------- */
function ensureToastHost() {
  let host = document.querySelector('.lx-toast-host');
  if (!host) {
    host = document.createElement('div');
    host.className = 'lx-toast-host';
    host.setAttribute('aria-live', 'polite');
    document.body.appendChild(host);
  }
  return host;
}
const toast = (message, type = 'default', ms = 2800) => {
  const host = ensureToastHost();
  const el = document.createElement('div');
  el.className = 'lx-toast' + (type === 'error' ? ' lx-toast--error' : type === 'ok' ? ' lx-toast--ok' : '');
  el.textContent = message;
  host.appendChild(el);
  requestAnimationFrame(() => el.classList.add('is-show'));
  setTimeout(() => {
    el.classList.remove('is-show');
    setTimeout(() => el.remove(), 300);
  }, ms);
};
toast.ok    = (m, ms) => toast(m, 'ok', ms);
toast.error = (m, ms) => toast(m, 'error', ms);

/* --------------------------------------------------------------------------
   Loading states
   -------------------------------------------------------------------------- */
const loading = {
  /** Spinner + disabled state on a single button while `fn()` runs. */
  async button(btn, fn) {
    if (!btn) return fn();
    const prev = btn.innerHTML;
    btn.classList.add('lx-busy');
    btn.disabled = true;
    try { return await fn(); }
    finally { btn.classList.remove('lx-busy'); btn.disabled = false; btn.innerHTML = prev; }
  },
  /** Full-page overlay while `fn()` runs. */
  async overlay(fn) {
    const ov = document.createElement('div');
    ov.className = 'lx-overlay';
    ov.innerHTML = '<div class="lx-overlay__spin" role="status" aria-label="Cargando"></div>';
    document.body.appendChild(ov);
    try { return await fn(); }
    finally { ov.remove(); }
  },
};

/* --------------------------------------------------------------------------
   Modal helpers (wrap Bootstrap.Modal, cache instances by id)
   -------------------------------------------------------------------------- */
const _modals = new Map();
const modal = {
  _get(id) {
    const el = document.getElementById(id);
    if (!el) return null;
    if (!_modals.has(id)) _modals.set(id, new bootstrap.Modal(el));
    return _modals.get(id);
  },
  show(id) { this._get(id)?.show(); },
  hide(id) { this._get(id)?.hide(); },
};

/* --------------------------------------------------------------------------
   Form helpers
   -------------------------------------------------------------------------- */
const form = {
  /** form or container -> plain object of [name]=value. */
  serialize(scope) {
    const out = {};
    scope.querySelectorAll('[name]').forEach((f) => {
      if (f.type === 'checkbox') out[f.name] = f.checked;
      else if (f.type === 'radio') { if (f.checked) out[f.name] = f.value; }
      else out[f.name] = f.value;
    });
    return out;
  },
  /** fill inputs in `scope` from a data object keyed by input name. */
  fill(scope, data) {
    Object.entries(data || {}).forEach(([k, v]) => {
      const f = scope.querySelector(`[name="${CSS.escape(k)}"]`);
      if (!f) return;
      if (f.type === 'checkbox') f.checked = !!v;
      else f.value = v ?? '';
    });
  },
  reset(scope) { scope.querySelectorAll('[name]').forEach((f) => {
    if (f.type === 'checkbox' || f.type === 'radio') f.checked = false; else f.value = '';
  }); },
};

/* --------------------------------------------------------------------------
   Money (MXN)
   -------------------------------------------------------------------------- */
const money = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(n) || 0);

/* --------------------------------------------------------------------------
   Theme toggle (persist in localStorage; wrapped in try/catch)
   -------------------------------------------------------------------------- */
const theme = {
  KEY: 'lx-theme',
  current() {
    const attr = document.documentElement.getAttribute('data-theme');
    if (attr) return attr;
    return matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  },
  apply(mode) {
    document.documentElement.setAttribute('data-theme', mode);
    try { localStorage.setItem(this.KEY, mode); } catch {}
    this._syncBtn();
  },
  toggle() { this.apply(this.current() === 'dark' ? 'light' : 'dark'); },
  _syncBtn() {
    const btn = document.getElementById('lxThemeBtn');
    if (btn) btn.innerHTML = this.current() === 'dark'
      ? '<i class="fa-solid fa-sun"></i>'
      : '<i class="fa-solid fa-moon"></i>';
  },
  init() {
    try {
      const saved = localStorage.getItem(this.KEY);
      if (saved) document.documentElement.setAttribute('data-theme', saved);
    } catch {}
    this._syncBtn();
    document.getElementById('lxThemeBtn')?.addEventListener('click', () => this.toggle());
    matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => this._syncBtn());
  },
};

/* --------------------------------------------------------------------------
   Expose + boot
   -------------------------------------------------------------------------- */
const Lx = { http, toast, loading, modal, form, money, theme, Wizard };
window.Lx = Lx;

document.addEventListener('DOMContentLoaded', () => {
  theme.init();
  // Boot the wizard if its shell is present on the page.
  const shell = document.getElementById('lxWizard');
  if (shell) {
    initFlujo(Lx);         // step 0 registers its hooks
    initSolicitante(Lx);   // step 1 registers its hooks
    initViaje(Lx);         // step 2 registers its hooks
    initGastos(Lx);        // step 3 registers its hooks
    initRevisar(Lx);       // step 4 registers its hooks + submit
    Wizard.init(shell);
  }
});

export default Lx;
